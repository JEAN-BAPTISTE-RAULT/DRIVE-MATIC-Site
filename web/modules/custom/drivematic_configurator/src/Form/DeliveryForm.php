<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Form;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\drivematic_configurator\Entity\DeliveryAddress;
use Drupal\drivematic_configurator\Entity\Quote;
use Drupal\drivematic_configurator\Service\QuotePdfGenerator;
use Drupal\drivematic_configurator\Service\QuotePersister;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Ecran « Livraison » du configurateur (F14, etape 3/3, /configurer/livraison).
 *
 * Dernier ecran : adresse de facturation en lecture seule (compte, jamais
 * modifiable en front, PRD F14), choix d'une adresse de livraison parmi
 * celles du partenaire (amorcees automatiquement depuis le compte a la
 * premiere visite, voir ensureAtLeastOneAddress()), et les deux actions
 * finales qui font passer le brouillon `PrivateTempStore` (ADR-031) a une
 * persistance reelle (entites Quote/QuoteConfiguration/QuoteEquipmentLine,
 * QuotePersister) : « Enregistrer le devis » (Quote::STATUS_A_FINALISER) ou
 * « Commander » (Quote::STATUS_A_COMMANDER).
 *
 * Hors perimetre (confirme avec l'utilisatrice, voir
 * docs/plans/configurateur-etape-3-livraison.md §6) : la page « Tableau de
 * bord »/« Mes devis » (F13/F15) qui permettrait de consulter/reprendre un
 * devis apres coup — seuls la persistance et les messages de confirmation
 * sont implementes ici.
 */
final class DeliveryForm extends FormBase {

  private const TEMPSTORE_COLLECTION = 'drivematic_configurator';
  private const TEMPSTORE_KEY = 'draft';

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected PrivateTempStoreFactory $tempStoreFactory,
    protected AccountProxyInterface $currentUser,
    protected QuotePersister $quotePersister,
    protected MailManagerInterface $mailManager,
    protected QuotePdfGenerator $pdfGenerator,
    protected FileSystemInterface $fileSystem,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('tempstore.private'),
      $container->get('current_user'),
      $container->get('drivematic_configurator.quote_persister'),
      $container->get('plugin.manager.mail'),
      $container->get('drivematic_configurator.quote_pdf_generator'),
      $container->get('file_system'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drivematic_configurator_delivery_form';
  }

  /**
   * Brouillon du devis en cours (meme mecanisme que ConfigurationForm).
   */
  private function tempStore(): PrivateTempStore {
    return $this->tempStoreFactory->get(self::TEMPSTORE_COLLECTION);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $title = $this->t('Configurez votre véhicule et obtenez votre tarif');
    $form['#prefix'] = '<div class="configurator-page"><h1 class="page-title configurator-form__title">' . $title . '</h1>';
    $form['#suffix'] = '</div>';
    $form['#attributes']['class'][] = 'webform-submission-form';
    $form['#attributes']['class'][] = 'configurator-form';
    $form['#attributes']['class'][] = 'delivery-form';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $draft = $this->tempStore()->get(self::TEMPSTORE_KEY) ?? [];
    $form['stepper'] = $this->buildStepper();

    if (!$draft) {
      // `$form_state->setRedirect()` n'a aucun effet sur une requete GET
      // (uniquement pris en compte apres une soumission) : etat vide inline,
      // meme pattern que QuoteForm quand le brouillon est absent.
      $form['empty'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['quote-form__empty']],
        'message' => [
          '#type' => 'html_tag',
          '#tag' => 'p',
          '#value' => $this->t('Aucun devis en cours.'),
        ],
        'link' => [
          '#type' => 'link',
          '#title' => $this->t('Configurer un véhicule'),
          '#url' => Url::fromRoute('drivematic_configurator.configuration'),
          '#attributes' => ['class' => ['configurator-form__submit']],
        ],
      ];
      return $form;
    }

    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

    $form['billing'] = $this->buildBillingSection($account);
    $form['delivery_address'] = $this->buildAddressSelector($account);

    // Rangee propre (maquette 511:14844) : « Ajouter une nouvelle adresse »
    // n'est PAS group avec Enregistrer/Commander (2 rangees d'actions
    // distinctes, la 2e nettement plus bas) — jamais dans le meme
    // `#type: actions` que les boutons de soumission finale.
    $form['add_address'] = [
      '#type' => 'container',
      'link' => [
        '#type' => 'link',
        '#title' => $this->t('Ajouter une nouvelle adresse'),
        '#url' => Url::fromRoute('drivematic_configurator.delivery_address_add'),
        '#attributes' => [
          'class' => ['use-ajax', 'delivery-form__add-address'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode(['width' => 760]),
        ],
      ],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['save_draft'] = [
      '#type' => 'submit',
      '#value' => $this->t('Enregistrer le devis'),
      '#submit' => ['::saveDraftSubmit'],
      '#attributes' => ['class' => ['delivery-form__save']],
    ];
    $form['actions']['order'] = [
      '#type' => 'submit',
      '#value' => $this->t('Commander'),
      '#submit' => ['::orderSubmit'],
      '#attributes' => ['class' => ['delivery-form__order']],
    ];

    return $form;
  }

  /**
   * Construit le fil d'etapes (variante de celui de QuoteForm).
   *
   * « Configuration » et « Devis » sont franchies (cliquables), « Livraison »
   * courante.
   *
   * @return array
   *   Render array du fil d'etapes.
   */
  private function buildStepper(): array {
    return [
      '#type' => 'html_tag',
      '#tag' => 'ol',
      '#attributes' => ['class' => ['configurator-form__stepper']],
      'configuration' => [
        '#type' => 'link',
        '#title' => $this->t('Configuration'),
        '#url' => Url::fromRoute('drivematic_configurator.configuration'),
        '#prefix' => '<li class="configurator-form__step is-done">',
        '#suffix' => '</li>',
        '#attributes' => ['class' => ['configurator-form__step-link']],
      ],
      'quote' => [
        '#type' => 'link',
        '#title' => $this->t('Devis'),
        '#url' => Url::fromRoute('drivematic_configurator.quote'),
        '#prefix' => '<li class="configurator-form__step is-done">',
        '#suffix' => '</li>',
        '#attributes' => ['class' => ['configurator-form__step-link']],
      ],
      'delivery' => [
        '#type' => 'html_tag',
        '#tag' => 'li',
        '#value' => $this->t('Livraison'),
        '#attributes' => ['class' => ['configurator-form__step', 'is-current']],
      ],
    ];
  }

  /**
   * Construit le bloc « Mon adresse de facturation » (lecture seule).
   *
   * Deviation utilisatrice #1 (non modifiable en front) : lien de contact a
   * la place, jamais de formulaire d'edition — l'adresse de facturation
   * reste pilotee par le back-office (meme regle que
   * PersonalInformationForm).
   */
  private function buildBillingSection(UserInterface $account): array {
    $element = [
      '#type' => 'container',
      '#attributes' => ['class' => ['delivery-form__section']],
      'title' => [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $this->t('Mon adresse de facturation'),
        '#attributes' => ['class' => ['delivery-form__section-title']],
      ],
      'address' => $this->buildAddressTextLines(
        $account->get('field_company_name')->value,
        $account->get('field_company_address')->value,
        $account->get('field_address_complement')->value,
        $account->get('field_postal_code')->value,
        $account->get('field_city')->value,
      ),
    ];
    $element['address']['siret'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => $this->t('SIRET : @siret', ['@siret' => $account->get('field_siret')->value]),
    ];

    $contact_url = $this->loadContactUrl();
    if ($contact_url) {
      $element['contact'] = [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#attributes' => ['class' => ['delivery-form__billing-contact']],
        '#value' => $this->t("Veuillez <a href=':url'>nous contacter</a> si vous souhaitez modifier votre adresse de facturation.", [':url' => $contact_url->toString()]),
      ];
    }

    return $element;
  }

  /**
   * Construit la section « Sélectionner une adresse de livraison ».
   *
   * Amorce une premiere adresse si le partenaire n'en a encore aucune
   * (ensureAtLeastOneAddress()). Toujours affichee (meme a une seule
   * adresse) — retour utilisatrice du 2026-09-01, voir §7 du plan.
   */
  private function buildAddressSelector(UserInterface $account): array {
    $addresses = $this->ensureAtLeastOneAddress($account);
    $selected_id = (string) reset($addresses)->id();

    $element = [
      '#type' => 'fieldset',
      '#title' => $this->t('Sélectionner une adresse de livraison :'),
      '#attributes' => ['class' => ['delivery-form__section', 'delivery-form__addresses']],
    ];

    foreach ($addresses as $address) {
      $element[$address->id()] = $this->buildAddressRow($address, $selected_id);
    }

    return $element;
  }

  /**
   * Charge les adresses du partenaire, en amorçant la premiere au besoin.
   *
   * L'adresse amorcee (`is_default`) est une vraie entite `delivery_address`
   * comme les autres, mais n'expose pas les liens Modifier/Supprimer
   * (`buildAddressRow()`, retour utilisatrice) : seule identique a
   * l'adresse de facturation, la seule obligatoire a la creation du compte,
   * elle n'est jamais retirable. Si elle disparaissait malgre tout (aucun
   * chemin UI ne le permet), une nouvelle copie serait ré-amorcee au
   * prochain passage sur cet ecran.
   *
   * @return \Drupal\drivematic_configurator\Entity\DeliveryAddress[]
   *   Les adresses du partenaire, au moins une.
   */
  private function ensureAtLeastOneAddress(UserInterface $account): array {
    $storage = $this->entityTypeManager->getStorage('delivery_address');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', $account->id())
      ->sort('id', 'ASC')
      ->execute();

    if ($ids) {
      return array_values($storage->loadMultiple($ids));
    }

    /** @var \Drupal\drivematic_configurator\Entity\DeliveryAddress $seed */
    $seed = $storage->create([
      'uid' => $account->id(),
      'is_default' => TRUE,
      'raison_sociale' => $account->get('field_company_name')->value,
      'adresse' => $account->get('field_company_address')->value,
      'complement' => $account->get('field_address_complement')->value,
      'code_postal' => $account->get('field_postal_code')->value,
      'ville' => $account->get('field_city')->value,
    ]);
    $seed->save();

    return [$seed];
  }

  /**
   * Construit une ligne de la liste d'adresses (radio + texte + actions).
   *
   * Radio construit hors de `#type: radios` (elements `#type: radio`
   * individuels partageant le meme `#parents`, meme mecanisme que
   * `#type: tableselect`) : place le texte et les liens Modifier/Supprimer
   * en dehors du `<label>` du bouton radio, pour eviter qu'un clic sur un
   * lien ne selectionne aussi la radio (un lien a l'interieur du `<label>`
   * genere par `#type: radios` declencherait les deux a la fois). L'intitule
   * accessible complet reste porte par la radio elle-meme
   * (`#title_display: invisible`) ; le bloc visuel est donc masque aux
   * lecteurs d'ecran (`aria-hidden`) pour ne pas le faire annoncer deux fois.
   *
   * Les liens Modifier/Supprimer sont omis pour l'adresse par defaut
   * (`is_default`, identique a l'adresse de facturation) : seules les
   * adresses ajoutees en plus par le partenaire sont modifiables/
   * supprimables (retour utilisatrice).
   */
  private function buildAddressRow(DeliveryAddress $address, string $selectedId): array {
    $id = (string) $address->id();
    $summary = $this->formatAddressSummary(
      $address->get('raison_sociale')->value,
      $address->get('adresse')->value,
      $address->get('complement')->value,
      $address->get('code_postal')->value,
      $address->get('ville')->value,
    );

    $card = [
      '#type' => 'container',
      '#attributes' => ['class' => ['delivery-form__address-row']],
      'radio' => [
        '#type' => 'radio',
        '#title' => $summary,
        '#title_display' => 'invisible',
        '#return_value' => $id,
        '#parents' => ['delivery_address'],
        '#default_value' => $selectedId,
        '#attributes' => ['class' => ['delivery-form__address-radio']],
      ],
      'content' => array_merge(
        [
          '#type' => 'container',
          '#attributes' => ['class' => ['delivery-form__address-text'], 'aria-hidden' => 'true'],
        ],
        $this->buildAddressTextLines(
          $address->get('raison_sociale')->value,
          $address->get('adresse')->value,
          $address->get('complement')->value,
          $address->get('code_postal')->value,
          $address->get('ville')->value,
        ),
      ),
    ];

    if (!$address->get('is_default')->value) {
      $card['actions'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['delivery-form__address-actions']],
        'modify' => [
          '#type' => 'link',
          '#title' => [
            'icon' => [
              '#type' => 'html_tag',
              '#tag' => 'span',
              '#value' => '',
              '#attributes' => ['class' => ['delivery-form__modify-icon']],
            ],
            'text' => ['#plain_text' => $this->t('Modifier')],
          ],
          '#url' => Url::fromRoute('drivematic_configurator.delivery_address_edit', ['delivery_address' => $id]),
          '#attributes' => [
            'class' => ['use-ajax', 'delivery-form__modify'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode(['width' => 760]),
            'aria-label' => $this->t('Modifier cette adresse de livraison'),
          ],
        ],
        'delete' => [
          '#type' => 'link',
          '#title' => $this->t('Supprimer'),
          '#url' => Url::fromRoute('drivematic_configurator.delivery_address_delete', ['delivery_address' => $id]),
          '#attributes' => [
            'class' => ['use-ajax', 'delivery-form__delete'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => Json::encode(['width' => 500]),
            'aria-label' => $this->t('Supprimer cette adresse de livraison'),
          ],
        ],
      ];
    }

    // Wrapper EXTERIEUR volontairement nu (aucune classe visuelle) : la
    // fondation `forms` applique `display: contents` a tout
    // `.fieldset-wrapper > div.js-form-wrapper` (Drupal ajoute cette classe
    // a tout `#type: container`, et ce conteneur est un enfant DIRECT du
    // fieldset) — annule silencieusement toute mise en page posee dessus
    // (meme piege documente sur `&__equipment-quantity`,
    // `_configurator-form.scss`). La vraie carte visuelle (`&__address-row`)
    // est donc un niveau plus bas (`card`), petit-enfant du fieldset,
    // jamais cible par ce selecteur.
    return [
      '#type' => 'container',
      'card' => $card,
    ];
  }

  /**
   * Construit le texte d'une adresse sur 3 lignes (`<br>`), sans saut.
   *
   * Un seul `<p>` (pas 3), donc aucune marge/paragraphe entre les lignes —
   * seulement des retours a la ligne (`<br>`). Reutilise pour la
   * facturation (lecture seule) et chaque ligne de la liste de livraison —
   * le saut de ligne qui precede le Siret (facturation uniquement) reste
   * porte par son propre `<p>`, ajoute a part par l'appelant.
   *
   * @return array
   *   1 element `html_tag` (paragraphe), a fusionner dans un conteneur.
   */
  private function buildAddressTextLines(?string $raisonSociale, ?string $adresse, ?string $complement, ?string $codePostal, ?string $ville): array {
    return [
      'summary' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#value' => $this->t('@raison_sociale<br>@adresse<br>@code_postal @ville', [
          '@raison_sociale' => $raisonSociale,
          '@adresse' => $this->formatAddressLine($adresse, $complement),
          '@code_postal' => $codePostal,
          '@ville' => $ville,
        ]),
      ],
    ];
  }

  /**
   * Formate une adresse sur une seule ligne : « Raison, Adresse, CP Ville ».
   */
  private function formatAddressSummary(?string $raisonSociale, ?string $adresse, ?string $complement, ?string $codePostal, ?string $ville): string {
    return trim(sprintf(
      '%s, %s, %s %s',
      $raisonSociale,
      $this->formatAddressLine($adresse, $complement),
      $codePostal,
      $ville,
    ));
  }

  /**
   * Formate la ligne « adresse, complément » (complément optionnel).
   */
  private function formatAddressLine(?string $adresse, ?string $complement): string {
    return $complement ? $adresse . ', ' . $complement : (string) $adresse;
  }

  /**
   * Charge l'URL de la page Contact.
   *
   * Node de type `contact`, exemplaire unique du site.
   *
   * @return \Drupal\Core\Url|null
   *   L'URL de la page, ou NULL si le node n'existe pas (degradation
   *   gracieuse : le lien est simplement omis).
   */
  private function loadContactUrl(): ?Url {
    $nodes = $this->entityTypeManager->getStorage('node')->loadByProperties(['type' => 'contact', 'status' => 1]);
    $node = reset($nodes);
    return $node ? $node->toUrl() : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    if (!$this->tempStore()->get(self::TEMPSTORE_KEY)) {
      return;
    }

    $address_id = $form_state->getValue('delivery_address');
    $address = $address_id ? $this->entityTypeManager->getStorage('delivery_address')->load($address_id) : NULL;

    // Ne fait jamais confiance a l'ID soumis sans verifier la propriete :
    // une valeur trafiquee pourrait pointer vers l'adresse d'un autre
    // partenaire (IDOR).
    if (!$address || (int) $address->getOwnerId() !== (int) $this->currentUser->id()) {
      $form_state->setErrorByName('delivery_address', $this->t('Adresse de livraison invalide.'));
      return;
    }

    $form_state->set('selected_delivery_address', $address);
  }

  /**
   * {@inheritdoc}
   *
   * Non utilise : chaque bouton a son propre #submit (meme pattern que
   * QuoteForm).
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
  }

  /**
   * Callback #submit de « Enregistrer le devis ».
   */
  public function saveDraftSubmit(array &$form, FormStateInterface $form_state): void {
    $this->persistQuote($form_state, Quote::STATUS_A_FINALISER);
  }

  /**
   * Callback #submit de « Commander ».
   */
  public function orderSubmit(array &$form, FormStateInterface $form_state): void {
    $quote = $this->persistQuote($form_state, Quote::STATUS_A_COMMANDER);
    $attachments = $this->generateQuotePdfAttachment($quote);
    $this->sendOrderConfirmationEmail($quote, $attachments);
    $this->sendInternalOrderNotification($quote, $attachments);
  }

  /**
   * Génère le PDF du devis et construit la pièce jointe pour hook_mail().
   *
   * Un échec de génération ne doit jamais empêcher l'envoi des e-mails de
   * confirmation ni faire échouer une commande déjà enregistrée en base —
   * seuls les e-mails partent alors sans pièce jointe.
   *
   * @return array[]
   *   Tableau `$message['params']['attachments']` (format attendu par
   *   symfony_mailer/LegacyMailerHelper::emailFromArray()), vide en cas
   *   d'échec.
   */
  private function generateQuotePdfAttachment(Quote $quote): array {
    try {
      $uri = $this->pdfGenerator->generate($quote);
    }
    catch (\Throwable $e) {
      $this->getLogger('drivematic_configurator')->error('Échec de la génération du PDF pour le devis @reference : @message', [
        '@reference' => $quote->get('reference')->value,
        '@message' => $e->getMessage(),
      ]);
      return [];
    }

    $attachment = [
      'filepath' => $this->fileSystem->realpath($uri),
      'filename' => $quote->get('reference')->value . '.pdf',
      'filemime' => 'application/pdf',
    ];

    return [$attachment];
  }

  /**
   * Persiste le devis, affiche le message de confirmation et repart a zero.
   *
   * Redirige vers l'etape 1 plutot qu'un « tableau de bord » : cette page
   * n'existe pas encore (F13/F15, hors perimetre — confirme avec
   * l'utilisatrice), seuls la persistance et les messages sont implementes
   * ici.
   */
  private function persistQuote(FormStateInterface $form_state, string $status): Quote {
    $draft = $this->tempStore()->get(self::TEMPSTORE_KEY) ?? [];
    $address = $form_state->get('selected_delivery_address');
    if (!$draft || !$address) {
      throw new AccessDeniedHttpException();
    }

    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    $quote = $this->quotePersister->persist($draft, $status, $account, $address);
    $this->tempStore()->delete(self::TEMPSTORE_KEY);

    $this->messenger()->addStatus($this->buildConfirmationMessage($status));
    $form_state->setRedirect('drivematic_configurator.configuration');

    return $quote;
  }

  /**
   * Envoie l'e-mail de confirmation (clic « Commander » uniquement).
   *
   * Un probleme d'envoi (SMTP, etc.) ne doit jamais faire echouer la
   * confirmation d'une commande deja enregistree en base — l'erreur est
   * seulement journalisee.
   */
  private function sendOrderConfirmationEmail(Quote $quote, array $attachments): void {
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager->getStorage('user')->load($quote->getOwnerId());

    try {
      $this->mailManager->mail(
        'drivematic_configurator',
        'quote_ordered',
        $account->getEmail(),
        $account->getPreferredLangcode(),
        ['quote' => $quote, 'attachments' => $attachments],
      );
    }
    catch (\Throwable $e) {
      $this->getLogger('drivematic_configurator')->error('Échec de l’envoi de l’e-mail de confirmation de commande pour le devis @reference : @message', [
        '@reference' => $quote->get('reference')->value,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Notifie Drive Matic Legrand de la commande (clic « Commander » uniquement).
   *
   * Adresse temporaire (comme toutes les autres notifications internes du
   * site, cf. mémoire mail-interne-audrey-temporaire) — à restaurer sur
   * info@drivematiclegrand.com avant la mise en prod. Independant de
   * sendOrderConfirmationEmail() : un echec ici ne doit ni empecher l'envoi
   * au partenaire ni faire echouer la confirmation d'une commande deja
   * enregistree en base.
   */
  private function sendInternalOrderNotification(Quote $quote, array $attachments): void {
    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager->getStorage('user')->load($quote->getOwnerId());

    try {
      $this->mailManager->mail(
        'drivematic_configurator',
        'quote_ordered_internal',
        'audrey@passerelle.com',
        $account->getPreferredLangcode(),
        ['quote' => $quote, 'attachments' => $attachments],
      );
    }
    catch (\Throwable $e) {
      $this->getLogger('drivematic_configurator')->error('Échec de l’envoi de la notification interne de commande pour le devis @reference : @message', [
        '@reference' => $quote->get('reference')->value,
        '@message' => $e->getMessage(),
      ]);
    }
  }

  /**
   * Construit le message de confirmation (texte fourni par l'utilisatrice).
   */
  private function buildConfirmationMessage(string $status): Markup {
    if ($status !== Quote::STATUS_A_COMMANDER) {
      return Markup::create((string) $this->t("Votre devis a bien été enregistré mais n'est pas finalisé. Vous pouvez le retrouver dès à présent dans votre tableau de bord afin de le reprendre."));
    }

    $lines = [
      $this->t('Félicitations, votre commande a bien été enregistrée et transmise à notre équipe !'),
      $this->t('Vous allez recevoir par mail un bon de commande à signer et à nous retourner.'),
      $this->t('Pensez à surveiller votre courrier indésirable.'),
    ];

    $contact_url = $this->loadContactUrl();
    $lines[] = $contact_url
      ? $this->t("Vous n'avez pas reçu votre bon de commande ? <a href=':url'>Contactez-nous ici</a>.", [':url' => $contact_url->toString()])
      : $this->t("Vous n'avez pas reçu votre bon de commande ? Contactez-nous.");

    return Markup::create(implode('<br>', array_map('strval', $lines)));
  }

}
