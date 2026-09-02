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
 * Archivage manuel d'un devis (back-office).
 *
 * Fait passer un devis « À commander » au statut « Archivé », avec la date
 * du jour posée sur `date_archivage` — meme resultat que l'archivage
 * automatique a 30 jours (`drivematic_configurator_cron()`), mais declenche
 * a la demande. Ne s'applique jamais a un devis « Commandé » (decision
 * utilisatrice) : re-verifie cote serveur, pas seulement en cachant le
 * bouton.
 */
final class QuoteArchiveForm extends ConfirmFormBase {

  /**
   * Le devis a archiver, resolu par buildForm() depuis la route.
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
    return 'drivematic_configurator_quote_archive_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Archiver ce devis ?');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Un devis archivé ne peut plus être modifié.');
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
    return $this->t('Archiver');
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

    $this->quote->set('status', Quote::STATUS_ARCHIVE);
    $this->quote->set('date_archivage', $this->time->getRequestTime());
    $this->quote->save();

    $this->entityTypeManager->getStorage('quote_status_change')->create([
      'quote_id' => $this->quote->id(),
      'status' => Quote::STATUS_ARCHIVE,
      'uid' => $this->currentUser->id(),
    ])->save();

    $this->messenger()->addStatus($this->t('Devis @reference archivé.', [
      '@reference' => (string) $this->quote->label(),
    ]));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

}
