# ADR-003 : Référentiel véhicules

## Statut
Accepte

## Date
2026-08-12

## Contexte
Le webform contact (F10) et le futur configurateur (F14/F17) ont besoin d'un referentiel **marque / modele / motorisation**, fourni par l'utilisatrice dans `Drive_Matic_modeles.xlsx` (31 marques, 124 modeles, motorisations par modele : BVM/BVA/hybride/electrique). Il fallait une **source unique reutilisable**.

## Options considerees

### Option A : options statiques dans chaque formulaire
- Avantages : trivial.
- Inconvenients : duplication, non reutilisable, pas de cascade marque→modele→motorisation, maintenance penible.

### Option B : 3 taxonomies reutilisables, import one-off
- Avantages : source unique, reutilisable (contact + configurateur), cascade possible, maintenance au cas par cas dans le BO.
- Inconvenients : les termes sont du contenu (non versionne) → a re-seeder en prod.

## Decision
**Option B.** Trois vocabulaires :
- `vehicle_brand` (Marque) — 31 termes.
- `motorisation` (Motorisation) — 4 termes : Manuelle (BVM), Automatique (BVA), Hybride, Électrique.
- `vehicle_model` (Modele) — 124 termes + `field_brand` (→ vehicle_brand, obligatoire, simple) + `field_motorisations` (→ motorisation, obligatoire, multi).

**Import one-off** (pas de module/commande/migration, choix utilisatrice) : script Drush ponctuel a partir de l'Excel nettoye (forward-fill des marques fusionnees, trim, suppression des lignes vides / « marque seule »). Maintenance ulterieure **au cas par cas dans le BO**.

## Consequences
- **Config versionnee** (config/sync) : les 3 vocabulaires + les 2 champs (storage/field) + le form display de `vehicle_model`.
- **Contenu non versionne** : les termes (en base). ⚠️ Un **deploiement prod** necessitera de recreer les termes une fois (re-jouer le seed) — pas de re-import automatique prevu.
- **Cascade** marque→modele→motorisation disponible pour le webform contact (F10) et le configurateur.
- Piege rencontre : creer les champs **et** poser les valeurs dans le meme run Drush ne persiste pas les valeurs (definitions de champ pas encore enregistrees) → seeder en 2 temps (creer champs, `drush cr`, puis creer les termes).
