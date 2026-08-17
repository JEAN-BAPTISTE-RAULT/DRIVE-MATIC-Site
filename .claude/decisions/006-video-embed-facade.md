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
- Dépendance : module `video_embed_field` (déjà activé). Fichiers : `field.storage.paragraph.field_video`, `field.field.paragraph.video_centered.*`, SDC `components/video-centered/`.

## Mise à jour (V3, 2026-08-13)
Le behavior de façade a été **mutualisé** en librairie de thème **`drive_matic/video-facade`** (`js/video-facade.js`) : JS **générique piloté par data-attributs** (`[data-dm-video-facade]`, `[data-dm-video-play]`, `template[data-dm-video-embed]`), agnostique du markup BEM. Un **seul** `Drupal.behaviors.driveMaticVideoFacade` (évite les collisions). Consommé par `video_centered` (migré, `libraryOverrides: drive_matic/video-facade`) **et** `history_element` (V3), réutilisable par `product_video_element` (V5). Le CSS de la pastille reste scopé par composant (seul le JS est partagé).

## Mise a jour (integration maquette, 2026-08-17)

La **pastille de lecture** est mutualisee a son tour : le CSS n'est plus scope par
composant (il divergeait — carre blanc translucide dans `video_centered`, cercle
rouge dans `history_element`, plaque + glyphe masque dans `product_video_element`).

- Un **SDC dedie `video-play`** porte desormais le markup **et** le style de la
  plaque ; les trois façades l'incluent (`{{ include('drive_matic:video-play') }}`)
  dans leur `<button data-dm-video-play>`. La CSS du composant inclus est bien
  attachee a l'inclusion : aucune declaration de dependance n'est necessaire.
- Le visuel est l'**export du calque « Group 17 »** de la maquette
  (`images/icons/video-play.svg`, Figma 396:11579) : plaque blanche de 70px
  arrondie a 8px et a 70 % d'opacite + glyphe de lecture acier. Les couleurs sont
  portees par le SVG, ce qui deroge sciemment a la regle « pas de couleur en
  dur » : c'est un visuel de maquette rendu en image, pas un glyphe a teindre —
  contrairement aux icones utilisees en `mask` (chevron, globe, telechargement).
  L'export brut de Figma inclut le fond du canvas et celui du frame : ces deux
  rectangles parasites doivent etre retires a la main.
- Consequence : le rayon, l'opacite et le glyphe d'une façade ne se reglent plus
  composant par composant. Un changement de maquette se fait en remplacant
  l'asset. La contrainte de couplage est que le conteneur de la miniature doit
  etre **positionne** (la plaque se centre en absolu) — documente dans le SDC.
