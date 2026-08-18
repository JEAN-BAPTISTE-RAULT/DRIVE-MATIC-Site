# ADR-004 : Pipeline images

## Statut
Accepte

## Date
2026-08-12

## Contexte
La decision #11 impose une gestion d'images industrialisee (media-library reutilisable, crop BO par ratios, image styles responsives alignes sur les breakpoints, WebP). Un premier jet du type `contact` avait pris un raccourci (champ image plain, sans style/responsive/WebP, hors media-library) — non conforme. Il fallait poser le vrai pipeline, prealable aux paragraphes (qui utilisent presque tous des images).

## Options considerees

### Option A : champ image plain par contenu
- Avantages : trivial.
- Inconvenients : pas reutilisable, ni crop, ni responsive, ni WebP → contraire a la decision #11.

### Option B : media-library + crop types + image styles responsives WebP + modes d'affichage par ratio
- Avantages : images reutilisables, crop BO par ratio, responsive + WebP, ratio choisi au rendu.
- Inconvenients : beaucoup d'image styles (config volumineuse).

## Decision
**Option B.**
- **Media-library** : les images sont des entites Media (bundle `image`), reutilisables.
- **Crop types** : `crop_1_1`, `crop_16_9`, `crop_12_5` (Crop API + Image Widget Crop sur le media image). Cas **sans crop** = scale seul.
- **Image styles** : 48 styles `dm_<ratio>_<largeur>` (crop + scale + WebP) aux largeurs des 6 breakpoints × multiplicateurs (1x/2x, full-width viewport, 2x cappe a 2560) + un style fallback (format d'origine) par ratio.
- **Responsive image styles** : `dm_1_1`, `dm_16_9`, `dm_12_5`, `dm_free`, mappes sur le breakpoint group `drive_matic`. **WebP + fallback** (le rendu produit `<picture>` avec `type="image/webp"` et un `<img>` de repli).
- **Modes d'affichage media** : `free`, `ratio_1_1`, `ratio_16_9`, `ratio_12_5` — chacun rend `field_media_image` avec le responsive image style correspondant. Le **ratio est choisi au niveau du champ referant** (via le mode d'affichage), pas sur le media.
- **Retrofit** : `contact.field_image` bascule de « image plain » a « reference media (bundle image) », rendue via le mode `free`.

## Consequences
- Les contenus/paragraphes referencent un **media image** et selectionnent le ratio via le **mode d'affichage media** (`free` / `ratio_*`). Reutilisable partout.
- `contact` est desormais conforme ; `brand` (a venir) suivra le meme schema (mode `free`).
- **`sizes`** : actuellement resolution-switching par breakpoint/multiplicateur ; a affiner par SDC (fraction d'affichage : 50 %, 1/3…) au moment des paragraphes.
- ~~**Crop optionnel**~~ → **TRANCHE le 2026-08-18 par l'utilisatrice : le recadrage est
  OBLIGATOIRE et MANUEL.** « Quand j'ai demande un crop avec un ratio precis, il est
  obligatoire et doit etre effectue manuellement par l'utilisateur a l'import de l'image
  avant d'enregistrer son contenu. » La piste d'un **fallback automatique** (focal point)
  est donc **ecartee** : le cadrage est une decision editoriale, pas une valeur par defaut.

  C'est deja ce que le formulaire impose : `core.entity_form_display.media.image.default`
  liste les trois types dans `crop_types_required`, et `ImageCrop::cropRequired()` bloque
  l'enregistrement du media tant qu'un recadrage requis n'est pas applique. **Aucun
  editeur ne peut donc contourner la regle.**

  ⚠️ **Le seul contournement est la creation programmatique** : l'API entite ne valide pas
  les formulaires. Tous les trous constates viennent de la — scripts de seed et imports
  depuis Figma. Corollaire pour la suite : **un media cree par script n'est pas conforme
  tant qu'un humain n'a pas cadre**, et un cadrage centre pose par script est une valeur
  machine, pas la decision editoriale que la regle exige.

  **Constat du 2026-08-18 — le risque s'est realise trois fois.** Le recadrage etant
  porte par le couple **(fichier, type de crop)**, un media recadre pour un ratio et
  reutilise a un autre ressort **non recadre**, sans erreur ni log. Releve a l'audit :
  aucune entite `crop_12_5` n'existait (17 blocs `image_full` rendus au ratio de leur
  source), une seule `crop_1_1`, et un `crop_16_9` manquant sur un media partage entre
  la home et la frise « Notre histoire ». Rien de tout cela n'etait visible sans mesurer.

  Audit remis a plat le meme jour : les 45 couples (fichier, ratio) du site sont
  recadres, et les 31 images a ratio impose des 29 pages publiques sortent au bon ratio.
  La question « crop requis ou automatique » reste ouverte, mais elle n'est plus
  theorique : **tant qu'il est optionnel, le controle doit etre outille**, un controle
  visuel ne le detecte pas. Methode : partir des modes d'affichage pour deduire le ratio
  impose de chaque emplacement, puis verifier l'existence du crop pour chaque fichier
  reference — et non l'inverse.
- **Prod** : dérives generes a la demande ; regeneration via `drush image:flush` au besoin.
