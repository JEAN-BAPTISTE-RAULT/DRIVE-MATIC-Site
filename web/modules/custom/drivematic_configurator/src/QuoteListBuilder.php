<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Ecran de controle en lecture seule des devis enregistres.
 *
 * Aucune action d'edition/suppression ligne par ligne : le cycle de vie
 * (creation, changement de statut, archivage) est entierement pilote par le
 * parcours partenaire (DeliveryForm) et le cron (archivage automatique a
 * J+30) — voir la note de classe de Quote. Onglets « Mes devis »/
 * duplication/PDF (F15) restent hors perimetre.
 */
final class QuoteListBuilder extends EntityListBuilder {

  public function __construct(
    EntityTypeInterface $entity_type,
    EntityStorageInterface $storage,
    private readonly DateFormatterInterface $dateFormatter,
  ) {
    parent::__construct($entity_type, $storage);
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): static {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('date.formatter'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['reference'] = $this->t('N° de devis');
    $header['partner'] = $this->t('Partenaire');
    $header['status'] = $this->t('Statut');
    $header['total_ttc'] = $this->t('Total TTC (€)');
    $header['created'] = $this->t('Date de création');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\drivematic_configurator\Entity\Quote $entity */
    $status_labels = $entity->getFieldDefinition('status')->getSetting('allowed_values');

    $row['reference'] = $entity->label();
    $row['partner'] = $entity->getOwner()?->label() ?? '—';
    $row['status'] = $status_labels[$entity->get('status')->value] ?? $entity->get('status')->value;
    $row['total_ttc'] = $entity->get('total_ttc')->value ?? '—';
    $row['created'] = $this->dateFormatter->format((int) $entity->get('created')->value, 'short');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   *
   * Pas d'operations (edition/suppression) : voir la note de classe.
   */
  public function getDefaultOperations(EntityInterface $entity): array {
    return [];
  }

}
