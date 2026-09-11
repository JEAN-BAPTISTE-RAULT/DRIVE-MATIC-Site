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
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\drivematic_configurator\Entity\Quote;
use Drupal\drivematic_configurator\Service\QuoteDraftBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Confirmation avant de reprendre un devis « à finaliser » (menu 3 points).
 *
 * Meme mecanisme de modale que QuoteConfigurationDeleteForm/
 * DeliveryAddressDeleteForm (ADR-034) — remplace l'ancien
 * QuoteModifyController, qui redirigeait directement sans avertir : le
 * catalogue pouvant evoluer entre la creation du devis et sa reprise
 * (imports DM non maitrises par ce projet), le partenaire doit d'abord en
 * etre informe avant que ses equipements/prix ne soient reajustes.
 *
 * A la confirmation, redirige desormais vers l'ETAPE 1 (Configuration), pas
 * l'etape 3 (Livraison, comportement precedent) : le partenaire doit pouvoir
 * revoir sa configuration avant de commander.
 *
 * ⚠️ `$form_state->getRedirect()` renvoie TOUJOURS FALSE sous soumission AJAX
 * reelle (`?ajax_form=1` ajoute par le JS core, `FormState::getRedirect()`
 * verifie `isRedirectDisabled()` — desactive explicitement par
 * `FormBuilder::buildForm()` des qu'il detecte ce parametre, AVANT meme que
 * les handlers de soumission ne s'executent), quel que soit ce que
 * `setRedirect()` a pose. `self::ajaxSubmit()` ne peut donc PAS s'appuyer
 * dessus pour savoir ou rediriger — d'ou la cle `ajax_redirect_url` posee
 * explicitement dans `self::submitForm()`, jamais affectee par ce mecanisme.
 * Piege invisible sur QuoteConfigurationDeleteForm/DeliveryAddressDeleteForm
 * (meme modele de modale) uniquement parce que leur cible de succes est
 * IDENTIQUE a `getCancelUrl()` — ici les deux divergent, ce qui l'a revele.
 */
final class QuoteModifyConfirmForm extends ConfirmFormBase {

  use ModalRequestTrait;

  private const TEMPSTORE_COLLECTION = 'drivematic_configurator';
  private const TEMPSTORE_DRAFT_KEY = 'draft';
  private const TEMPSTORE_EDITING_KEY = 'editing_quote_id';

  /**
   * Le devis a reprendre, fourni par l'upcasting du parametre de route.
   */
  protected ?Quote $quote = NULL;

  public function __construct(
    protected QuoteDraftBuilder $draftBuilder,
    protected PrivateTempStoreFactory $tempStoreFactory,
    protected AccountProxyInterface $currentUser,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('drivematic_configurator.quote_draft_builder'),
      $container->get('tempstore.private'),
      $container->get('current_user'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drivematic_configurator_quote_modify_confirm_form';
  }

  /**
   * {@inheritdoc}
   *
   * Vide volontairement (meme convention que les autres modales du
   * configurateur) : la description porte le seul texte visible.
   */
  public function getQuestion(): TranslatableMarkup {
    // phpcs:ignore Drupal.Semantics.FunctionT.EmptyString
    return new TranslatableMarkup('');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties(['type' => 'contact', 'status' => 1]);
    $contact_node = reset($nodes);
    $contact_url = $contact_node ? $contact_node->toUrl() : NULL;

    return $contact_url
      ? $this->t("Les équipements que vous aviez sélectionnés à l'enregistrement de ce devis ont pu être modifiés pour refléter le catalogue actuel (ajustement du prix ou suppression). En cas de question, n'hésitez pas à <a href=':url'>nous contacter</a>.", [':url' => $contact_url->toString()])
      : $this->t("Les équipements que vous aviez sélectionnés à l'enregistrement de ce devis ont pu être modifiés pour refléter le catalogue actuel (ajustement du prix ou suppression). En cas de question, n'hésitez pas à nous contacter.");
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Poursuivre');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelText(): TranslatableMarkup {
    return $this->t('Annuler');
  }

  /**
   * {@inheritdoc}
   *
   * « Annuler » ferme la modale sans rien faire : retour à « Mes devis ».
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('drivematic_partner.my_quotes', [], ['query' => ['onglet' => 'a-finaliser']]);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?Quote $quote = NULL): array {
    $this->quote = $quote;
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#ajax'] = ['callback' => '::ajaxSubmit'];

    // Voir DeliveryAddressForm::buildForm() : meme piege (pas de <h1> hors
    // route de node), meme omission en modale (titre deja porte par le
    // dialogue).
    if (!$this->isModalRequest()) {
      $form['#prefix'] = '<h1 class="page-title">' . $this->getDescription() . '</h1>';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Reprend l'ancienne logique de QuoteModifyController::modify() —
   * reconstruit le brouillon PrivateTempStore depuis le devis persiste, seul
   * le redirect final change (etape 1, pas etape 3).
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if (!$this->quote || (int) $this->quote->getOwnerId() !== (int) $this->currentUser->id()) {
      throw new AccessDeniedHttpException();
    }

    if ($this->quote->get('status')->value !== Quote::STATUS_A_FINALISER) {
      $this->messenger()->addError($this->t('Ce devis ne peut plus être modifié.'));
      $form_state->setRedirectUrl($this->getCancelUrl());
      return;
    }

    $result = $this->draftBuilder->build($this->quote);
    if ($result['dropped'] > 0) {
      $this->messenger()->addWarning($this->buildCatalogChangeWarning());
    }

    $temp_store = $this->tempStoreFactory->get(self::TEMPSTORE_COLLECTION);
    $temp_store->set(self::TEMPSTORE_DRAFT_KEY, $result['draft']);
    $temp_store->set(self::TEMPSTORE_EDITING_KEY, $this->quote->id());

    $form_state->setRedirect('drivematic_configurator.configuration');
    // $form_state->getRedirect() ci-dessus ne suffit PAS pour ajaxSubmit() :
    // voir la note de classe (core desactive silencieusement le redirect
    // sous ?ajax_form=1). Cle dediee, jamais affectee par ce mecanisme.
    $form_state->set('ajax_redirect_url', Url::fromRoute('drivematic_configurator.configuration'));
  }

  /**
   * Message d'avertissement « configuration(s) supprimée(s) ».
   *
   * Duplique QuoteDuplicateController::buildCatalogChangeWarning() (deja
   * duplique une 1re fois entre QuoteDuplicateController et l'ancien
   * QuoteModifyController — meme choix assume, pas de factorisation pour 3
   * usages) : une configuration referencant un vehicule/une motorisation
   * entierement supprime de la taxonomie (pas seulement depublie) ne peut
   * plus etre resolue du tout par QuoteVehicleTermResolver.
   */
  private function buildCatalogChangeWarning(): TranslatableMarkup {
    $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties(['type' => 'contact', 'status' => 1]);
    $contact_node = reset($nodes);
    $contact_url = $contact_node ? $contact_node->toUrl() : NULL;

    return $contact_url
      ? $this->t("Une ou plusieurs de vos configurations ont dû être supprimées en raison d'un changement du catalogue. Pour toute question, n'hésitez pas à <a href=':url'>nous contacter</a>.", [':url' => $contact_url->toString()])
      : $this->t("Une ou plusieurs de vos configurations ont dû être supprimées en raison d'un changement du catalogue. Pour toute question, n'hésitez pas à nous contacter.");
  }

  /**
   * Callback #ajax du bouton de confirmation — voir DeliveryAddressForm.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ferme la modale et redirige vers l'etape 1 (ou « Mes devis » en cas
   *   d'erreur, cf. submitForm()).
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    // Jamais $form_state->getRedirect() ici (voir note de classe) : toujours
    // FALSE sous AJAX reel, quel que soit ce que submitForm() a pose.
    /** @var \Drupal\Core\Url $redirect */
    $redirect = $form_state->get('ajax_redirect_url') ?? $this->getCancelUrl();
    $response->addCommand(new RedirectCommand($redirect->toString()));
    return $response;
  }

}
