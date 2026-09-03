<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\drivematic_configurator\Entity\Quote;
use Drupal\drivematic_configurator\Entity\QuoteEquipmentLine;
use Drupal\drivematic_configurator\Service\PartnerDiscountResolver;
use Drupal\drivematic_configurator\Service\QuotePdfGenerator;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Remises par équipement (4 lignes fixes), accordées par Drive Matic.
 *
 * Embarqué dans QuoteDetailController::view() via `\Drupal::formBuilder()`
 * (pas de route dédiée : pas de besoin identifié en dehors de la page de
 * détail) — visible uniquement quand le devis est au statut « À commander »
 * ET que le visiteur a la permission `edit drivematic configurator quotes`
 * (double vérification faite par l'appelant ET ici, en défense en
 * profondeur).
 *
 * Exactement 4 lignes (rétrovision ext./int., télécommande VOR, double
 * pédalier auto-école), jamais une par ligne/configuration : un devis à
 * plusieurs véhicules peut porter plusieurs lignes du même équipement (une
 * par configuration) — un seul taux par type s'applique alors uniformément
 * à TOUTES les lignes de ce type sur CE devis (jamais au compte partenaire,
 * ni à un autre devis).
 *
 * N'écrit jamais `unit_price`/`discounted_unit_price`/`ht`/`discounted_ht`
 * (gelés, cf. Entity/QuoteEquipmentLine.php) : seul `dm_discount_rate` est
 * modifié par ligne, puis les totaux du devis sont recalculés à partir du
 * prix effectif (`QuoteEquipmentLine::getEffectiveDiscountedHt()`).
 * Enregistrer une remise remet aussi `date_commande` à l'heure actuelle —
 * ce qui redémarre le délai de 30 jours avant archivage automatique
 * (PRD F15, « cas limites »).
 *
 * Chaque ligne est préremplie avec `dm_discount_rate`, gelé dès la création
 * du devis (snapshot du taux partenaire à cet instant, ADR-043 addendum 2)
 * — jamais recalculé depuis le compte partenaire ensuite, même si celui-ci
 * change. `PartnerDiscountResolver` n'intervient ici qu'en repli défensif
 * (`resolveDefaultRate()`), pour les lignes antérieures à cette règle dont
 * `dm_discount_rate` serait encore NULL (cf. hook_update 11010/11011).
 *
 * Chaque LIGNE d'équipement (pas chaque type) dont le taux change réellement
 * génère sa propre entrée `QuoteDiscountChange` (auteur + ancien/nouveau
 * taux) — une resoumission sans changement n'en crée aucune. Voir
 * `logDiscountChange()`.
 */
final class QuoteDiscountForm extends FormBase {

  /**
   * Types equipement catalogue, dans l'ordre d'affichage.
   *
   * Memes cles que QuoteCalculator::EQUIPMENT_CATALOG_TYPES/
   * QuoteDetailController (ADR-028 : mapping duplique faute de source
   * canonique unique dans le projet). Libelles resolus separement
   * (equipmentTypeLabel()) : un `t()` ne doit jamais recevoir de variable.
   */
  private const EQUIPMENT_TYPES = ['retrovision_ext', 'retrovision_int', 'telecommande_vor', 'pedalier'];

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected TimeInterface $time,
    protected QuotePdfGenerator $pdfGenerator,
    protected PartnerDiscountResolver $discountResolver,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('datetime.time'),
      $container->get('drivematic_configurator.quote_pdf_generator'),
      $container->get('drivematic_configurator.partner_discount_resolver'),
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
    $account = $quote->getOwner();
    $lines_by_type = $this->loadLinesByType($quote);

    $form['rates'] = [
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => [$this->t('Équipement'), $this->t('Remise Drive Matic (%)')],
    ];

    foreach (self::EQUIPMENT_TYPES as $type) {
      $label = $this->equipmentTypeLabel($type);
      $default_rate = $this->resolveDefaultRate($lines_by_type[$type] ?? [], $account, $type);

      $form['rates'][$type]['label'] = ['#plain_text' => $label];
      $form['rates'][$type]['rate'] = [
        '#type' => 'number',
        '#title' => $this->t('Remise Drive Matic (%) pour @label', ['@label' => $label]),
        '#title_display' => 'invisible',
        '#min' => 0,
        '#max' => 100,
        '#step' => 0.01,
        '#default_value' => $default_rate,
      ];
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Enregistrer les remises'),
    ];

    return $form;
  }

  /**
   * Libellé d'un type d'équipement catalogue (jamais un `t()` sur variable).
   */
  private function equipmentTypeLabel(string $type): TranslatableMarkup {
    return match ($type) {
      'retrovision_ext' => $this->t('Rétrovision extérieure'),
      'retrovision_int' => $this->t('Rétrovision intérieure'),
      'telecommande_vor' => $this->t('Télécommande VOR'),
      'pedalier' => $this->t('Double pédalier auto-école'),
      default => $this->t('Équipement inconnu'),
    };
  }

  /**
   * Taux à préremplir pour un type d'équipement (ADR-043 addendum 2).
   *
   * Reprend `dm_discount_rate`, déjà gelé sur les lignes de ce type depuis
   * la création du devis (elles partagent la même valeur, celle du compte
   * partenaire À CET INSTANT — ne suit plus le compte ensuite). Le repli sur
   * la remise partenaire COURANTE ne joue qu'en défense, pour une ligne
   * antérieure à cette règle dont `dm_discount_rate` serait encore NULL
   * (hook_update 11010/11011) — jamais pour une ligne normale.
   *
   * @param \Drupal\drivematic_configurator\Entity\QuoteEquipmentLine[] $lines
   *   Les lignes de ce type sur ce devis (peut être vide).
   * @param \Drupal\user\UserInterface|null $account
   *   Le partenaire propriétaire du devis.
   * @param string $type
   *   Le type d'équipement catalogue.
   */
  private function resolveDefaultRate(array $lines, ?UserInterface $account, string $type): ?float {
    foreach ($lines as $line) {
      $rate = $line->get('dm_discount_rate')->value;
      if ($rate !== NULL) {
        return (float) $rate;
      }
    }

    return $this->discountResolver->resolve($account, $type);
  }

  /**
   * Charge les lignes disponibles du devis, groupées par type d'équipement.
   *
   * @return array<string, \Drupal\drivematic_configurator\Entity\QuoteEquipmentLine[]>
   *   Clé = type catalogue (`equipment_type`), valeur = ses lignes sur ce
   *   devis (toutes configurations confondues).
   */
  private function loadLinesByType(Quote $quote): array {
    $grouped = [];
    foreach ($this->loadAvailableLines($quote) as $line) {
      $type = (string) $line->get('equipment_type')->value;
      $grouped[$type][] = $line;
    }

    return $grouped;
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

    $account = $quote->getOwner();
    $lines_by_type = $this->loadLinesByType($quote);

    foreach ($form_state->getValue('rates') as $type => $values) {
      $new_rate = round((float) $values['rate'], 2);

      // Applique uniformement au type d'equipement, sur CE devis uniquement
      // (jamais au compte partenaire) : toutes les lignes de ce type,
      // toutes configurations confondues, recoivent le meme taux.
      foreach ($lines_by_type[$type] ?? [] as $line) {
        $stored_rate = $line->get('dm_discount_rate')->value;
        // `dm_discount_rate` est gele des la creation (ADR-043 addendum 2) :
        // ce repli sur la remise partenaire ne joue qu'en defense, pour une
        // ligne anterieure a cette regle encore a NULL (hook_update
        // 11010/11011) — sans quoi resoumettre la suggestion affichee sans
        // la changer journaliserait un faux changement.
        $old_rate = $stored_rate !== NULL
          ? round((float) $stored_rate, 2)
          : round($this->discountResolver->resolve($account, $type) ?? 0.0, 2);

        $line->set('dm_discount_rate', $new_rate)->save();
        $this->logDiscountChange($quote, $line, $old_rate, $new_rate);
      }
    }

    $this->recalculateTotals($quote);
    // Enregistrer une remise redemarre le delai des 30 jours avant
    // archivage automatique (PRD F15, « cas limites »).
    $quote->set('date_commande', $this->time->getRequestTime());
    $quote->save();

    $this->regenerateQuotePdf($quote);

    $this->messenger()->addStatus($this->t('Remises enregistrées.'));
  }

  /**
   * Régénère le PDF du devis (écrase le fichier d'origine), prix à jour.
   *
   * La remise DM est le seul événement, après la commande initiale, qui
   * change les prix d'un devis — voir QuotePdfGenerator. Un échec ne doit
   * pas faire échouer l'enregistrement de la remise, déjà effectué.
   */
  private function regenerateQuotePdf(Quote $quote): void {
    try {
      $this->pdfGenerator->generate($quote);
    }
    catch (\Throwable $e) {
      $this->messenger()->addWarning($this->t('Les remises ont été enregistrées, mais le PDF du devis n’a pas pu être régénéré.'));
      $this->getLogger('drivematic_configurator')->error('Échec de la régénération du PDF pour le devis @reference : @message', [
        '@reference' => $quote->get('reference')->value,
        '@message' => $e->getMessage(),
      ]);
    }
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
