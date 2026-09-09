<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Form;

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
 * Confirmation de suppression d'un devis (modale, page « Mes devis », ADR-052).
 *
 * Meme mecanisme de modale que DeliveryAddressDeleteForm (voir sa note de
 * classe, ADR-034) : dialogue Drupal core (`use-ajax`), pas de composant
 * custom. Supprime le devis ET ses `quote_configuration`/
 * `quote_equipment_line` (jamais orphelines en base).
 */
final class QuoteDeleteForm extends ConfirmFormBase {

  use ModalRequestTrait;

  /**
   * Le devis a supprimer, fourni par l'upcasting du parametre de route.
   */
  protected ?Quote $quote = NULL;

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected AccountProxyInterface $currentUser,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drivematic_configurator_quote_delete_form';
  }

  /**
   * {@inheritdoc}
   *
   * Vide volontairement, meme choix que DeliveryAddressDeleteForm (demande
   * utilisatrice, ADR-034) : cette modale n'a pas de titre, la question se
   * lit dans getDescription().
   */
  public function getQuestion(): TranslatableMarkup {
    // phpcs:ignore Drupal.Semantics.FunctionT.EmptyString
    return new TranslatableMarkup('');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Voulez-vous vraiment supprimer ce devis ?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('drivematic_partner.my_quotes', [], ['query' => ['onglet' => 'a-finaliser']]);
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
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#ajax'] = ['callback' => '::ajaxSubmit'];

    // Meme piege que DeliveryAddressDeleteForm (voir sa note de classe) :
    // pas de <h1> hors route de node, omis en modale (titre deja porte par
    // le dialogue).
    if (!$this->isModalRequest()) {
      $form['#prefix'] = '<h1 class="page-title">' . $this->getDescription() . '</h1>';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Defense en profondeur : la route est deja protegee par
    // `_entity_access: 'quote.delete'`.
    if (!$this->quote || (int) $this->quote->getOwnerId() !== (int) $this->currentUser->id()) {
      throw new AccessDeniedHttpException();
    }

    $this->deleteQuoteAndRelated($this->quote);
    $this->messenger()->addStatus($this->t('Devis supprimé.'));
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Supprime le devis et ses entites liees (jamais orphelines en base).
   */
  private function deleteQuoteAndRelated(Quote $quote): void {
    $configuration_storage = $this->entityTypeManager->getStorage('quote_configuration');
    $line_storage = $this->entityTypeManager->getStorage('quote_equipment_line');

    $configuration_ids = $configuration_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('quote_id', $quote->id())
      ->execute();

    if ($configuration_ids) {
      $line_ids = $line_storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('configuration_id', $configuration_ids, 'IN')
        ->execute();
      if ($line_ids) {
        $line_storage->delete($line_storage->loadMultiple($line_ids));
      }
      $configuration_storage->delete($configuration_storage->loadMultiple($configuration_ids));
    }

    $quote->delete();
  }

  /**
   * Callback #ajax du bouton de confirmation — voir DeliveryAddressDeleteForm.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ferme la modale et redirige vers l'onglet « à finaliser ».
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $response->addCommand(new RedirectCommand($this->getCancelUrl()->toString()));
    return $response;
  }

}
