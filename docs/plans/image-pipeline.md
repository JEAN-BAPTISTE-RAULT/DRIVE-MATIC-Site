# Plan — Pipeline images

> Prealable technique #1 (PRD §7) + decision #11. Media-library reutilisable, crop BO par ratios, image styles responsives (6 breakpoints), sortie WebP. Rebranche les champs image existants (`contact`, `brand`).

## Décisions
- **WebP + fallback** : sources `<picture>` en WebP, `<img>` de repli au format d'origine (styles source = effet WebP ; style fallback = sans conversion).
- **Pleine largeur viewport** : images full-bleed jusqu'à la largeur d'écran ; plus grand dérivé ~2560 px.
- **Logos = raster (PNG)** : pas de `svg_image` ; les logos passent par le pipeline sans-crop.

## 1. Modèle & artefacts
- **Media type `image`** (core Media + Media Library) : images = entités réutilisables.
- **Crop types** (Crop API + Image Widget Crop) : `crop_1_1`, `crop_16_9`, `crop_12_5`. Le cas **sans crop** = scale seul (pas de crop type).
- **Image styles** par ratio × breakpoint × multiplicateur (crop + scale + WebP) + styles scale-only pour le sans-crop + styles fallback (sans WebP).
- **Responsive image styles** : `dm_1_1`, `dm_16_9`, `dm_12_5`, `dm_free` — mappés sur le breakpoint group `drive_matic`.
- **Widget média** : Image Widget Crop propose les 3 ratios à l'upload.

## 2. Ratios & usages (ADR-001)
| Ratio | Usages |
|-------|--------|
| **1:1** | image_text_50 |
| **16:9** | grid, jumbo, actualités, images/vidéos produit, cross-selling, histoire, vidéo centrée |
| **12:5** | image_full |
| **sans crop** | image_text_100, product_characteristics, image_centered, `contact`, `brand` |

## 3. Largeurs des dérivés (proposition, à affiner)
| Breakpoint | Largeur 1x | Largeur 2x |
|-----------|-----------|-----------|
| xs (≤575) | 576 | 1152 |
| sm (576-767) | 768 | 1536 |
| md (768-991) | 992 | 1984 |
| lg (992-1199) | 1200 | 2400 |
| xl (1200-1439) | 1440 | 2560 (cap) |
| xxl (≥1440) | 1920 | 2560 (cap) |

Hauteur = largeur × ratio pour les styles cropés. Le `sizes` (fraction d'affichage : 50 %, 1/3…) sera affiné **par SDC** au moment des paragraphes.

## 4. Fichiers impactés
- Config : `crop.type.*` (×3), `image.style.*` (~48), `responsive_image.styles.*` (×4), config media type `image` + displays, activation `responsive_image`/`media_library`.
- **Retrofit** : `contact` et `brand` → champ **référence média** (media:image) + formatter responsive `dm_free` (remplace `field_image` plain).
- **ADR-004** (pipeline images) + doc de référence.

## 5. Sécurité / contraintes
- Traitement serveur ; uploads validés par Media. WebP par GD natif (OK). Dérivés à la demande.
- **~48 image styles** en config (volumineux mais généré).
- **Pas d'upscale** abusif (les styles scale n'agrandissent pas les petites images).
- **SVG** hors périmètre (logos en PNG).

## 6. Cohérence
Décision #11 + préalable #1. S'appuie sur les breakpoints (PRD §6) + ratios (ADR-001). Nouvel **ADR-004**. Conforme le `contact` déjà livré (retrofit).

## 7. Étapes
1. Activer `responsive_image` + `media_library` ; vérifier le media type `image`.
2. Crop types (×3) + widget Image Widget Crop sur le media image.
3. Image styles (crop+scale+WebP + fallback + sans-crop) par ratio/breakpoint/multiplicateur.
4. Responsive image styles (×4, mapping breakpoints, WebP + fallback).
5. **Retrofit** `contact`/`brand` → média + formatter responsive ; image de test.
6. ADR-004 + doc + export config.

## 8. Tests / feedback
- Manuel : upload via média-library, crop au ratio, node → inspecter `srcset`/`<picture>`, vérifier **.webp** + dimensions par breakpoint + fallback.
- `drush image:flush` ; contrôle `files/styles/`.
- Cas limites : image < style (pas d'upscale), portrait sur ratio paysage (crop force le ratio), fallback non-WebP présent.

## Statut
- [x] Plan validé (WebP+fallback, full-width, logos PNG)
- [ ] Modules + crop types + widget
- [ ] Image styles + responsive image styles
- [ ] Retrofit contact/brand
- [ ] ADR-004 + export + commit
