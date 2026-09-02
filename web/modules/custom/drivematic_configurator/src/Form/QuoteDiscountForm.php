<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drivematic_configurator\Entity\Quote;
use Drupal\drivematic_configurator\Entity\QuoteEquipmentLine;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Remise exceptionnelle par ligne d'équipement, accordée par Drive Matic.
 *
 * Embarqué dans QuoteDetailController::view() via `\Drupal::formBuilder()`
 * (pas de route dédiée : pas de besoin identifié en dehors de la page de
 * détail) — visible uniquement quand le devis est au statut « À commander »
 * ET que le visiteur a la permission `edit drivematic configurator quotes`
 * (double vérification faite par l'appelant ET ici, en défense en
 * profondeur).
 *
 * N'écrit jamais `unit_price`/`discounted_unit_price`/`ht`/`discounted_ht`
 * (gelés, cf. Entity/QuoteEquipmentLine.php) : seul `dm_discount_rate` est
 * modifié par ligne, puis les totaux du devis sont recalculés à partir du
 * prix effectif (`QuoteEquipmentLine::getEffectiveDiscountedHt()`).
 * Enregistrer une remise remet aussi `date_commande` à l'heure actuelle —
 * ce qui redémarre le délai de 30 jours avant archivage automatique
 * (PRD F15, « cas limites »).
 *
 * Chaque ligne dont le taux change réellement génère une entrée
 * `QuoteDiscountChange` (auteur + ancien/nouveau taux) — une resoumission
 * sans changement n'en crée aucune. Voir `logDiscountChange()`.
 */
final class QuoteDiscountForm extends FormBase {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TimeInterface $time,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('datetime.time'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drivematic_configurator_quote_discount_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?Quote $quote = NULL): array {
    if (!$quote || $quote->get('status')->value !== Quote::STATUS_A_COMMANDER) {
      return $form;
    }

    $form['quote_id'] = ['#type' => 'value', '#value' => (int) $quote->id()];

    // Regroupe par configuration (une section par véhicule) — sinon, sur un
    // devis à plusieurs configurations, impossible de savoir à quel
    // véhicule s'applique la remise d'un équipement homonyme (ex. deux
    // configurations avec chacune une ligne « Rétrovision extérieure »).
    // `#tree` posé ici cascade a `lines`/`rate` en dessous.
    $form['configurations'] = ['#tree' => TRUE];

    $configuration_storage = $this->entityTypeManager->getStorage('quote_configuration');
    $line_storage = $this->entityTypeManager->getStorage('quote_equipment_line');

    $configuration_ids = $configuration_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('quote_id', $quote->id())
      ->sort('weight', 'ASC')
      ->execute();

    /** @var \Drupal\drivematic_configurator\Entity\QuoteConfiguration $configuration */
    foreach ($configuration_storage->loadMultiple($configuration_ids) as $configuration) {
      $line_ids = $line_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('configuration_id', $configuration->id())
        ->condition('unavailable', FALSE)
        ->sort('weight', 'ASC')
        ->execute();

      if (!$line_ids) {
        continue;
      }

      $form['configurations'][$configuration->id()] = [
        '#type' => 'details',
        '#title' => $this->t('@brand @model — @motorisation (@count véhicule(s))', [
          '@brand' => (string) $configuration->get('vehicle_brand')->value,
          '@model' => (string) $configuration->get('vehicle_model')->value,
          '@motorisation' => (string) $configuration->get('motorisation')->value,
          '@count' => (string) $configuration->get('vehicle_count')->value,
        ]),
        '#open' => TRUE,
      ];
      $form['configurations'][$configuration->id()]['lines'] = [
        '#type' => 'table',
        '#header' => [$this->t('Équipement'), $this->t('Remise Drive Matic (%)')],
      ];

      /** @var \Drupal\drivematic_configurator\Entity\QuoteEquipmentLine $line */
      foreach ($line_storage->loadMultiple($line_ids) as $line) {
        $form['configurations'][$configuration->id()]['lines'][$line->id()]['label'] = [
          '#plain_text' => $line->label(),
        ];
        $form['configurations'][$configuration->id()]['lines'][$line->id()]['rate'] = [
          '#type' => 'number',
          '#title' => $this->t('Remise Drive Matic (%) pour @label', ['@label' => $line->label()]),
          '#title_display' => 'invisible',
          '#min' => 0,
          '#max' => 100,
          '#step' => 0.01,
          '#default_value' => (float) ($line->get('dm_discount_rate')->value ?? 0),
        ];
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Enregistrer les remises'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $quote_storage = $this->entityTypeManager->getStorage('quote');
    /** @var \Drupal\drivematic_configurator\Entity\Quote|null $quote */
    $quote = $quote_storage->load($form_state->getValue('quote_id'));

    // Re-verification serveur : le formulaire n'est construit que dans le
    // bon statut, mais celui-ci peut avoir change entre l'affichage et la
    // soumission.
    if (!$quote || $quote->get('status')->value !== Quote::STATUS_A_COMMANDER) {
      $this->messenger()->addError($this->t("Ce devis n'est pas (ou plus) au statut « À commander »."));
      return;
    }

    $line_storage = $this->entityTypeManager->getStorage('quote_equipment_line');
    foreach ($form_state->getValue('configurations') as $configuration_values) {
      foreach ($configuration_values['lines'] as $line_id => $values) {
        /** @var \Drupal\drivematic_configurator\Entity\QuoteEquipmentLine|null $line */
        $line = $line_storage->load($line_id);
        if (!$line) {
          continue;
        }

        $old_rate = round((float) ($line->get('dm_discount_rate')->value ?? 0), 2);
        $new_rate = round((float) $values['rate'], 2);

        $line->set('dm_discount_rate', $new_rate)->save();
        $this->logDiscountChange($quote, $line, $old_rate, $new_rate);
      }
    }

    $this->recalculateTotals($quote);
    // Enregistrer une remise redemarre le delai des 30 jours avant
    // archivage automatique (PRD F15, « cas limites »).
    $quote->set('date_commande', $this->time->getRequestTime());
    $quote->save();

    $this->messenger()->addStatus($this->t('Remises enregistrées.'));
  }

  /**
   * Enregistre une entrée d'historique si le taux a réellement changé.
   *
   * @see \Drupal\drivematic_configurator\Entity\QuoteDiscountChange
   */
  private function logDiscountChange(Quote $quote, QuoteEquipmentLine $line, float $old_rate, float $new_rate): void {
    if ($old_rate === $new_rate) {
      return;
    }

    $this->entityTypeManager->getStorage('quote_discount_change')->create([
      'quote_id' => $quote->id(),
      'line_id' => $line->id(),
      'old_rate' => $old_rate,
      'new_rate' => $new_rate,
      'uid' => $this->currentUser()->id(),
    ])->save();
  }

  /**
   * Recalcule les totaux du devis à partir du prix effectif de ses lignes.
   */
  private function recalculateTotals(Quote $quote): void {
    $total_ht = 0.0;
    $total_discounted_ht = 0.0;

    foreach ($this->loadAvailableLines($quote) as $line) {
      $total_ht += (float) $line->get('ht')->value;
      $total_discounted_ht += $line->getEffectiveDiscountedHt() ?? 0.0;
    }

    $vat = $total_discounted_ht * 0.20;

    $quote->set('total_ht', $total_ht);
    $quote->set('total_discount', $total_ht - $total_discounted_ht);
    $quote->set('total_discounted_ht', $total_discounted_ht);
    $quote->set('total_vat', $vat);
    $quote->set('total_ttc', $total_discounted_ht + $vat);
  }

  /**
   * Charge toutes les lignes disponibles (hors `unavailable`) du devis.
   *
   * @return \Drupal\drivematic_configurator\Entity\QuoteEquipmentLine[]
   *   Les lignes, triées par configuration puis par poids.
   */
  private function loadAvailableLines(Quote $quote): array {
    $configuration_storage = $this->entityTypeManager->getStorage('quote_configuration');
    $line_storage = $this->entityTypeManager->getStorage('quote_equipment_line');

    $configuration_ids = $configuration_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('quote_id', $quote->id())
      ->sort('weight', 'ASC')
      ->execute();

    if (!$configuration_ids) {
      return [];
    }

    $line_ids = $line_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('configuration_id', $configuration_ids, 'IN')
      ->condition('unavailable', FALSE)
      ->sort('configuration_id', 'ASC')
      ->sort('weight', 'ASC')
      ->execute();

    /** @var \Drupal\drivematic_configurator\Entity\QuoteEquipmentLine[] */
    return $line_storage->loadMultiple($line_ids);
  }

}
