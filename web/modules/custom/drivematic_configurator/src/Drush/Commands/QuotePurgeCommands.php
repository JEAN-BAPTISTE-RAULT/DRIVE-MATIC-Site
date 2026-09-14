<?php

declare(strict_types=1);

namespace Drupal\drivematic_configurator\Drush\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drush\Attributes as CLI;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;

/**
 * Purge complete des devis (entites + PDF), pour repartir d'une base vide.
 *
 * Outil de maintenance generique (pas lie a une feature precise) : sert
 * notamment a repartir sans donnees historiques avant un chantier qui change
 * les regles de gel d'un devis (ex. le moment ou `reference` est posee), pour
 * ne pas melanger des devis crees sous l'ancien comportement avec les
 * nouveaux. Supprime TOUJOURS l'integralite des devis, quel que soit leur
 * statut ou leur proprietaire — jamais un sous-ensemble filtre.
 */
final class QuotePurgeCommands extends DrushCommands {

  use AutowireTrait;

  /**
   * Entites liees a un devis, dans l'ordre de suppression (enfants d'abord).
   *
   * `quote` en dernier : les autres references le pointent
   * (`quote_id`)/pointent une entite qui le pointe (`quote_equipment_line`
   * via `quote_configuration`), meme si Drupal ne verifie aucune contrainte
   * de cle etrangere — un ordre coherent evite au minimum des lignes
   * orphelines visibles le temps que la commande s'execute.
   */
  private const RELATED_ENTITY_TYPES = [
    'quote_discount_change',
    'quote_equipment_line',
    'quote_configuration',
    'quote_status_change',
    'quote',
  ];

  private const PDF_DIRECTORY = 'private://devis-pdf';

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly FileSystemInterface $fileSystem,
  ) {
    parent::__construct();
  }

  /**
   * Supprime tous les devis, leurs entites liees et leurs PDF.
   *
   * Verifie ensuite qu'il n'en reste rien.
   */
  #[CLI\Command(name: 'drivematic:quotes-purge', aliases: ['dm-quotes-purge'])]
  #[CLI\Option(name: 'dry-run', description: "N'affiche que ce qui serait supprime, ne touche a rien.")]
  #[CLI\Usage(name: 'drush drivematic:quotes-purge --dry-run', description: 'Previsualise sans rien supprimer.')]
  #[CLI\Usage(name: 'drush drivematic:quotes-purge', description: 'Supprime, avec confirmation interactive.')]
  #[CLI\Usage(name: 'drush drivematic:quotes-purge -y', description: 'Supprime sans confirmation (scripts).')]
  public function purge(array $options = ['dry-run' => FALSE]): void {
    $counts = $this->countAll();
    $this->reportCounts($counts, $options['dry-run'] ? 'A supprimer (apercu, --dry-run)' : 'A supprimer');

    if (array_sum($counts) === 0) {
      $this->logger()->success('Rien a supprimer : la base est deja vide de tout devis.');
      return;
    }

    if ($options['dry-run']) {
      return;
    }

    if (!$this->io()->confirm('Supprimer DEFINITIVEMENT tous les devis, leurs entites liees et leurs PDF ci-dessus ? Irreversible.', FALSE)) {
      throw new UserAbortException();
    }

    foreach (self::RELATED_ENTITY_TYPES as $entity_type_id) {
      $this->deleteAll($entity_type_id);
    }
    $pdf_deleted = $this->purgePdfDirectory();

    $remaining = $this->countAll();
    $remaining['pdf'] = $this->countPdfFiles();

    if (array_sum($remaining) > 0) {
      $this->reportCounts($remaining, 'ECHEC : encore present apres suppression');
      throw new \RuntimeException('La purge est incomplete — voir le detail ci-dessus. Base de donnees potentiellement dans un etat incoherent, a investiguer avant de relancer.');
    }

    $this->logger()->success(sprintf(
      'Purge terminee : %d devis et entites liees supprimes, %d PDF supprimes. Verifie : plus aucune ligne/fichier ne subsiste.',
      array_sum(array_diff_key($counts, ['pdf' => NULL])),
      $pdf_deleted,
    ));
  }

  /**
   * Compte chaque entite liee + les PDF sur disque.
   *
   * @return array<string,int>
   *   Cle = type d'entite (ou `pdf`), valeur = nombre de lignes/fichiers.
   */
  private function countAll(): array {
    $counts = [];
    foreach (self::RELATED_ENTITY_TYPES as $entity_type_id) {
      $counts[$entity_type_id] = (int) $this->entityTypeManager
        ->getStorage($entity_type_id)
        ->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();
    }
    $counts['pdf'] = $this->countPdfFiles();

    return $counts;
  }

  /**
   * Affiche un tableau recapitulatif des compteurs.
   *
   * @param array<string,int> $counts
   *   Meme forme que self::countAll().
   * @param string $title
   *   Titre affiche au-dessus du tableau.
   */
  private function reportCounts(array $counts, string $title): void {
    $this->io()->title($title);
    $rows = [];
    foreach ($counts as $key => $count) {
      $rows[] = [$key === 'pdf' ? 'PDF (' . self::PDF_DIRECTORY . ')' : $key, $count];
    }
    $this->io()->table(['Table / dossier', 'Lignes / fichiers'], $rows);
  }

  /**
   * Supprime toutes les entites d'un type donne, par lots.
   *
   * Par lots (pas un seul `loadMultiple()` global) : eviter d'epuiser la
   * memoire si un environnement accumule un jour un tres grand nombre de
   * devis — volumetrie actuelle faible (~100 partenaires) mais le cout de ce
   * garde-fou est nul.
   */
  private function deleteAll(string $entity_type_id): void {
    $storage = $this->entityTypeManager->getStorage($entity_type_id);

    do {
      $ids = $storage->getQuery()
        ->accessCheck(FALSE)
        ->range(0, 100)
        ->execute();
      if ($ids) {
        $storage->delete($storage->loadMultiple($ids));
      }
    } while ($ids);
  }

  /**
   * Supprime tous les PDF de devis presents sur le disque.
   *
   * @return int
   *   Nombre de fichiers supprimes.
   */
  private function purgePdfDirectory(): int {
    $deleted = 0;
    foreach ($this->pdfFiles() as $realpath) {
      if (unlink($realpath)) {
        $deleted++;
      }
    }
    return $deleted;
  }

  /**
   * Compte les PDF de devis presents sur le disque.
   */
  private function countPdfFiles(): int {
    return count($this->pdfFiles());
  }

  /**
   * Liste les chemins reels des PDF de devis sur le disque.
   *
   * @return string[]
   *   Chemins reels (realpath), tableau vide si le dossier n'existe pas
   *   encore (aucun devis jamais commande sur cet environnement).
   */
  private function pdfFiles(): array {
    $realpath = $this->fileSystem->realpath(self::PDF_DIRECTORY);
    if ($realpath === FALSE || !is_dir($realpath)) {
      return [];
    }

    return glob($realpath . '/*.pdf') ?: [];
  }

}
