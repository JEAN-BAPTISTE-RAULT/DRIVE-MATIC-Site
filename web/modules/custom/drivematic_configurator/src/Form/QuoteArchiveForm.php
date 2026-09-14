<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\drivematic_configurator\Entity\Quote;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Confirmation d'archivage manuel d'un devis (page « Mes devis », ADR-057).
 *
 * Même mécanisme de modale que QuoteDeleteForm (ConfirmFormBase +
 * ModalRequestTrait, dialogue Drupal core `use-ajax`). Ne s'applique qu'aux
 * devis « Commandé » (Quote::STATUS_COMMANDE) — re-vérifié côté serveur, pas
 * seulement en cachant le lien (ADR-057, précise ADR-045 : seul le
 * partenaire peut désormais archiver manuellement, depuis son tableau de
 * bord).
 */
final class QuoteArchiveForm extends ConfirmFormBase {

  use ModalRequestTrait;

  /**
   * Le devis a archiver, fourni par l'upcasting du parametre de route.
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
   *
   * Vide volontairement, même choix que QuoteDeleteForm/
   * DeliveryAddressDeleteForm (ADR-034) : cette modale n'a pas de titre, la
   * question se lit dans getDescription().
   */
  public function getQuestion(): TranslatableMarkup {
    // phpcs:ignore Drupal.Semantics.FunctionT.EmptyString
    return new TranslatableMarkup('');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Voulez-vous vraiment archiver ce devis ?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('drivematic_partner.my_quotes', [], ['query' => ['onglet' => 'en-cours']]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Oui');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText(): TranslatableMarkup {
    return $this->t('Non');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?Quote $quote = NULL): array {
    $this->quote = $quote;

    // Même réflexe que QuoteMarkOrderedForm : un devis qui n'est plus (ou
    // pas encore) « Commandé » entre l'affichage du lien et l'ouverture de
    // cette modale (ex. archivé entre-temps depuis un autre onglet) rend un
    // message inline plutôt qu'un formulaire de confirmation qui échouerait
    // à la soumission.
    if (!$quote || $quote->get('status')->value !== Quote::STATUS_COMMANDE) {
      return [
        'message' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t("Ce devis n'est pas (ou plus) au statut « Commandé » : action impossible."),
        ],
        'back' => [
          '#type' => 'link',
          '#title' => $this->t('← Retour à mes devis'),
          '#url' => $this->getCancelUrl(),
        ],
      ];
    }

    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#ajax'] = ['callback' => '::ajaxSubmit'];

    // Même piège que QuoteDeleteForm (voir sa note de classe) : pas de <h1>
    // hors route de node, omis en modale (titre déjà porté par le dialogue).
    if (!$this->isModalRequest()) {
      $form['#prefix'] = '<h1 class="page-title">' . $this->getDescription() . '</h1>';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Défense en profondeur : la route est déjà protégée par
    // `_entity_access: 'quote.update'` ; le statut est re-vérifié ici car
    // seul un devis « Commandé » est archivable manuellement (ADR-057).
    if (!$this->quote
      || (int) $this->quote->getOwnerId() !== (int) $this->currentUser->id()
      || $this->quote->get('status')->value !== Quote::STATUS_COMMANDE
    ) {
      throw new AccessDeniedHttpException();
    }

    $this->quote->set('status', Quote::STATUS_ARCHIVE);
    $this->quote->set('date_archivage', $this->time->getRequestTime());
    $this->quote->save();

    $this->entityTypeManager->getStorage('quote_status_change')->create([
      'quote_id' => $this->quote->id(),
      'status' => Quote::STATUS_ARCHIVE,
      'uid' => $this->currentUser->id(),
    ])->save();

    $this->messenger()->addStatus($this->t('Devis archivé.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Callback #ajax du bouton de confirmation — voir QuoteDeleteForm.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ferme la modale et redirige vers l'onglet « en cours ».
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $response->addCommand(new RedirectCommand($this->getCancelUrl()->toString()));
    return $response;
  }

}
