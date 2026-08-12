# Plan — Référentiel véhicules

> Référentiel réutilisable (3 taxonomies) importé une fois depuis `Drive_Matic_modeles.xlsx`, partagé webform contact + configurateur.

## 1. Modèle de données
- `vehicle_brand` (Marque) — 31 termes.
- `motorisation` (Motorisation) — 4 termes : manuelle (BVM), automatique (BVA), hybride, électrique.
- `vehicle_model` (Modèle) — 124 termes + `field_brand` (→ vehicle_brand, obligatoire, simple) + `field_motorisations` (→ motorisation, obligatoire, multi).

## 2. Config vs contenu
- **Config versionnée** (config/sync) : vocabulaires + champs (storage/field) + form display du terme `vehicle_model`.
- **Contenu (en base, non versionné)** : les termes. Créés **une fois** par un script ponctuel.

## 3. Approche d'import (décidée)
Pas de module/commande d'import ni de migration. **Création one-off** des termes via un script Drush ponctuel (`drush php:script`, non conservé comme outillage). Maintenance ultérieure **au cas par cas dans le BO**.
> ⚠️ Les termes étant du contenu, un **déploiement prod** nécessitera de les recréer là-bas (re-jouer le script une fois). Noté dans l'ADR-003.

## 4. Nettoyage Excel → données
Forward-fill des marques (cellules fusionnées), suppression lignes vides + lignes « marque seule », trim des espaces (`Q3 `, `C3 APRES 2024`), motorisations = colonnes `X` (BVM/BVA/HYBRIDE/ELECTRIQUE).

## 5. Sécurité / idempotence
Serveur-only (Drush) ; Entity API (pas de SQL) ; idempotent (upsert par nom+marque pour les modèles). Données publiques, aucun enjeu sensible.

## 6. Cohérence
Aligné webform-contact (cascade marque→modèle→motorisation). Nouveau référentiel partagé → **ADR-003**. Aucune décision verrouillée impactée.

## 7. Vérification
31 marques / 124 modèles / 4 motorisations ; relations correctes (échantillon) ; `npm run lint` + hook verts ; config exportée propre.

## Statut
- [x] Plan validé (import = one-off, maintenance BO)
- [x] Vocabulaires + champs + form display (config exportée)
- [x] Seed des termes (31 marques / 124 modèles / 4 motorisations, relations vérifiées)
- [x] ADR-003
- [ ] Commit
