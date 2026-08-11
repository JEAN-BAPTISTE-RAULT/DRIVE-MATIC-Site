# Bibliotheque de Paragraphes — validee

> Liste **validee par l'utilisatrice** (arbitrage du recoupement specs ↔ maquettes). Reference d'implementation. Decision actee dans [ADR-001](../../../.claude/decisions/001-bibliotheque-paragraphes.md).
>
> Convention : `machine_name` — « Label ». **obl** = obligatoire, **opt** = optionnel. Les **Elements** ne sont pas placables seuls : ils sont references par leur **Bloc** parent (paragraphes imbriques). Chaque paragraphe retenu = 1 **SDC** (decision #10).

## Blocs autonomes

| # | Machine name | Label | Champs | Media / crop | Options / comportement |
|---|--------------|-------|--------|--------------|------------------------|
| 1 | `image_text_50` | Bloc :: Image + texte 50/50 | légende image (opt), titre (obl), description (obl), lien (opt), lien de téléchargement (opt) | image cropable **1:1** (obl) | texte gauche/droite ; fond gris/blanc |
| 4 | `image_text_100` | Bloc :: Image + texte 100% | légende image (opt), titre (opt), description (opt), lien (opt), lien de téléchargement (opt) | image **SANS CROP** (largeur fixe maquette, hauteur proportionnelle) (obl) | — |
| 7 | `text_centered` | Bloc :: Texte centré | titre (obl), description (opt), lien (opt), lien de téléchargement (opt) | — | — |
| 8 | `text_left_aligned` | Bloc :: Texte aligné à gauche | titre (obl), description (obl), lien (opt), lien de téléchargement (opt) | — | — |
| 13 | `image_full` | Bloc :: Image 100% | titre (opt), lien (opt) | image cropable **12:5** (obl) | — |
| 14 | `product_arguments` | Bloc :: Argumentaire produit | max 3 titres (min 1) | — | — |
| 26 | `image_centered` | Bloc :: Image centrée | légende (opt) | image **SANS CROP** (largeur fixe maquette, hauteur proportionnelle) (obl) | — |
| 27 | `video_centered` | Bloc :: Vidéo centrée | lien embed vidéo (obl), légende (opt) | thumbnail cropable **16:9** (obl) | — |
| 9 | `news_home` | Bloc :: Actualités Homepage | titre (obl), lien (obl) | image principale des news crop **16:9** | vue des 5 news (`news`) les plus récentes en **slideshow** ; par item : image 16:9, titre, lien « Lire la suite » → détail |
| 10 | `brands_home` | Bloc :: Marques Homepage | titre (obl), lien (obl) | — | vue de toutes les marques (`brand`), ordre alpha, en **slideshow** |

## Blocs composés (Bloc + Éléments imbriqués)

| # | Machine name | Label | Contenu | Média / crop | Comportement |
|---|--------------|-------|---------|--------------|--------------|
| 2 | `accordion_element` | Élément :: accordéon | titre (obl), description (obl), lien (opt), lien de téléchargement (opt) | — | — |
| 3 | `accordion` | Bloc :: accordéon | titre (opt) + N× `accordion_element` (min 1, illimité) | — | — |
| 5 | `grid_element` | Élément :: grille | titre (opt), N× liens (min 1, illimité) | image cropable **16:9** (obl) | — |
| 6 | `grid` | Bloc :: grille | titre (opt) + N× `grid_element` (min 1, illimité) | — | — |
| 11 | `jumbo_home_element` | Élément :: Jumbo Homepage | titre (obl), lien (opt) | image cropable **16:9** (obl) | — |
| 12 | `jumbo_home` | Bloc :: Jumbo Homepage | max 3 × `jumbo_home_element` (min 1) | — | **slideshow** si 2 ou 3 |
| 15 | `product_image_element` | Élément :: Image produit | légende image (opt), titre (opt), description (opt), lien (opt), lien de téléchargement (opt) | image cropable **16:9** (obl) | — |
| 16 | `product_video_element` | Élément :: Vidéo produit | lien embed vidéo (obl), légende vidéo (opt), titre (opt), description (opt), lien (opt), lien de téléchargement (opt) | thumbnail cropable **16:9** (obl) | — |
| 17 | `product_features` | Bloc :: Présentation produit | max 5 × (`product_image_element` **ou** `product_video_element`) (min 1) | — | **slideshow** si > 1 |
| 18 | `product_characteristic_element` | Élément :: Caractéristique produit | titre (obl), description (obl) | — | — |
| 19 | `product_characteristics` | Bloc :: Caractéristiques produit | légende image (opt), titre (opt), N× `product_characteristic_element` (min 1, illimité), lien de téléchargement notice technique (opt), lien de téléchargement documentation (opt) | image **SANS CROP** (largeur fixe maquette, hauteur proportionnelle) (obl) | — |
| 20 | `product_cross_element` | Élément :: Cross-selling produit | titre (obl), lien (obl) | image cropable **16:9** (obl) | — |
| 21 | `product_cross` | Bloc :: Cross-selling produit | titre (obl), max 5 × `product_cross_element` (min 1) | — | — |
| 22 | `triptych_element` | Élément :: Triptyque | texte au-dessus (opt), texte en gras (obl), texte au-dessous (opt) | — | — |
| 23 | `triptych` | Bloc :: Triptyque | max 3 × `triptych_element` (min 1) | — | — |
| 24 | `history_element` | Élément :: Histoire | titre (obl), description (opt), légende (opt) | **soit** image cropable **16:9** (obl) **soit** thumbnail vidéo cropable **16:9** (obl) + lien embed (obl) — **exclusif** | — |
| 25 | `history` | Bloc :: Histoire | titre (obl), N× `history_element` (min 1, illimité) | — | **slideshow** si > 1 |

## Ratios de crop (entrée pour l'étude images, préalable #1)

| Ratio | Paragraphes concernés |
|-------|-----------------------|
| **1:1** | `image_text_50` |
| **16:9** | `grid_element`, `news_home` (image news), `jumbo_home_element`, `product_image_element`, `product_video_element` (thumbnail), `product_cross_element`, `history_element` (image ou thumbnail vidéo), `video_centered` (thumbnail) |
| **12:5** | `image_full` |
| **SANS CROP** (largeur fixe maquette, hauteur proportionnelle) | `image_text_100`, `product_characteristics`, `image_centered` |

→ L'étude images (PRD §7) doit produire, pour **1:1 / 16:9 / 12:5**, les image styles responsive sur les 6 breakpoints + sortie WebP ; et definir la regle de largeur fixe pour le cas « sans crop ».

## Notes d'implementation

- **Éléments imbriqués** : champs de reference `entity_reference_revisions` (module Paragraphs) ; les types « Élément » sont exclus du placement direct en contenu.
- **Vidéo** : les paragraphes video stockent un **thumbnail cropable 16:9** + un **lien embed** ; valider/allowlister les providers (YouTube/Dailymotion/Vimeo) pour eviter l'injection d'iframe arbitraire.
- **Liens** (precise par [ADR-002](../../../.claude/decisions/002-types-de-contenu.md)) : `lien` = champ **Link** pouvant etre **interne** (reference node) ou **externe**, avec **cible au choix de l'admin** (onglet courant / nouvel onglet).
- **« lien de téléchargement »** = en réalite un champ **fichier** : afficher en front un lien de telechargement avec **nom + format + poids** (calcul auto) uniquement si le champ est renseigne. Distinct de la convention « lien » ci-dessus.
- **Slideshow** : comportement front (JS vanilla, accessible clavier RGAA AA) ; degrade en liste si un seul item.
- **Vues** : `news_home` et `brands_home` embarquent une vue Drupal (content types `news` / `brand`) — declarer les list cache tags.
- **SDC** : Bloc composé = SDC parent qui compose une liste de SDC « Élément ».
