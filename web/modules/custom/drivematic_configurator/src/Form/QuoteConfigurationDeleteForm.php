<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Confirmation de suppression d'une configuration (modale, F14 etape 2/3).
 *
 * Meme mecanisme de modale que DeliveryAddressDeleteForm (voir sa note de
 * classe) — agit ici sur le brouillon `PrivateTempStore` de QuoteForm plutot
 * que sur une entite : pas d'`_entity_access` a verifier, la tempstore etant
 * deja scopee par utilisateur courant (`PrivateTempStore`, voir la note de
 * classe de QuoteForm) — aucun risque IDOR, une cle invalide/etrangere ne
 * peut agir que sur le brouillon du visiteur courant, jamais sur celui d'un
 * autre partenaire.
 *
 * Remplace l'ancien `QuoteForm::removeConfigurationSubmit()` (suppression
 * immediate, sans confirmation, sur simple clic).
 */
final class QuoteConfigurationDeleteForm extends ConfirmFormBase {

  use ModalRequestTrait;

  private const TEMPSTORE_COLLECTION = 'drivematic_configurator';
  private const TEMPSTORE_KEY = 'draft';

  /**
   * Cle de la configuration a supprimer, fournie par le parametre de route.
   */
  protected ?int $configurationKey = NULL;

  public function __construct(
    protected PrivateTempStoreFactory $tempStoreFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('tempstore.private'),
    );
  }

  /**
   * Brouillon du devis en cours (voir la note de classe de QuoteForm).
   */
  private function tempStore(): PrivateTempStore {
    return $this->tempStoreFactory->get(self::TEMPSTORE_COLLECTION);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drivematic_configurator_quote_configuration_delete_form';
  }

  /**
   * {@inheritdoc}
   *
   * Vide volontairement (meme demande utilisatrice que
   * DeliveryAddressDeleteForm) : cette modale n'a pas de titre — la
   * question se lit dans `getDescription()`, seul texte visible.
   */
  public function getQuestion(): TranslatableMarkup {
    // phpcs:ignore Drupal.Semantics.FunctionT.EmptyString
    return new TranslatableMarkup('');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): TranslatableMarkup {
    return $this->t('Voulez-vous vraiment supprimer cette configuration ?');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return Url::fromRoute('drivematic_configurator.quote');
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
  public function buildForm(array $form, FormStateInterface $form_state, ?int $configuration_key = NULL): array {
    $this->configurationKey = $configuration_key;
    $form = parent::buildForm($form, $form_state);
    $form['actions']['submit']['#ajax'] = ['callback' => '::ajaxSubmit'];

    // Voir DeliveryAddressForm::buildForm() : meme piege (pas de <h1> hors
    // route de node), meme omission en modale. `getQuestion()` etant vide,
    // la description sert de titre de repli ici.
    if (!$this->isModalRequest()) {
      $form['#prefix'] = '<h1 class="page-title">' . $this->getDescription() . '</h1>';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    if ($this->configurationKey === NULL) {
      return;
    }

    $draft = $this->tempStore()->get(self::TEMPSTORE_KEY) ?? [];
    unset($draft[$this->configurationKey]);
    if ($draft) {
      $this->tempStore()->set(self::TEMPSTORE_KEY, $draft);
    }
    else {
      $this->tempStore()->delete(self::TEMPSTORE_KEY);
    }
    $form_state->setRedirectUrl($this->getCancelUrl());
  }

  /**
   * Callback #ajax du bouton de confirmation — voir DeliveryAddressForm.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ferme la modale et redirige vers l'ecran Devis.
   */
  public function ajaxSubmit(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $response->addCommand(new RedirectCommand($this->getCancelUrl()->toString()));
    return $response;
  }

}
