<?php

declare(strict_types=1);

namespace Drupal\drivematic_home;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Pointe la page d'accueil sur l'unique node « homepage » publie.
 *
 * Surcharge `system.site:page.front` a l'execution, sans ID de node en dur ni
 * ecriture en base : la valeur configuree reste `/node` (portable), tandis que
 * le node d'accueil — recree au seed avec n'importe quel ID — est resolu
 * dynamiquement. Les surcharges ne sont jamais exportees par `drush cex`, donc
 * `config/sync` ne subit aucune derive.
 */
final class FrontPageOverride implements ConfigFactoryOverrideInterface {

  /**
   * ID du node d'accueil, mis en cache pour la duree de la requete.
   *
   * FALSE = pas encore resolu ; NULL = aucun node d'accueil ; int = ID trouve.
   */
  private int|null|false $homepageNid = FALSE;

  /**
   * Construit le service de surcharge.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Le gestionnaire de types d'entite.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (in_array('system.site', $names, TRUE)) {
      $nid = $this->getHomepageNid();
      if ($nid !== NULL) {
        $overrides['system.site']['page']['front'] = '/node/' . $nid;
      }
    }
    return $overrides;
  }

  /**
   * Retourne l'ID du premier node « homepage » publie, ou NULL.
   *
   * @return int|null
   *   L'ID du node d'accueil, ou NULL si aucun (ou systeme d'entites
   *   indisponible, p. ex. pendant l'installation).
   */
  private function getHomepageNid(): ?int {
    if ($this->homepageNid !== FALSE) {
      return $this->homepageNid;
    }
    $this->homepageNid = NULL;
    try {
      $ids = $this->entityTypeManager->getStorage('node')->getQuery()
        ->accessCheck(FALSE)
        ->condition('type', 'homepage')
        ->condition('status', 1)
        ->sort('nid')
        ->range(0, 1)
        ->execute();
      if ($ids) {
        $this->homepageNid = (int) reset($ids);
      }
    }
    catch (\Exception $e) {
      // Systeme d'entites indisponible : on retombe sur la valeur configuree.
    }
    return $this->homepageNid;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheSuffix() {
    return 'drivematic_home.front';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata($name) {
    $metadata = new CacheableMetadata();
    if ($name === 'system.site') {
      // Recalculer si un node d'accueil est cree, modifie ou supprime.
      $metadata->addCacheTags(['node_list:homepage']);
    }
    return $metadata;
  }

  /**
   * {@inheritdoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

}
