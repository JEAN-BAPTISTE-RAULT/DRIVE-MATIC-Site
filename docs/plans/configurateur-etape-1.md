# Plan — Configurateur de devis, écran 1 « Configuration » (F14)

> Plan d'implementation. Base : PRD F14 (§ Configurateur, 3 étapes) + maquettes Figma
> (fileKey `ZmmVBSOWSsHVkok6EU2Ays`, node 493-16990 desktop, 606-36813 mobile, 508-13222
> « Ajouter configuration »). Périmètre de ce plan : **le premier écran uniquement**
> (« Configuration ») — pas les étapes Devis ni Livraison, pas le calcul de tarif,
> pas le catalogue produits (F17, pas encore implémenté).

## 1. Intention

Construire l'écran « Configuration » du configurateur de devis : un formulaire réservé
aux partenaires authentifiés, permettant de sélectionner un véhicule (cascade
marque → modèle → motorisation via les taxonomies existantes), cocher des équipements
(quantité conditionnelle pour la rétrovision extérieure), indiquer un nombre de
véhicules identiques, et dupliquer ce bloc via « Ajouter une configuration »
(jusqu'à 10, suppression possible à partir du 2ᵉ bloc).

## 2. Fichiers impactés

**Nouveau module `drivematic_configurator`**
- `web/modules/custom/drivematic_configurator/drivematic_configurator.info.yml`
- `web/modules/custom/drivematic_configurator/drivematic_configurator.routing.yml` —
  route `/configurer`, `_role: 'partenaire'`
- `web/modules/custom/drivematic_configurator/src/Form/ConfigurationForm.php` —
  FormBase, pattern Drupal « Add more » (#ajax) pour les blocs répétables
- `web/modules/custom/drivematic_configurator/drivematic_configurator.libraries.yml` —
  lib JS du stepper quantité

**Généralisation du module existant `drivematic_forms`**
- `web/modules/custom/drivematic_forms/js/vehicle-select.js` — ciblage par attribut
  `data-vehicle-cascade`/`data-vehicle-role` au lieu de `name="marque"` (cascades
  multiples indépendantes sur une même page)
- `web/modules/custom/drivematic_forms/drivematic_forms.module` — réutilisation de
  `_drivematic_forms_vehicle_map()` par le nouveau module
- `config/sync/webform.webform.contact.yml` — attributs `data-vehicle-cascade`/
  `data-vehicle-role` sur le fieldset `demande` + les 3 selects existants
  (non-régression à vérifier manuellement)

**Nettoyage `/configurer`**
- Suppression du node placeholder `configurator` existant et de son `path_alias`

**SCSS**
- `src/scss/_forms.scss` — extension du sélecteur de fondation pour couvrir le
  nouveau formulaire (classe `.webform-submission-form` réutilisée, cf. §5)
- Nouveau partiel si besoin de styles propres au configurateur (grille des 3
  blocs, carte grise, stepper)

**Assets**
- `web/themes/custom/drive_matic/images/icons/minus.svg` (nouveau, export Figma)
- `web/themes/custom/drive_matic/images/icons/plus-circle.svg` (nouveau, export
  Figma, bouton « Ajouter une configuration »)
- `web/themes/custom/drive_matic/images/icons/trash.svg` (nouveau, pas dans la
  maquette : créé dans le style des icônes existantes — trait `stroke-width="2"`,
  `round`/`round`, pas de remplissage — bouton de suppression à partir du 2ᵉ bloc)
- `plus.svg` (existant) réutilisé pour le stepper

**Doc**
- `.claude/decisions/0XX-configurateur-formbase-vs-webform.md` (nouvel ADR)
- `docs/content-model.md` — ajout des 3 taxonomies véhicule (dérive doc préexistante)
- `docs/E2E_SCENARIOS.md` — nouveau parcours (lors du `/sync`)

## 3. Interfaces publiques

- Nouvelle route `drivematic_configurator.configuration` (`/configurer`)
- Contrat JS généralisé : `data-vehicle-cascade` (conteneur) / `data-vehicle-role`
  (`brand|model|motorisation`) — remplace le contrat implicite `name="marque"` etc.
- `_drivematic_forms_vehicle_map()` appelée depuis `drivematic_configurator`
- Pas de nouveau global JS/export à déclarer dans la config du linter (IIFE +
  `Drupal.behaviors`, comme l'existant)

## 4. Sécurité

- Route `/configurer` avec `_role: 'partenaire'`, revérifiée côté serveur — corrige
  le gap déjà noté dans le PRD (absence de `_custom_access` actuellement).
- Cap serveur à 10 configurations (pas seulement un bouton caché en JS).
- Quantités validées côté serveur (`#min`/`#max` Form API), pas seulement via `#states`.
- Sélections véhicule contraintes aux term IDs valides via `#options` serveur.
- Aucune donnée partenaire sensible affichée à ce stade (pas de tarif/remise ici).

## 5. Risques, contraintes techniques et décisions actées

- **FormBase custom** (décidé) plutôt que Webform composite — aligné sur le modèle
  de données cible (entités Devis/Configuration à venir en F14-F17).
- **`/configurer` réutilisé** (décidé) — suppression du node placeholder + de son
  alias, route statique du nouveau module prend le relais.
- **Réutilisation de `.webform-submission-form`** : ADR-015 anticipait explicitement
  « configurateur à venir » comme consommateur de cette fondation. Classe posée sur
  le formulaire custom (bien que non produit par le module Webform), documentée en
  commentaire + dans le nouvel ADR pour éviter toute confusion future.
- **Cascade véhicule généralisée** : passage à un ciblage par wrapper + rôle,
  nécessaire pour N blocs de configuration indépendants. Seul autre consommateur :
  webform contact — à re-tester manuellement après le changement.
- **Icône poubelle hors maquette** : créée dans le style existant (cf. §2), pas
  redessinée en CSS.
- **Pas de catalogue produit (F17)** : les 4 équipements sont codés en dur (aucune
  entité catalogue n'existe encore) — conforme au périmètre demandé.
- **Accessibilité** : cascade dégradée sans JS (listes complètes) ; stepper avec
  `<button type="button">` ; `#states` natif Drupal pour la révélation conditionnelle
  de la quantité rétrovision (pas de JS custom nécessaire pour ce point).

## 6. Cohérence avec les specs

Aligné avec PRD F14 étape 1 et le référentiel véhicule ADR-003. Aucune décision
verrouillée contredite.

## 7. Plan d'implémentation

```
1. Créer le module drivematic_configurator (info.yml, routing.yml, form squelette)
   → vérifier : /configurer répond 200 en partenaire, 403 en anonyme/autre rôle

2. Généraliser vehicle-select.js (data-attributes) + adapter webform.webform.contact.yml
   → vérifier : cascade marque→modèle→motorisation toujours fonctionnelle sur
     /nous-contacter (avec et sans JS)

3. Construire ConfigurationForm : bloc 1 statique (véhicule + équipements + quantités)
   → vérifier : rendu conforme à la maquette desktop, cascade active, quantité
     rétrovision révélée par #states

4. Ajouter le stepper quantité (JS + markup #field_prefix/#field_suffix)
   → vérifier : +/- fonctionne, bornes respectées (1-2 rétrovision, min 1 sans max
     pour véhicules identiques)

5. Ajouter le pattern « Add more » + suppression
   → « Ajouter une configuration » (#ajax, cap 10)
   → bouton poubelle (icône seule, aria-label) à partir du 2ᵉ bloc, #ajax dédié qui
     retire le bloc et renumérote les titres « Configuration N » restants
   → vérifier : jamais de bouton sur le bloc 1, suppression au milieu renumérote
     correctement, impossible de descendre sous 1 configuration

6. Habillage SCSS (grille, checkbox, stepper, carte grise, boutons) + assets icônes
   → vérifier : npm run css puis contrôle du .css généré ; comparaison desktop/mobile
     aux maquettes (mesures, pas à l'œil)

7. Nettoyage /configurer (suppression node placeholder + alias périmé)
   → vérifier : plus de doublon d'URL, pas de lien mort vers l'ancien contenu

8. npm run lint / format:check / composer lint, self-review, ADR
```

## 8. Stratégie de test

Pas de suite de tests automatisés en place (placeholder PRD) → vérification manuelle
systématique après chaque étape, navigateur + `drush cr` après changement de
routing/libraries. Boucle rapide : rechargement navigateur, `npm run css` pour le SCSS.

Cas d'erreur à tester en plus du happy path :
- anonyme sur `/configurer` → 403/redirection login
- 11ᵉ configuration → bloquée
- quantité rétrovision à 0 ou 3 → rejetée serveur
- soumission sans marque sélectionnée → modèle/motorisation restent vides
- suppression d'un bloc ajouté, y compris le dernier de la liste
