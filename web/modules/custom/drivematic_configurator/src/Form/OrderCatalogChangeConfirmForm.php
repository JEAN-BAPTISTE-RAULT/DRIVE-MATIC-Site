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
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\drivematic_configurator\Entity\Quote;
use Drupal\drivematic_configurator\Service\QuoteFinalizer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Confirmation d'un changement de catalogue detecte au clic « Commander ».
 *
 * Meme mecanisme de modale que les autres confirmations du configurateur
 * (ADR-034), mais construite directement via le service `form_builder`
 * depuis DeliveryForm::orderAjaxCallback() plutot que routee : elle n'a pas
 * besoin de parametre de route, tout ce qu'elle lit vient de la
 * PrivateTempStore (brouillon, adresse, devis en cours d'edition — deja en
 * place avant l'ouverture de cette modale, cf. DeliveryForm::validateForm()
 * et QuoteModifyConfirmForm/nouvelle creation).
 *
 * ADR-056 : DeliveryForm::orderSubmit() ne persiste RIEN quand un
 * changement de catalogue est detecte ; cette modale est la seule a
 * finaliser la commande dans ce cas, avec le tarif/la disponibilite
 * desormais a jour (QuoteFinalizer recalcule via QuotePersister, jamais les
 * valeurs vues avant la detection).
 */
final class OrderCatalogChangeConfirmForm extends ConfirmFormBase {

  private const TEMPSTORE_COLLECTION = 'drivematic_configurator';
  private const TEMPSTORE_KEY = 'draft';
  private const TEMPSTORE_EDITING_KEY = 'editing_quote_id';
  private const TEMPSTORE_CATALOG_SNAPSHOT_KEY = 'catalog_snapshot';
  private const TEMPSTORE_ADDRESS_ID_KEY = 'selected_delivery_address_id';

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected PrivateTempStoreFactory $tempStoreFactory,
    protected AccountProxyInterface $currentUser,
    protected QuoteFinalizer $quoteFinalizer,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('tempstore.private'),
      $container->get('current_user'),
      $container->get('drivematic_configurator.quote_finalizer'),
    );
  }

  /**
   * Brouillon du devis en cours (meme mecanisme que DeliveryForm).
   */
  private function tempStore(): PrivateTempStore {
    return $this->tempStoreFactory->get(self::TEMPSTORE_COLLECTION);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drivematic_configurator_order_catalog_change_confirm_form';
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
      ? $this->t("Attention, certains équipements ont dû être modifiés pour refléter le catalogue actuel (ajustement du prix ou suppression). En cas de question, n'hésitez pas à <a href=':url'>nous contacter</a>.", [':url' => $contact_url->toString()])
      : $this->t("Attention, certains équipements ont dû être modifiés pour refléter le catalogue actuel (ajustement du prix ou suppression). En cas de question, n'hésitez pas à nous contacter.");
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText(): TranslatableMarkup {
    return $this->t('Poursuivre la commande');
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
   * « Annuler » ferme la modale et retourne a l'etape 1 du configurateur.
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('drivematic_configurator.configuration');
  }

  /**
   * {@inheritdoc}
   *
   * Construite directement via le service `form_builder` (voir la note de
   * classe), pas navigueee : sans ceci, `#action` retomberait sur l'URL de
   * la requete courante au moment de la construction
   * (DeliveryForm::orderAjaxCallback(), donc `/configurer/livraison`) —
   * une soumission irait alors a DeliveryForm, pas a ce formulaire.
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);
    $form['#action'] = Url::fromRoute('drivematic_configurator.order_catalog_change_confirm')->toString();
    $form['actions']['submit']['#ajax'] = ['callback' => '::ajaxSubmit'];
    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Relit tout depuis la PrivateTempStore (formulaire distinct de
   * DeliveryForm, aucun acces a son $form_state) et finalise la commande
   * avec le tarif/la disponibilite recalcules a l'instant present.
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $draft = $this->tempStore()->get(self::TEMPSTORE_KEY) ?? [];
    $address_id = $this->tempStore()->get(self::TEMPSTORE_ADDRESS_ID_KEY);
    /** @var \Drupal\drivematic_configurator\Entity\DeliveryAddress|null $address */
    $address = $address_id ? $this->entityTypeManager->getStorage('delivery_address')->load($address_id) : NULL;
    if (!$draft || !$address || (int) $address->getOwnerId() !== (int) $this->currentUser->id()) {
      throw new AccessDeniedHttpException();
    }

    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

    $editing_quote_id = $this->tempStore()->get(self::TEMPSTORE_EDITING_KEY);
    /** @var \Drupal\drivematic_configurator\Entity\Quote|null $editing_quote */
    $editing_quote = $editing_quote_id ? $this->entityTypeManager->getStorage('quote')->load($editing_quote_id) : NULL;
    if ($editing_quote && (int) $editing_quote->getOwnerId() !== (int) $this->currentUser->id()) {
      throw new AccessDeniedHttpException();
    }

    $this->quoteFinalizer->finalize($draft, Quote::STATUS_A_COMMANDER, $account, $address, $editing_quote);

    $this->tempStore()->delete(self::TEMPSTORE_KEY);
    $this->tempStore()->delete(self::TEMPSTORE_EDITING_KEY);
    $this->tempStore()->delete(self::TEMPSTORE_CATALOG_SNAPSHOT_KEY);
    $this->tempStore()->delete(self::TEMPSTORE_ADDRESS_ID_KEY);

    $this->messenger()->addStatus($this->t("Félicitations, votre commande a bien été enregistrée et transmise à notre équipe !"));
    $form_state->setRedirect('drivematic_configurator.configuration');
    // $form_state->getRedirect() ne suffit PAS pour ajaxSubmit() : core
    // desactive silencieusement le redirect sous AJAX reel (voir la meme
    // note sur QuoteModifyConfirmForm). Ici la cible de succes est deja
    // identique a getCancelUrl(), donc le symptome ne se serait jamais vu —
    // pose quand meme explicitement pour ne pas dependre de cette
    // coincidence si l'une des deux cibles change un jour.
    $form_state->set('ajax_redirect_url', Url::fromRoute('drivematic_configurator.configuration'));
  }

  /**
   * Callback #ajax du bouton de confirmation — voir DeliveryAddressForm.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ferme la modale et redirige vers l'etape 1.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    // Jamais $form_state->getRedirect() ici (voir QuoteModifyConfirmForm).
    /** @var \Drupal\Core\Url $redirect */
    $redirect = $form_state->get('ajax_redirect_url') ?? $this->getCancelUrl();
    $response->addCommand(new RedirectCommand($redirect->toString()));
    return $response;
  }

}
