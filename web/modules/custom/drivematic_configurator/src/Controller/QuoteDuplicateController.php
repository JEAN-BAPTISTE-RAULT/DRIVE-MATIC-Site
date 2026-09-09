<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\drivematic_configurator\Entity\DeliveryAddress;
use Drupal\drivematic_configurator\Entity\Quote;
use Drupal\drivematic_configurator\Service\QuoteDraftBuilder;
use Drupal\drivematic_configurator\Service\QuotePersister;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Action « Dupliquer » du menu 3 points (« Mes devis à finaliser », ADR-052).
 *
 * Action en un clic (lien CSRF-protege, `_csrf_token: 'TRUE'`, pas de
 * confirmation — contrairement a Supprimer) : recree un nouveau devis
 * « à finaliser » avec le meme contenu de configuration que le devis source,
 * recalcule au tarif catalogue/a la remise partenaire du jour (ADR-052,
 * precise ADR-043 : un devis « à finaliser » n'est pas encore fige).
 */
final class QuoteDuplicateController extends ControllerBase {

  public function __construct(
    protected QuoteDraftBuilder $draftBuilder,
    protected QuotePersister $quotePersister,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('drivematic_configurator.quote_draft_builder'),
      $container->get('drivematic_configurator.quote_persister'),
    );
  }

  /**
   * Duplique le devis et redirige vers l'onglet « à finaliser ».
   *
   * `_entity_access: 'quote.view'` (route) verifie deja la propriete cote
   * serveur ; la re-verification ci-dessous suit le meme reflexe que
   * QuoteDeleteForm (defense en profondeur, jamais confiance au seul
   * controle d'acces de la route pour une action qui ecrit en base).
   */
  public function duplicate(Quote $quote): RedirectResponse {
    if ((int) $quote->getOwnerId() !== (int) $this->currentUser()->id()) {
      throw new AccessDeniedHttpException();
    }

    $result = $this->draftBuilder->build($quote);
    if (!$result['draft']) {
      $this->messenger()->addError($this->t("Impossible de dupliquer ce devis : aucune de ses configurations n'est plus disponible au catalogue."));
      return $this->redirectToQuotes();
    }

    if ($result['dropped'] > 0) {
      $this->messenger()->addWarning($this->buildCatalogChangeWarning());
    }

    /** @var \Drupal\user\UserInterface $account */
    $account = $this->entityTypeManager()->getStorage('user')->load($this->currentUser()->id());

    $this->quotePersister->persist($result['draft'], Quote::STATUS_A_FINALISER, $account, $this->resolveDeliveryAddress($quote));
    $this->messenger()->addStatus($this->t('Le devis a été dupliqué.'));

    return $this->redirectToQuotes();
  }

  /**
   * Resout l'adresse de livraison a preselectionner (ADR-052).
   *
   * `delivery_address_id` si l'entite existe encore, sinon repli sur la
   * 1re adresse du partenaire (meme repli que DeliveryForm::
   * buildAddressSelector() pour un devis anterieur a ce champ) — un
   * partenaire proprietaire d'au moins un devis est necessairement deja
   * passe par l'ecran Livraison, qui amorce toujours au moins une adresse
   * (DeliveryForm::ensureAtLeastOneAddress()) : au moins une adresse existe
   * donc toujours ici.
   */
  private function resolveDeliveryAddress(Quote $quote): DeliveryAddress {
    $referenced = $quote->get('delivery_address_id')->entity;
    if ($referenced) {
      return $referenced;
    }

    $storage = $this->entityTypeManager()->getStorage('delivery_address');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('uid', $quote->getOwnerId())
      ->sort('id', 'ASC')
      ->range(0, 1)
      ->execute();

    return $storage->load(reset($ids));
  }

  /**
   * Message d'avertissement « configuration(s) supprimée(s) » (ADR-052).
   *
   * Lien vers /contact resolu par node (meme pattern que DeliveryForm::
   * loadContactUrl()) : omis avec degradation gracieuse si la page de
   * contact n'existe pas/plus.
   */
  private function buildCatalogChangeWarning(): TranslatableMarkup {
    $nodes = $this->entityTypeManager()->getStorage('node')->loadByProperties(['type' => 'contact', 'status' => 1]);
    $contact_node = reset($nodes);
    $contact_url = $contact_node ? $contact_node->toUrl() : NULL;

    return $contact_url
      ? $this->t("Une ou plusieurs de vos configurations ont dû être supprimées en raison d'un changement du catalogue. Pour toute question, n'hésitez pas à <a href=':url'>nous contacter</a>.", [':url' => $contact_url->toString()])
      : $this->t("Une ou plusieurs de vos configurations ont dû être supprimées en raison d'un changement du catalogue. Pour toute question, n'hésitez pas à nous contacter.");
  }

  /**
   * Redirige vers l'onglet « à finaliser » de « Mes devis ».
   */
  private function redirectToQuotes(): RedirectResponse {
    return $this->redirect('drivematic_partner.my_quotes', [], ['query' => ['onglet' => 'a-finaliser']]);
  }

}
