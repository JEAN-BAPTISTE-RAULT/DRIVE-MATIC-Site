<?php

declare(strict_types=1);

namespace Drupal\drivematic_catalog\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\drivematic_catalog\Batch\CatalogImportBatch;
use Drupal\drivematic_catalog\Service\CatalogImporter;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Import du combinatoire (F17) : formulaire en 2 temps.
 *
 * 1. Upload + analyse a blanc (CatalogImporter::parse()/diff()) — rien
 *    n'est ecrit en base a cette etape.
 * 2. Ecran de confirmation affichant les comptages, avant tout declenchement
 *    du Batch qui ecrit reellement (garde-fou CLAUDE.md sur les commandes
 *    destructrices en base — cf. docs/plans/catalogue-tarifs-import.md §5).
 */
final class CatalogImportForm extends FormBase {

  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileSystemInterface $fileSystem,
    protected CatalogImporter $importer,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('drivematic_catalog.importer'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'drivematic_catalog_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    if ($form_state->get('step') === 'confirm') {
      return $this->buildConfirmStep($form, $form_state);
    }
    return $this->buildUploadStep($form, $form_state);
  }

  /**
   * Etape 1 : upload du fichier.
   */
  private function buildUploadStep(array $form, FormStateInterface $form_state): array {
    $form['intro'] = [
      '#markup' => '<p>' . $this->t('Le fichier remplace entièrement le référentiel véhicules (marques/modèles rapprochés par nom — leurs identifiants existants sont conservés) et le catalogue de tarifs (entièrement recréé). Rien ne sera écrit avant une étape de confirmation.') . '</p>',
    ];

    $form['file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Fichier combinatoire (.xlsx)'),
      '#required' => TRUE,
      '#upload_location' => 'temporary://catalog-import',
      '#upload_validators' => [
        'FileExtension' => ['extensions' => 'xlsx'],
      ],
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['analyze'] = [
      '#type' => 'submit',
      '#value' => $this->t('Analyser le fichier'),
      '#submit' => ['::analyzeSubmit'],
    ];

    return $form;
  }

  /**
   * Etape 2 : recapitulatif du diff, avant ecriture.
   */
  private function buildConfirmStep(array $form, FormStateInterface $form_state): array {
    $diff = $form_state->get('diff');

    $form['summary'] = [
      '#theme' => 'item_list',
      '#title' => $this->t('Ce qui va être appliqué'),
      '#items' => [
        $this->t('Marques : @c créées, @k conservées, @d supprimées', [
          '@c' => $diff['marques_creees'],
          '@k' => $diff['marques_conservees'],
          '@d' => $diff['marques_supprimees'],
        ]),
        $this->t('Modèles : @c créés, @u mis à jour, @d supprimés', [
          '@c' => $diff['modeles_crees'],
          '@u' => $diff['modeles_mis_a_jour'],
          '@d' => $diff['modeles_supprimes'],
        ]),
        $this->t('Catalogue de tarifs : @old lignes actuelles supprimées, @new lignes créées', [
          '@old' => $diff['lignes_tarif_actuelles'],
          '@new' => $diff['lignes_tarif_a_creer'],
        ]),
      ],
    ];

    if ($diff['marques_supprimees'] > 0 || $diff['modeles_supprimes'] > 0) {
      $form['warning'] = [
        '#markup' => '<p role="alert">' . $this->t('⚠️ Des marques/modèles absents du fichier seront supprimés de la taxonomie du site.') . '</p>',
      ];
    }

    $details = [
      'marques_creees_noms' => $this->t('Marques créées'),
      'marques_supprimees_noms' => $this->t('Marques supprimées'),
      'modeles_crees_noms' => $this->t('Modèles créés'),
      'modeles_supprimes_noms' => $this->t('Modèles supprimés'),
    ];
    foreach ($details as $key => $title) {
      if ($diff[$key]) {
        $form[$key] = [
          '#theme' => 'item_list',
          '#title' => $title,
          '#items' => $diff[$key],
        ];
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['confirm'] = [
      '#type' => 'submit',
      '#value' => $this->t("Confirmer l'import"),
      '#submit' => ['::confirmSubmit'],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Annuler'),
      '#submit' => ['::cancelSubmit'],
      '#limit_validation_errors' => [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Rien a valider a l'etape de confirmation (pas de champ de saisie).
  }

  /**
   * Soumission de l'étape 1 : parse le fichier, calcule le diff.
   *
   * Passe à l'étape de confirmation. N'écrit rien en base.
   */
  public function analyzeSubmit(array &$form, FormStateInterface $form_state): void {
    $fids = $form_state->getValue('file');
    $file = $fids ? $this->entityTypeManager->getStorage('file')->load(reset($fids)) : NULL;
    if (!$file) {
      $form_state->setErrorByName('file', $this->t('Aucun fichier reçu.'));
      return;
    }

    $real_path = $this->fileSystem->realpath($file->getFileUri());
    try {
      $parsed = $this->importer->parse($real_path);
    }
    catch (\RuntimeException $e) {
      $form_state->setErrorByName('file', $e->getMessage());
      $file->delete();
      return;
    }

    if (!$parsed['models']) {
      $form_state->setErrorByName('file', $this->t('Aucun modèle exploitable trouvé dans ce fichier (aucune ligne avec un tarif de pédalier renseigné).'));
      $file->delete();
      return;
    }

    $diff = $this->importer->diff($parsed);
    // Le fichier a livre tout ce dont l'etape de confirmation/le batch ont
    // besoin (donnees copiees dans $form_state) : plus utile sur disque.
    $file->delete();

    $form_state->set('parsed', $parsed);
    $form_state->set('diff', $diff);
    $form_state->set('step', 'confirm');
    $form_state->setRebuild(TRUE);
  }

  /**
   * Soumission de l'étape 2 (confirmation) : déclenche le batch d'écriture.
   */
  public function confirmSubmit(array &$form, FormStateInterface $form_state): void {
    $parsed = $form_state->get('parsed');
    batch_set([
      'title' => $this->t('Import du catalogue de tarifs'),
      'init_message' => $this->t('Préparation…'),
      'operations' => CatalogImportBatch::buildOperations($parsed),
      'finished' => [CatalogImportBatch::class, 'finished'],
    ]);
  }

  /**
   * Soumission d'« Annuler » : retour à l'étape 1, rien n'a été écrit.
   */
  public function cancelSubmit(array &$form, FormStateInterface $form_state): void {
    $form_state->set('step', NULL);
    $form_state->set('parsed', NULL);
    $form_state->set('diff', NULL);
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Non utilise : chaque bouton a son propre #submit.
  }

}
