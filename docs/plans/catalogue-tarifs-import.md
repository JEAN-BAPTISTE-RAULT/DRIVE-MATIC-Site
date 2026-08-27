# Plan — Catalogue de tarifs (F17) : stockage et import du combinatoire

> **Brouillon en attente de validation.** Base : combinatoire `Drive_Matic_Combinatoire.xlsx`
> (construit dans cette session à partir de `TABLEAU TARIF PUBLIC`), PRD §5 (modèle de
> données) et F17, ADR-003 (référentiel véhicules), ADR-028 (configurateur FormBase).
> Périmètre : le **stockage** des tarifs et leur **import** depuis le fichier Excel.
> Hors périmètre : le calcul de devis, les entités Devis/Configuration/Ligne d'équipement
> (F15, pas encore commencées), le branchement des prix réels dans `ConfigurationForm.php`
> (F14 écran 2 « Devis », pas encore commencé).

## 1. Intention

Remplacer les 3 sources actuelles du référentiel véhicules/tarifs — le fichier Excel sur le
Bureau, la taxonomie en base (créée par scripts Drush ponctuels non versionnés), et rien du
tout côté tarifs — par un mécanisme unique et rejouable : un écran d'admin qui importe le
combinatoire Excel, vide et recrée le référentiel véhicules ET le catalogue de tarifs des 4
équipements du configurateur (télécommande VOR, pédalier, rétrovision extérieure,
rétrovision intérieure).

## 2. Correspondance équipements ↔ colonnes du combinatoire

Confirmé avec l'utilisatrice — toujours 4 équipements, pas plus :

| Équipement | Varie par | Colonnes source |
|---|---|---|
| Télécommande VOR | Marque + Modèle | Type de VOR, Tarif VOR, Référence VOR |
| Pédalier | Marque + Modèle + Motorisation | Tarif/Référence/Type châssis (BVM/BVA/Hybride/Électrique — une seule colonne s'applique une fois la motorisation choisie) |
| Rétrovision extérieure (qté 1-2) | Rien (tarif unique) | Extérieure (€ HT), Référence extérieure |
| Rétrovision intérieure | Rien (tarif unique) | Intérieure (€ HT), Référence intérieure |

Une ligne du combinatoire sans tarif pour un équipement donné (ex. Statut "À publier" mais
colonnes pédalier vides — Bigster, MG3, Auris, BZ4X, Dolphin G) ne crée **pas** de ligne de
catalogue pour cet équipement : absence de tarif = rien à proposer, indépendamment du Statut
(qui ne gouverne que la visibilité du véhicule lui-même).

## 3. ⚠️ Le "vider et recréer" littéral casserait les données existantes

Le webform contact (F10) stocke déjà, en production potentielle, des soumissions dont les
champs marque/modèle sont des `webform_term_select` — la valeur enregistrée est l'**ID** du
terme, pas son libellé. Un vider-recréer strict (suppression totale des termes puis recréation)
change les ID à chaque import : toute soumission existante référençant l'ancien ID pointerait
dans le vide après le premier réimport.

**Ajustement proposé** (fonctionnellement identique à "le fichier fait foi", mais sans casser
l'historique) : l'import fait un **rapprochement par nom** plutôt qu'une suppression totale —
- un terme déjà présent (même marque, même modèle) est **mis à jour en place** (ID stable) ;
- un terme absent du fichier est **supprimé** (comme un vider-recréer) ;
- un terme nouveau dans le fichier est **créé**.

Résultat perçu identique à un vider-recréer (le fichier gouverne entièrement le contenu final),
sans le risque de corrompre des références existantes. Je le fais remonter explicitement car
ça s'écarte de la formulation littérale — dis-moi si tu préfères vraiment une suppression
totale malgré le risque.

## 4. Stockage : nouvelle entité `equipment_price`

Entité de contenu custom (premier entity type custom du projet — justifié : ni un type de
contenu (pas de page publique), ni un terme de taxonomie (une ligne = une combinaison
tarifée, pas un terme de vocabulaire)), module `drivematic_catalog`.

Champs de base :
- `type_equipement` (liste : `telecommande_vor` / `pedalier` / `retrovision_ext` / `retrovision_int`)
- `vehicle_model` (référence vers `taxonomy_term` bundle `vehicle_model` — vide pour les 2 rétrovisions)
- `motorisation` (référence vers `taxonomy_term` bundle `motorisation` — uniquement pour `pedalier`)
- `tarif_ht` (decimal)
- `reference` (texte, nullable — vide aujourd'hui pour VOR et rétrovision, faute de donnée source)
- `type_chassis` (texte, nullable — `pedalier` uniquement)

**Sur le gel des prix** (ta préoccupation principale) : ce catalogue reste **vivant** — il
change à chaque réimport, c'est voulu. Le gel n'a pas lieu ici. Il aura lieu dans la future
**Ligne d'équipement** d'un devis (F15, hors périmètre de ce plan) qui **copiera** `tarif_ht`
et `reference` au moment de la création du devis, sans jamais relire le catalogue ensuite —
exactement le schéma déjà décrit au PRD §5 ("Ligne d'équipement : tarif catalogue HT, tarif
remise HT"). Ce plan pose un stockage compatible avec ce futur mécanisme, sans l'implémenter.

## 5. Import : formulaire d'upload + Batch API

- Route admin (permission dédiée `administer catalog import`, jamais accordée au rôle
  `partenaire`) : upload du fichier `.xlsx`.
- **Étape de prévisualisation obligatoire avant toute écriture** (garde-fou CLAUDE.md sur les
  commandes destructrices en base) : le fichier est d'abord analysé à blanc, affichant un
  résumé (« X marques ajoutées, Y supprimées, Z modèles mis à jour, N lignes de tarif ») ;
  rien n'est écrit avant confirmation explicite.
- Traitement effectif (suppression/mise à jour/création des termes + des `equipment_price`)
  via Batch API après confirmation — ~150 lignes, largement sous tout risque de timeout, mais
  Batch API donne un retour de progression et isole les erreurs ligne par ligne.
- Écran de liste en lecture seule des `equipment_price` après import (contrôle visuel) —
  pas d'écran d'édition ligne par ligne : corriger = corriger le fichier Excel et réimporter,
  cohérent avec "le fichier fait foi".

## 6. Fichiers impactés

**Nouveau module `drivematic_catalog`**
- `drivematic_catalog.info.yml`
- `drivematic_catalog.permissions.yml` — permission `administer catalog import`
- `drivematic_catalog.routing.yml` — route de l'écran d'import (+ liste en lecture seule)
- `drivematic_catalog.links.menu.yml` — entrée menu admin
- `src/Entity/EquipmentPrice.php` — entité de contenu, `baseFieldDefinitions()`
- `src/EquipmentPriceListBuilder.php` — écran de liste en lecture seule
- `src/Service/CatalogImporter.php` — parsing xlsx (PhpSpreadsheet) + diff à blanc +
  application (rapprochement par nom, §3)
- `src/Form/CatalogImportForm.php` — upload, prévisualisation, déclenchement du batch
- `src/Batch/CatalogImportBatch.php` — callbacks Batch API

**Dépendance**
- `composer.json` — ajout de `phpoffice/phpspreadsheet` (lecture xlsx serveur)

**Doc**
- `.claude/decisions/030-catalogue-tarifs-import.md` — nouvel ADR (entité custom,
  rapprochement par nom plutôt que suppression totale, choix Batch API)
- `docs/content-model.md` — nouvelle entité `equipment_price`
- `docs/PRD.md` — F17 : modalités d'import tranchées (était `[A PRECISER]`)
- `README.md` — route d'import, format attendu du fichier

## 7. Interfaces publiques

- Route `/admin/.../catalogue/import` (upload + prévisualisation)
- Route `/admin/.../catalogue` (liste en lecture seule)
- `CatalogImporter::preview(string $filePath): ImportDiff` — analyse à blanc
- `CatalogImporter::apply(ImportDiff $diff): void` — écriture (appelée depuis les callbacks Batch)
- Entité `equipment_price` : CRUD standard via `EntityTypeManager`

Aucune interface publique front (JS/Twig) modifiée à ce stade — `ConfigurationForm.php`
n'est pas touché par ce plan (branchement des vrais tarifs = chantier séparé, une fois cette
brique posée).

## 8. Sécurité

- Import réservé à une permission admin dédiée, jamais accordée au rôle `partenaire` —
  re-vérifiée côté serveur sur la route (`_permission`, pas un simple masquage de lien).
- Upload de fichier : validation d'extension (`.xlsx` uniquement) côté serveur (pas seulement
  l'attribut `accept` du champ file), taille plafonnée, passage systématique par l'API fichier
  de Drupal (jamais de chemin construit à la main à partir d'un nom de fichier utilisateur).
- Écriture en base exclusivement via l'API entité (`EquipmentPrice::create()`,
  `TermStorage::create()`) — aucune requête SQL construite par concaténation.
- Aucune donnée partenaire impliquée dans ce chantier.

## 9. Risques et contraintes techniques

- **Suppression/recréation de taxonomie** : voir §3 — rapprochement par nom retenu plutôt que
  suppression totale, pour ne pas casser les soumissions webform existantes qui référencent
  des term ID.
- **Cache** : aucun contenu public ne lit encore ce catalogue (F14 n'est pas branché dessus) —
  pas de cache tag à gérer dans ce plan ; à prévoir quand le catalogue alimentera
  `ConfigurationForm.php`.
- **Import destructif** : prévisualisation obligatoire avant écriture (§5), conforme au
  garde-fou CLAUDE.md sur les commandes destructrices en base.
- **Volumétrie** : ~150 lignes, Batch API par prudence/retour utilisateur plus que par
  nécessité de performance.
- **i18n** : les 4 valeurs de `type_equipement` sont des clés machine ; leurs libellés
  affichés passent par `t()`.

## 10. Cohérence avec les spécifications

Aligné avec F17 (référentiel + catalogue de tarifs) et le modèle de données PRD §5
("Ligne d'équipement" porte son propre tarif copié — voir §4). Ne contredit aucune décision
verrouillée (§3 du PRD) : pas de paiement en ligne, pas de Commerce.

## 11. Plan d'implémentation (étapes vérifiables)

1. `composer require phpoffice/phpspreadsheet` → vérifier `composer install` propre.
2. Scaffold `drivematic_catalog` + entité `EquipmentPrice` (sans logique d'import) →
   `drush cr`, vérifier via `drush php:eval` que le schéma de la table est installé.
3. `CatalogImporter::preview()` : parse le combinatoire, calcule le diff **sans rien écrire**
   → vérifier manuellement sur le vrai fichier (comparer les comptages à ce qu'on connaît :
   29 marques, 138 modèles publiés, ~4 lignes de tarif par modèle selon motorisations
   disponibles).
4. `CatalogImportForm` (upload + écran de prévisualisation, pas encore de bouton de
   confirmation actif) → vérifier l'affichage des comptages sur un vrai upload.
5. `CatalogImporter::apply()` + callbacks Batch, bouton de confirmation actif → lancer
   l'import réel, vérifier en base (comptages + spot-check de quelques valeurs connues,
   ex. Peugeot 208 pédalier BVA = 1084 € réf V2S021220-1).
6. Écran de liste en lecture seule.
7. ADR + mise à jour doc (content-model.md, PRD F17, README).

## 12. Stratégie de test et boucle de feedback

- Pas de suite de tests automatisés définie sur ce projet (CLAUDE.md) → vérification manuelle
  à chaque étape : `drush cr`, navigation admin, `drush php:eval` pour inspecter les données
  écrites (même méthode que celle utilisée dans cette session pour vérifier les créations de
  taxonomie).
- Cas d'erreur à tester explicitement : fichier non-xlsx, onglet "Référentiel véhicules"
  absent/renommé, ligne marque vide, motorisation hors des 4 valeurs connues, tentative
  d'accès à la route d'import par un compte partenaire (doit être refusée côté serveur).
- `npm run lint` (PHPCS inclus) sur tout le code PHP/JS ajouté avant de considérer une étape
  terminée.
