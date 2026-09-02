<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\drivematic_configurator\Entity\Quote;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirmation manuelle d'une commande conclue par téléphone (back-office).
 *
 * Fait passer un devis « À commander » au statut « Commandé »
 * (Quote::STATUS_COMMANDE), avec la date du jour posée automatiquement sur
 * `date_confirmation` — action Drive Matic, rien ne se passe sur le site
 * (PRD F15/F16). Ne s'applique qu'aux devis « À commander » : re-vérifié
 * côté serveur (pas seulement en cachant le bouton), un devis dans un autre
 * état est refusé.
 */
final class QuoteMarkOrderedForm extends ConfirmFormBase {

  /**
   * Le devis a marquer commande, resolu par buildForm() depuis la route.
   */
  protected ?Quote $quote = NULL;

  public function __construct(
    protected TimeInterface $time,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('datetime.time'),
      $container->get('entity_type.manager'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drivematic_configurator_quote_mark_ordered_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Marquer ce devis comme commandé ?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t("La date du jour sera enregistrée comme date de confirmation de la commande. À utiliser uniquement lorsque le partenaire a conclu l'accord par téléphone directement avec Drive Matic.");
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return $this->quote ? $this->quote->toUrl() : Url::fromRoute('view.quotes.page_1');
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Marquer comme commandé');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?Quote $quote = NULL): array {
    $this->quote = $quote;
    $form = parent::buildForm($form, $form_state);

    // `$form_state->setRedirect()` n'a aucun effet sur une requete GET (cf.
    // DeliveryForm/QuoteForm) : etat refuse rendu inline, jamais redirige,
    // si le devis n'est pas (ou plus) au bon statut.
    if (!$quote || $quote->get('status')->value !== Quote::STATUS_A_COMMANDER) {
      return [
        'message' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t("Ce devis n'est pas (ou plus) au statut « À commander » : action impossible."),
        ],
        'back' => [
          '#type' => 'link',
          '#title' => $this->t('← Retour au devis'),
          '#url' => $this->getCancelUrl(),
        ],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Re-verification serveur : la precondition ci-dessus ne protege que
    // l'affichage du formulaire, pas la soumission (etat pouvant changer
    // entre l'affichage et la validation).
    if (!$this->quote || $this->quote->get('status')->value !== Quote::STATUS_A_COMMANDER) {
      $this->messenger()->addError($this->t("Ce devis n'est pas (ou plus) au statut « À commander »."));
      $form_state->setRedirectUrl($this->getCancelUrl());
      return;
    }

    $this->quote->set('status', Quote::STATUS_COMMANDE);
    $this->quote->set('date_confirmation', $this->time->getRequestTime());
    $this->quote->save();

    $this->entityTypeManager->getStorage('quote_status_change')->create([
      'quote_id' => $this->quote->id(),
      'status' => Quote::STATUS_COMMANDE,
      'uid' => $this->currentUser->id(),
    ])->save();

    $this->messenger()->addStatus($this->t('Devis @reference marqué comme commandé.', [
      '@reference' => (string) $this->quote->label(),
    ]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
