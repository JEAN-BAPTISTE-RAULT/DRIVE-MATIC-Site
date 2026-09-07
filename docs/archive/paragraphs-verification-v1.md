# Verification — Paragraphes SDC · Vague V1 (Textes & médias)

> Trace d'audit fin d'implémentation. Plan : `docs/plans/paragraphs-sdc.md`. Décisions : ADR-001, ADR-004, ADR-006.

## Commandes executees

| Commande | Resultat | Notes |
|---|---|---|
| `npm run lint` | ✅ rc=0 | ESLint + Stylelint + PHPCS (dont le nouveau `video-centered.js`) |
| `npm run format:check` | ✅ rc=0 | Prettier (CSS généré des SDC exclu via `.prettierignore`) |
| `drush config:status` | ✅ propre | canonicalisé via `drush cim` puis `drush cex` |
| `npm test` | ⚠ placeholder | stratégie de test non définie (PRD) |
| Rendu navigateur (`drush runserver` + node de test) | ✅ | 6 paragraphes rendus ; façade vidéo cliquée |

## Changements comportementaux

6 nouveaux types de paragraphe placables dans le type de contenu `page` : `text_left_aligned`, `image_text_50` (options texte G/D + fond gris/blanc), `image_text_100`, `image_centered`, `image_full` (bannière 12:5, titre/CTA superposés), `video_centered` (façade). Images en `<picture>` responsive WebP au ratio du bloc. Vidéo : la ressource tierce n'est chargée qu'au clic.

## Risques identifies et mitigations

- **Injection d'iframe vidéo** → allowlist providers (`youtube`/`vimeo`) re-vérifiée serveur : URL Dailymotion rejetée à la validation (testé).
- **Chargement tiers avant consentement (RGPD/LCP)** → façade `<template>` inerte, iframe injectée au clic uniquement (vérifié en navigateur : `iframeInLiveDOMBefore=false`, `…After=true`).
- **Fuite des champs "prop" en double** → champs scalaires masqués dans le view display (vérifié : pas de valeur brute dans le HTML).
- **Conflit Sass↔Prettier sur le CSS généré** → CSS des SDC ignoré par Prettier (source `.scss` toujours vérifiée).

## Edge cases testes

- Champ optionnel vide (fichier/lien) → aucun markup vide cassé (F1). ✅
- Option `text_position=right` → `flex-direction: row-reverse` (image à droite en desktop, ordre source préservé en mobile). ✅
- `background=grey` → modificateur `--bg-grey` appliqué. ✅
- Vidéo provider non autorisé (Dailymotion) → violation de validation ; YouTube → 0 violation. ✅

## Self-review

1. **Decision la plus difficile** : rendu de `video_centered`. La façade via `<template>` (plutôt que rendre l'iframe directement) était le seul moyen de concilier perf/RGPD et markup validé serveur — confirmé par un clic réel en navigateur.
2. **Alternatives rejetees** : Media core `remote_video`/oembed (ADR-006, écarté : miniature non cropable + pas de façade native) ; plugin de comportement Paragraphs pour les options 50/50 (trop lourd pour 2 options binaires) ; cibler les éléments BEM en dur en SCSS (rejeté par le pattern Stylelint → nesting `&` uniquement).
3. **Point de moindre confiance** : l'esthétique fine de `image_full` (overlay titre/CTA) et `video_centered` (pastille play) — maquettes Figma non ouvertes ; structure (props/slots, ratios, a11y) solide, style à caler à l'intégration visuelle.
