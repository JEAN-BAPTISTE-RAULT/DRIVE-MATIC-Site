<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\drivematic_configurator\Entity\Quote;
use Drupal\drivematic_configurator\Service\QuoteDraftBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Action « Modifier » du menu 3 points (« Mes devis à finaliser », ADR-052).
 *
 * Reconstruit le brouillon `PrivateTempStore` depuis le devis (meme service
 * que Dupliquer, QuoteDraftBuilder) et redirige vers l'ecran Livraison
 * (DeliveryForm), qui detecte la cle `editing_quote_id` pour resauvegarder
 * en place (QuotePersister::update()) plutot que de creer un nouveau devis.
 */
final class QuoteModifyController extends ControllerBase {

  private const TEMPSTORE_COLLECTION = 'drivematic_configurator';
  private const TEMPSTORE_DRAFT_KEY = 'draft';
  private const TEMPSTORE_EDITING_KEY = 'editing_quote_id';

  public function __construct(
    protected QuoteDraftBuilder $draftBuilder,
    protected PrivateTempStoreFactory $tempStoreFactory,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('drivematic_configurator.quote_draft_builder'),
      $container->get('tempstore.private'),
    );
  }

  /**
   * Prepare le brouillon et redirige vers l'ecran Livraison.
   *
   * `_entity_access: 'quote.update'` (route) verifie deja la propriete cote
   * serveur ; la re-verification ci-dessous suit le meme reflexe que
   * QuoteDeleteForm/QuoteDuplicateController (defense en profondeur, jamais
   * confiance au seul controle d'acces de la route pour une action qui
   * ecrit en base).
   */
  public function modify(Quote $quote): RedirectResponse {
    if ((int) $quote->getOwnerId() !== (int) $this->currentUser()->id()) {
      throw new AccessDeniedHttpException();
    }

    if ($quote->get('status')->value !== Quote::STATUS_A_FINALISER) {
      $this->messenger()->addError($this->t('Ce devis ne peut plus être modifié.'));
      return $this->redirect('drivematic_partner.my_quotes', [], ['query' => ['onglet' => 'a-finaliser']]);
    }

    $result = $this->draftBuilder->build($quote);
    if ($result['dropped'] > 0) {
      $this->messenger()->addWarning($this->buildCatalogChangeWarning());
    }

    $temp_store = $this->tempStoreFactory->get(self::TEMPSTORE_COLLECTION);
    $temp_store->set(self::TEMPSTORE_DRAFT_KEY, $result['draft']);
    $temp_store->set(self::TEMPSTORE_EDITING_KEY, $quote->id());

    return $this->redirect('drivematic_configurator.delivery');
  }

  /**
   * Message d'avertissement « configuration(s) supprimée(s) » (ADR-052).
   *
   * Duplique QuoteDuplicateController::buildCatalogChangeWarning() — meme
   * choix assume que la duplication persist()/update() de QuotePersister
   * (ADR-052) : pas de factorisation pour 2 usages.
   */
  private function buildCatalogChangeWarning(): TranslatableMarkup {
    $nodes = $this->entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'contact', 'status' => 1]);
    $contact_node = reset($nodes);
    $contact_url = $contact_node ? $contact_node->toUrl() : NULL;

    return $contact_url
      ? $this->t("Une ou plusieurs de vos configurations ont dû être supprimées en raison d'un changement du catalogue. Pour toute question, n'hésitez pas à <a href=':url'>nous contacter</a>.", [':url' => $contact_url->toString()])
      : $this->t("Une ou plusieurs de vos configurations ont dû être supprimées en raison d'un changement du catalogue. Pour toute question, n'hésitez pas à nous contacter.");
  }

}
