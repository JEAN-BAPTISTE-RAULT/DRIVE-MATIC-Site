# ADR-030 : Catalogue de tarifs (F17) — entité custom et import par rapprochement

## Statut
Accepte

## Date
2026-08-27

## Contexte
Le combinatoire Excel (marques/modèles/tarifs des 4 équipements du configurateur —
télécommande VOR, pédalier, rétrovision ext./int.) construit avec l'utilisatrice devient la
source d'autorité du référentiel véhicules ET du catalogue de tarifs. Il fallait : (1) un
stockage pour des tarifs qui varient par véhicule × motorisation (télécommande VOR, pédalier),
ce que le modèle du PRD §5 ("Produit : tarif catalogue HT" unique) ne capture pas tel quel ;
(2) un mécanisme d'import réutilisable, remplaçant les scripts Drush ponctuels non versionnés
d'ADR-003.

## Options considerees

### Stockage

**Option A — champs sur `vehicle_model`** : ajouter les colonnes du combinatoire comme champs
du bundle `vehicle_model`.
- Avantages : réutilise l'entité existante, cohérent avec le principe déjà suivi sur ce
  projet (étendre les entités cœur plutôt que créer une entité custom — ADR-026).
- Inconvenients : fige la pricing à exactement 4 motorisations nommées ; mélange donnée de
  référence (taxonomie) et donnée commerciale (tarif) ; ne loge pas les équipements non liés
  à un véhicule (rétrovision) sans structure séparée de toute façon.

**Option B — entité `equipment_price` dédiée** (retenue) : une ligne par combinaison
priceable (type d'équipement × véhicule × motorisation le cas échéant).
- Avantages : fidèle au modèle du PRD §5 ("Ligne d'équipement" référence un "Produit") ; un
  seul stockage pour les 4 équipements, qu'ils varient par véhicule ou non ; extensible sans
  nouveau champ.
- Inconvenients : premier entity type custom du projet (plus de code : classe d'entité,
  list builder).

### Import

**Option A — commande Drush** : reproductible mais réservée à un accès terminal.

**Option B — formulaire d'upload admin + Batch API** (retenue, décidée avec l'utilisatrice) :
accessible sans terminal ; Batch API plus pour le retour de progression que par nécessité de
performance (~150 lignes).

**Option C — Migrate API** : écartée, disproportionnée pour un import occasionnel (pas un flux
récurrent haute fréquence).

### Vider-recréer la taxonomie

Un vider-recréer littéral de `vehicle_brand`/`vehicle_model` (suppression totale puis
recréation) change l'ID de chaque terme à chaque import. Le webform contact (F10) stocke déjà
des soumissions dont les champs marque/modèle sont des `webform_term_select` — la valeur
enregistrée est l'ID du terme, pas son libellé. Un vider-recréer casserait donc silencieusement
les références de toute soumission existante.

**Retenu : rapprochement par nom (upsert)**, pas de suppression totale : un terme déjà présent
(même nom) est mis à jour en place (ID stable) ; un terme absent du fichier est supprimé ; un
terme nouveau est créé. Résultat perçu identique à un vider-recréer (le fichier gouverne
entièrement le contenu final) sans le risque de casser des références externes.
`equipment_price`, lui, n'est référencé nulle part ailleurs : il est entièrement vidé puis
recréé à chaque import, sans ce risque.

## Decision
Option B pour le stockage (entité `equipment_price`, module `drivematic_catalog`), formulaire
d'upload + Batch API pour l'import, rapprochement par nom (pas de suppression totale) pour la
taxonomie. Détail complet : `docs/plans/catalogue-tarifs-import.md`.

Correspondance équipements ↔ colonnes du combinatoire (confirmée avec l'utilisatrice — 4
équipements, jamais plus) :

| Équipement | Varie par | Colonnes |
|---|---|---|
| Télécommande VOR | Marque + Modèle | Type de VOR, Tarif VOR, Référence VOR |
| Pédalier | Marque + Modèle + Motorisation | Tarif/Référence/Type châssis (BVM/BVA/Hybride/Électrique) |
| Rétrovision extérieure (qté 1-2) | Rien (tarif unique) | Extérieure (€ HT), Référence extérieure |
| Rétrovision intérieure | Rien (tarif unique) | Intérieure (€ HT), Référence intérieure |

Une ligne du combinatoire sans tarif pour un équipement ne crée pas de ligne de catalogue pour
cet équipement. Un modèle sans aucune motorisation déductible (aucun tarif pédalier renseigné,
quel que soit son Statut) n'a ni terme `vehicle_model`, ni aucune ligne de tarif — `Statut` ne
gouverne que la visibilité du véhicule, pas l'existence d'un tarif.

## Consequences
- Nouveau module `drivematic_catalog` : entité `equipment_price` (aucun bundle, aucun écran
  d'édition ligne par ligne — corriger un tarif = corriger le fichier Excel et réimporter),
  service `CatalogImporter` (parse/diff/applyTaxonomy/clearPrices/createPriceRows), formulaire
  `CatalogImportForm` (upload → prévisualisation obligatoire → confirmation → Batch), routes
  admin `/admin/content/catalogue-tarifs` (liste) et `/admin/content/catalogue-tarifs/import`.
- Nouvelle dépendance `phpoffice/phpspreadsheet` (composer.json).
- Le catalogue reste **vivant** — il change à chaque import, ce n'est pas lui qui gèle les
  prix. Le gel se fera dans la future Ligne d'équipement d'un devis (F15, pas encore
  implémentée) qui copiera `tarif_ht`/`reference` à la création, sans jamais relire ce
  catalogue ensuite.
- Motorisation vocabulaire (4 termes, ADR-003) non touché par cet import : seuls
  `vehicle_brand`/`vehicle_model` sont rapprochés par nom.
- `ConfigurationForm.php` (F14) n'est pas modifié par ce chantier : le branchement des tarifs
  réels dans le configurateur reste un chantier séparé.
