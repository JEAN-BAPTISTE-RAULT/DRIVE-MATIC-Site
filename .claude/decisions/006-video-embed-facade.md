# ADR-006 : Vidéo — champ embed + façade

## Statut
Accepte

## Date
2026-08-13

## Contexte
La bibliothèque de paragraphes (ADR-001) comporte des blocs vidéo (`video_centered` en V1 ; `product_video_element`, `history_element` en V4/V5) définis comme **miniature cropable 16:9 + lien embed**. Le pipeline images (ADR-004) repose sur Media. Deux besoins spécifiques à la vidéo : (1) **cloisonner les providers** autorisés pour éviter l'injection d'iframe arbitraire (sécurité) ; (2) **ne pas charger la ressource tierce au chargement de page** (performance LCP + conformité RGPD : pas de dépôt YouTube/Vimeo avant action de l'utilisateur).

## Options considerees

### Option A : Media core `remote_video` (oembed)
- Avantages : cohérent avec la media-library (tout est Media) ; oembed natif Drupal.
- Inconvenients : la miniature auto-oembed n'est **pas cropable en BO** au ratio 16:9 requis ; allowlist providers moins directe ; le rendu oembed charge le tiers immédiatement (pas de façade native).

### Option B : `video_embed_field` (contrib) + miniature média séparée + façade
- Avantages : **allowlist providers** native (`allowed_providers`, validée serveur) ; miniature = média image distinct, donc **cropable 16:9** via le pipeline ADR-004 ; façade simple (iframe rendue puis différée).
- Inconvenients : diverge du modèle « tout est Media remote_video » ; un module contrib de plus (déjà présent et activé).

## Decision
**Option B.**
- **Lien embed** = champ `field_video` de type **`video_embed_field`**, réglage `allowed_providers` = **map** `{youtube: youtube, vimeo: vimeo}` (le validateur itère les **clés** comme provider ids ; une liste séquentielle casse). Une URL hors allowlist est **rejetée à la validation** (contrôle serveur, pas seulement front).
- **Miniature** = `field_image` (média image) rendue au mode d'affichage **`ratio_16_9`** (pipeline ADR-004, WebP responsive).
- **Façade** : le SDC `video-centered` rend la miniature + un bouton lecture accessible (`<button>`, `aria-label`), et place l'iframe (`content.field_video`, formatter `video_embed_field_video`) dans un **`<template>` inerte**. Le behavior JS **`driveMaticVideoFacade`** (`once`) clone le contenu du template dans le DOM **au clic** — la ressource tierce n'est donc chargée qu'à ce moment.

## Consequences
- Un paragraphe vidéo porte **deux champs** (embed + miniature média) au lieu d'une seule entité Media remote_video.
- Sécurité : providers cloisonnés côté serveur ; pas de chargement tiers avant interaction (perf + RGPD).
- Réutilisable pour les futurs blocs vidéo (V4/V5) via le même SDC/pattern façade.
- Dépendance : module `video_embed_field` (déjà activé). Fichiers : `field.storage.paragraph.field_video`, `field.field.paragraph.video_centered.*`, SDC `components/video-centered/` (`.twig`/`.scss`/`.js` + `libraryOverrides` : `core/drupal`, `core/once`).
