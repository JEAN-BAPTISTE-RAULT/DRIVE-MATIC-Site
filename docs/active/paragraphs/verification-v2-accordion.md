# Verification — Paragraphes SDC · Vague V2 (Accordéon)

> Trace d'audit fin d'implémentation. Plan : `docs/plans/paragraphs-sdc.md`. Décisions : ADR-001, ADR-007.

## Commandes executees

| Commande | Resultat | Notes |
|---|---|---|
| `npm run lint` | ✅ rc=0 | ESLint (nouveau `accordion.js` + `js-detect.js`) + Stylelint + PHPCS 108/108 |
| `npm run format:check` | ✅ rc=0 | Prettier (CSS généré des SDC exclu via `.prettierignore`) |
| `drush config:status` | ✅ propre | canonicalisé via `drush cim` puis `drush cex` |
| `npm test` | ⚠ placeholder | stratégie de test non définie (PRD) |
| Rendu navigateur (`drush runserver` + node de test) | ✅ | accordéon 3 éléments ; interactions et ARIA inspectés au DOM |

## Changements comportementaux

2 nouveaux types de paragraphe : `accordion` (Bloc, placable dans `page`) et `accordion_element` (Élément, **non placable directement**). Première **paire Bloc/Élément** de la bibliothèque : le Bloc référence ses Éléments via le storage partagé `field_elements` (ERR illimité, ADR-007). L'accordéon est **fermé par défaut sans flash** (fondation `no-js`/`js`, lib `js-detect` en `<head>`), **une seule section ouverte à la fois** (fermeture du précédent), accessible clavier ; **dégradé ouvert/lisible sans JS**. Premier behavior JS du thème (`Drupal.behaviors.driveMaticAccordion`).

## Risques identifies et mitigations

- **Contenu masqué au chargement casse l'accessibilité/SEO sans JS** → amélioration progressive : markup serveur sans `aria-expanded`/`hidden`, panneaux ouverts par défaut ; CSS gate l'état fermé sous `html.js` uniquement (vérifié : `panelDisplay=none` + `hidden=true` seulement après attach ; ouvert en repli sans JS).
- **Flash d'ouverture au chargement** → `js-detect` chargé en `<head>` (parser-blocking) bascule `html.js` avant peinture (core pose sa propre classe `js` en footer, trop tard → doublon inoffensif `js js`). Vérifié : panneaux fermés dès le 1er rendu, aucun flash.
- **IDs DOM non uniques (aria-controls/panneau)** → construits depuis l'id du paragraphe. Piège : `paragraph.id` renvoie un `FieldItemList` (concaténation → erreur) ; corrigé en `paragraph.id.0.value`.
- **Fuite des champs "prop" en double** → `field_title` masqué dans le view display (`hidden:`), lu en prop via `paragraph.field_title.value`.

## Edge cases testes

- **Ouverture** : clic → `aria-expanded=true`, panneau `hidden` retiré, `.is-open` posé, chevron pivoté. ✅
- **Fermeture du précédent** : ouvrir un 2e élément ferme le 1er (`onlyOneOpen=1` en permanence). ✅
- **ARIA** : `role="region"` + `aria-labelledby` ↔ `aria-controls` ↔ ids cohérents sur les 3 éléments. ✅
- **Clavier** : `<button>` natif ; l'activation Entrée/Espace déclenche le handler `click` (prouvé via `.click()` → toggle ; les events clavier **synthétiques** du harnais ne simulent pas l'activation native — artefact de test, pas un bug). Focus visible via `:focus-visible`. ✅
- **Champs optionnels vides** (lien/fichier absents) → aucun bloc `actions`/`download` vide (guard `block(...) is not empty`). ✅
- **Texte riche CKEditor** (gras) rendu dans le panneau. ✅

## Self-review

1. **Decision la plus difficile** : le « fermé par défaut ». La solution naïve (fermé dans le HTML) casse l'accessibilité/SEO sans JS → fondation `no-js`/`js` en header (fermé sans flash *et* lisible en repli).
2. **Alternatives rejetees** : `<details name>` natif (support/animation moins maîtrisés, style moins contrôlable) ; storage ERR dédié par Bloc (rejeté au profit du partagé `field_elements`, ADR-007, avec storage dédié réservé aux Blocs plafonnés) ; script inline en `<head>` (rejeté → lib attachée via `.libraries.yml`, conforme au garde-fou « pas d'inline »).
3. **Point de moindre confiance** : l'esthétique fine (couleur/taille du chevron, espacements) — maquettes Figma non ouvertes ; structure (props/slots, ARIA, PE) solide, style à caler à l'intégration visuelle. Doublon `js js` assumé (côté core, inoffensif).
