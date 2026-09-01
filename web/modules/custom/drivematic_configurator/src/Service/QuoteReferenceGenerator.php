<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Service;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Lock\LockBackendInterface;

/**
 * Genere la reference d'un devis : `WAAAAMMJJ-001` (PRD F15).
 *
 * Compteur journalier remis a zero chaque jour — verrouille (LockBackend)
 * pour rester correct meme si deux commandes arrivent au meme instant
 * (volumetrie faible, ~100 partenaires, mais le cas doit etre gere).
 */
final class QuoteReferenceGenerator {

  private const LOCK_NAME = 'drivematic_configurator_quote_reference';

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly LockBackendInterface $lock,
    private readonly TimeInterface $time,
  ) {}

  /**
   * Genere la prochaine reference disponible pour aujourd'hui.
   *
   * @return string
   *   Reference au format `WAAAAMMJJ-001`.
   */
  public function generate(): string {
    while (!$this->lock->acquire(self::LOCK_NAME)) {
      $this->lock->wait(self::LOCK_NAME);
    }

    try {
      $day_prefix = 'W' . date('Ymd', $this->time->getCurrentTime());
      $storage = $this->entityTypeManager->getStorage('quote');
      $count = (int) $storage->getQuery()
        ->accessCheck(FALSE)
        ->condition('reference', $day_prefix . '-%', 'LIKE')
        ->count()
        ->execute();

      return sprintf('%s-%03d', $day_prefix, $count + 1);
    }
    finally {
      $this->lock->release(self::LOCK_NAME);
    }
  }

}
