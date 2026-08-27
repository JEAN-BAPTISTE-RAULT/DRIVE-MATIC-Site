<?php

declare(strict_types=1);

namespace Drupal\drivematic_catalog\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Parse et applique le combinatoire de tarifs du catalogue (F17).
 *
 * Cf. docs/plans/catalogue-tarifs-import.md pour le plan complet.
 *
 * Format attendu, fige par ce que produit le combinatoire construit avec
 * l'utilisatrice : feuille "Référentiel véhicules", en-tete sur 2 lignes
 * (groupe + sous-en-tete), donnees a partir de la ligne 3, 23 colonnes dans
 * un ordre fixe (voir self::COLUMNS). Valide un echantillon d'en-tetes avant
 * de lire quoi que ce soit : un fichier au mauvais format doit echouer tot
 * et clairement, pas produire un import partiel silencieux.
 *
 * `vehicle_brand`/`vehicle_model` sont rapprochees PAR NOM (upsert), pas
 * supprimees puis recreees : un vider-recreer litteral changerait les ID de
 * terme a chaque import et casserait les soumissions webform existantes qui
 * stockent un ID de terme (`webform_term_select`). Cf. §3 du plan pour le
 * raisonnement complet. `equipment_price`, lui, n'est reference nulle part
 * ailleurs : il est entierement vide puis recree a chaque import.
 */
final class CatalogImporter {

  /**
   * Colonne (1-based) => cle du champ parse. Ordre fige, voir note de classe.
   */
  private const COLUMNS = [
    1 => 'marque',
    2 => 'modele',
    3 => 'vor_type',
    4 => 'vor_tarif',
    5 => 'vor_ref',
    6 => 'bvm_t',
    7 => 'bvm_r',
    8 => 'bvm_c',
    9 => 'bva_t',
    10 => 'bva_r',
    11 => 'bva_c',
    12 => 'hyb_t',
    13 => 'hyb_r',
    14 => 'hyb_c',
    15 => 'ele_t',
    16 => 'ele_r',
    17 => 'ele_c',
    18 => 'retro_ext',
    19 => 'retro_ext_ref',
    20 => 'retro_int',
    21 => 'retro_int_ref',
    22 => 'statut',
    23 => 'remarque',
  ];

  /**
   * Cles de self::COLUMNS a lire pour chaque motorisation.
   *
   * Motorisation => [cle tarif, cle reference, cle chassis].
   */
  private const MOTORISATION_COLUMNS = [
    'Manuelle' => ['bvm_t', 'bvm_r', 'bvm_c'],
    'Automatique' => ['bva_t', 'bva_r', 'bva_c'],
    'Hybride' => ['hyb_t', 'hyb_r', 'hyb_c'],
    'Électrique' => ['ele_t', 'ele_r', 'ele_c'],
  ];

  private const SHEET_NAME = 'Référentiel véhicules';

  /**
   * En-tetes attendues (ligne 2), verifiees avant toute lecture.
   *
   * Positions cles seulement (pas les 23) — cf. note de classe.
   */
  private const EXPECTED_HEADERS = [
    1 => 'Marque',
    2 => 'Modèle',
    3 => 'Type de VOR',
    4 => 'Tarif VOR (€ HT)',
    18 => 'Extérieure (€ HT)',
    22 => 'Statut',
  ];

  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Lit le fichier et retourne une structure neutre (marques/modeles/tarifs).
   *
   * N'ecrit rien en base.
   *
   * @param string $realPath
   *   Chemin reel du fichier .xlsx uploade.
   *
   * @return array
   *   ['brands' => string[], 'models' => array, 'prices' => array].
   *
   * @throws \RuntimeException
   *   Si le fichier n'a pas le format attendu (feuille absente, en-tetes ne
   *   correspondant pas) ou si les tarifs de rétrovision divergent d'une
   *   ligne a l'autre (incoherence de donnees a corriger dans le fichier).
   */
  public function parse(string $realPath): array {
    $spreadsheet = IOFactory::load($realPath);
    $sheet = $spreadsheet->getSheetByName(self::SHEET_NAME);
    if (!$sheet) {
      throw new \RuntimeException(sprintf('Feuille "%s" introuvable dans ce fichier.', self::SHEET_NAME));
    }

    foreach (self::EXPECTED_HEADERS as $col => $expected) {
      $actual = trim((string) $sheet->getCell([$col, 2])->getValue());
      if ($actual !== $expected) {
        throw new \RuntimeException(sprintf(
          'En-tête inattendue en colonne %d, ligne 2 : "%s" au lieu de "%s". Le format du fichier ne correspond pas au combinatoire attendu.',
          $col, $actual, $expected,
        ));
      }
    }

    $brands = [];
    $models = [];
    $prices = [];
    $retrovision = ['retrovision_ext' => [], 'retrovision_int' => []];

    $last_row = $sheet->getHighestRow();
    for ($row = 3; $row <= $last_row; $row++) {
      $data = $this->readRow($sheet, $row);
      if ($data['marque'] === NULL || $data['modele'] === NULL) {
        continue;
      }
      $marque = trim((string) $data['marque']);
      $modele = trim((string) (is_float($data['modele']) ? rtrim(rtrim(sprintf('%.10F', $data['modele']), '0'), '.') : $data['modele']));
      if ($marque === '' || $modele === '') {
        continue;
      }

      $brands[$marque] = TRUE;

      $motorisations = [];
      foreach (self::MOTORISATION_COLUMNS as $moto_label => $columns) {
        if ($this->numeric($data[$columns[0]]) !== NULL) {
          $motorisations[] = $moto_label;
        }
      }

      // Aucune motorisation deductible = aucun tarif pedalier nulle part :
      // rien a proposer pour ce modele, on ne cree ni terme ni tarif (meme
      // logique que celle appliquee a la main dans cette session pour
      // Bigster/MG3/Auris/BZ4X/Dolphin G).
      if (!$motorisations) {
        continue;
      }

      $models[$modele] = ['brand' => $marque, 'motorisations' => $motorisations];

      $vor_tarif = $this->numeric($data['vor_tarif']);
      if ($vor_tarif !== NULL) {
        $prices[] = [
          'type' => 'telecommande_vor',
          'brand' => $marque,
          'model' => $modele,
          'tarif' => $vor_tarif,
          'reference' => $this->text($data['vor_ref']),
          'chassis' => NULL,
        ];
      }

      foreach (self::MOTORISATION_COLUMNS as $moto_label => [$tarif_key, $ref_key, $chassis_key]) {
        $tarif = $this->numeric($data[$tarif_key]);
        if ($tarif === NULL) {
          continue;
        }
        $prices[] = [
          'type' => 'pedalier',
          'brand' => $marque,
          'model' => $modele,
          'motorisation' => $moto_label,
          'tarif' => $tarif,
          'reference' => $this->text($data[$ref_key]),
          'chassis' => $this->text($data[$chassis_key]),
        ];
      }

      foreach (['retrovision_ext' => 'retro_ext', 'retrovision_int' => 'retro_int'] as $type => $key) {
        $tarif = $this->numeric($data[$key]);
        if ($tarif !== NULL) {
          $retrovision[$type][$tarif] = $this->text($data[$key . '_ref']);
        }
      }
    }

    foreach ($retrovision as $type => $values) {
      if (count($values) > 1) {
        throw new \RuntimeException(sprintf(
          'Tarifs "%s" incohérents dans le fichier : plusieurs valeurs différentes trouvées (%s). Ce tarif doit être identique sur toutes les lignes.',
          $type, implode(', ', array_keys($values)),
        ));
      }
      if ($values) {
        $tarif = array_key_first($values);
        $prices[] = [
          'type' => $type,
          'brand' => NULL,
          'model' => NULL,
          'tarif' => (float) $tarif,
          'reference' => $values[$tarif],
          'chassis' => NULL,
        ];
      }
    }

    return [
      'brands' => array_keys($brands),
      'models' => $models,
      'prices' => $prices,
    ];
  }

  /**
   * Compare la structure parsee a l'etat actuel de la base, sans rien ecrire.
   *
   * Les noms sont retournes uniquement pour les creations/suppressions (ce
   * qui apparait ou disparait reellement) : les listes conservees/mises a
   * jour peuvent atteindre 100+ entrees, sans rien changer de risque a
   * examiner avant de confirmer.
   *
   * @param array $parsed
   *   Retour de self::parse().
   *
   * @return array
   *   Comptages pour l'ecran de confirmation :
   *   marques_creees/conservees/supprimees (+ marques_creees_noms/
   *   marques_supprimees_noms), modeles_crees/mis_a_jour/supprimes (+
   *   modeles_crees_noms/modeles_supprimes_noms), lignes_tarif_a_creer,
   *   lignes_tarif_actuelles.
   */
  public function diff(array $parsed): array {
    $existing_brands = $this->loadTermNames('vehicle_brand');
    $existing_models = $this->loadTermNames('vehicle_model');

    $new_brand_names = $parsed['brands'];
    $new_model_names = array_keys($parsed['models']);

    $brands_created = array_diff($new_brand_names, array_keys($existing_brands));
    $brands_removed = array_diff(array_keys($existing_brands), $new_brand_names);
    $models_created = array_diff($new_model_names, array_keys($existing_models));
    $models_removed = array_diff(array_keys($existing_models), $new_model_names);

    $existing_price_count = (int) $this->entityTypeManager
      ->getStorage('equipment_price')
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();

    return [
      'marques_creees' => count($brands_created),
      'marques_creees_noms' => $this->sorted($brands_created),
      'marques_conservees' => count(array_intersect($new_brand_names, array_keys($existing_brands))),
      'marques_supprimees' => count($brands_removed),
      'marques_supprimees_noms' => $this->sorted($brands_removed),
      'modeles_crees' => count($models_created),
      'modeles_crees_noms' => $this->sorted($models_created),
      'modeles_mis_a_jour' => count(array_intersect($new_model_names, array_keys($existing_models))),
      'modeles_supprimes' => count($models_removed),
      'modeles_supprimes_noms' => $this->sorted($models_removed),
      'lignes_tarif_a_creer' => count($parsed['prices']),
      'lignes_tarif_actuelles' => $existing_price_count,
    ];
  }

  /**
   * Trie et reindexe un tableau de noms pour l'affichage.
   *
   * @return string[]
   *   Valeurs triees, reindexees a partir de 0.
   */
  private function sorted(array $values): array {
    sort($values);
    return $values;
  }

  /**
   * Applique reellement les changements de taxonomie (marques/modeles).
   *
   * Appelee depuis les callbacks Batch (CatalogImportBatch), jamais
   * directement depuis le formulaire.
   *
   * @param array $parsed
   *   Retour de self::parse().
   *
   * @return array
   *   Comptages effectivement appliques (meme forme que self::diff(), sans
   *   les cles *_actuelles).
   */
  public function applyTaxonomy(array $parsed): array {
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');

    $existing_brands = $this->loadTermNames('vehicle_brand');
    $brand_ids = $existing_brands;
    $created_brands = 0;
    foreach ($parsed['brands'] as $name) {
      if (!isset($brand_ids[$name])) {
        $term = $term_storage->create(['vid' => 'vehicle_brand', 'name' => $name]);
        $term->save();
        $brand_ids[$name] = (int) $term->id();
        $created_brands++;
      }
    }
    $removed_brands = array_diff(array_keys($existing_brands), $parsed['brands']);
    foreach ($removed_brands as $name) {
      $term_storage->load($existing_brands[$name])?->delete();
    }

    $motorisation_ids = $this->loadTermNames('motorisation');
    foreach (array_keys(self::MOTORISATION_COLUMNS) as $label) {
      if (!isset($motorisation_ids[$label])) {
        throw new \RuntimeException(sprintf('Terme de motorisation "%s" introuvable en taxonomie — ne devrait jamais arriver (vocabulaire posé par ADR-003).', $label));
      }
    }

    $existing_models = $this->loadTermNames('vehicle_model');
    $created_models = 0;
    $updated_models = 0;
    foreach ($parsed['models'] as $model_name => $info) {
      $moto_values = array_map(
        static fn (string $label) => ['target_id' => $motorisation_ids[$label]],
        $info['motorisations'],
      );
      if (isset($existing_models[$model_name])) {
        $term = $term_storage->load($existing_models[$model_name]);
        $updated_models++;
      }
      else {
        $term = $term_storage->create(['vid' => 'vehicle_model', 'name' => $model_name]);
        $created_models++;
      }
      $term->set('field_brand', ['target_id' => $brand_ids[$info['brand']]]);
      $term->set('field_motorisations', $moto_values);
      $term->save();
    }
    $removed_models = array_diff(array_keys($existing_models), array_keys($parsed['models']));
    foreach ($removed_models as $name) {
      $term_storage->load($existing_models[$name])?->delete();
    }

    return [
      'marques_creees' => $created_brands,
      'marques_supprimees' => count($removed_brands),
      'modeles_crees' => $created_models,
      'modeles_mis_a_jour' => $updated_models,
      'modeles_supprimes' => count($removed_models),
    ];
  }

  /**
   * Vide entierement le catalogue de tarifs (equipment_price).
   *
   * Appelee une fois, avant la creation des nouvelles lignes.
   *
   * @return int
   *   Nombre de lignes supprimees.
   */
  public function clearPrices(): int {
    $storage = $this->entityTypeManager->getStorage('equipment_price');
    $ids = $storage->getQuery()->accessCheck(FALSE)->execute();
    if ($ids) {
      $storage->delete($storage->loadMultiple($ids));
    }
    return count($ids);
  }

  /**
   * Cree les lignes de tarif pour un lot de prix parses.
   *
   * Appelee par chunks depuis le Batch, apres self::applyTaxonomy() et
   * self::clearPrices().
   *
   * @param array $price_rows
   *   Sous-ensemble de $parsed['prices'].
   *
   * @return int
   *   Nombre de lignes creees.
   */
  public function createPriceRows(array $price_rows): int {
    $model_ids = $this->loadTermNames('vehicle_model');
    $motorisation_ids = $this->loadTermNames('motorisation');
    $storage = $this->entityTypeManager->getStorage('equipment_price');

    $count = 0;
    foreach ($price_rows as $price) {
      $values = [
        'type_equipement' => $price['type'],
        'tarif_ht' => $price['tarif'],
        'reference' => $price['reference'],
        'type_chassis' => $price['chassis'],
      ];
      if ($price['model'] !== NULL) {
        if (!isset($model_ids[$price['model']])) {
          throw new \RuntimeException(sprintf('Modèle "%s" introuvable en taxonomie lors de la création des tarifs — la taxonomie aurait dû être mise à jour avant.', $price['model']));
        }
        $values['vehicle_model'] = ['target_id' => $model_ids[$price['model']]];
      }
      if (isset($price['motorisation'])) {
        $values['motorisation'] = ['target_id' => $motorisation_ids[$price['motorisation']]];
      }
      $storage->create($values)->save();
      $count++;
    }
    return $count;
  }

  /**
   * Charge les termes d'un vocabulaire, indexes par libelle.
   *
   * @return array<string,int>
   *   Nom du terme => term id, pour un vocabulaire donne.
   */
  private function loadTermNames(string $vocabulary): array {
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => $vocabulary]);
    $names = [];
    foreach ($terms as $term) {
      $names[$term->label()] = (int) $term->id();
    }
    return $names;
  }

  /**
   * Lit les 23 colonnes (self::COLUMNS) d'une ligne de donnees.
   *
   * @return array<string,mixed>
   *   Valeurs brutes des 23 colonnes pour cette ligne.
   */
  private function readRow(Worksheet $sheet, int $row): array {
    $data = [];
    foreach (self::COLUMNS as $col => $key) {
      $data[$key] = $sheet->getCell([$col, $row])->getValue();
    }
    return $data;
  }

  /**
   * Normalise une valeur de cellule en float, ou NULL si vide/non numerique.
   *
   * Une cellule vide ou un artefact de formule (chaine vide, espace,
   * booleen) n'est pas un tarif.
   */
  private function numeric(mixed $value): ?float {
    if (is_int($value) || is_float($value)) {
      return (float) $value;
    }
    if (is_string($value) && trim($value) !== '' && is_numeric(trim($value))) {
      return (float) trim($value);
    }
    return NULL;
  }

  /**
   * Normalise une valeur de cellule en chaine, ou NULL si vide.
   */
  private function text(mixed $value): ?string {
    if ($value === NULL) {
      return NULL;
    }
    $text = trim(str_replace("\xc2\xa0", ' ', (string) $value));
    return $text === '' ? NULL : $text;
  }

}
