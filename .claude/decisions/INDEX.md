# Decisions architecturales

Les decisions fondatrices sont dans [docs/PRD.md §3](../../docs/PRD.md).
Ce dossier documente les decisions **posterieures** au PRD initial.

| # | Titre | Statut | Date |
|---|---|---|---|
| [ADR-001](001-bibliotheque-paragraphes.md) | Bibliotheque de Paragraphes | Accepte | 2026-08-11 |
| [ADR-002](002-types-de-contenu.md) | Types de contenu editorial | Accepte | 2026-08-11 |
| [ADR-003](003-referentiel-vehicules.md) | Referentiel vehicules (taxonomies) | Accepte | 2026-08-12 |
| [ADR-004](004-pipeline-images.md) | Pipeline images | Accepte | 2026-08-12 |
| [ADR-005](005-config-par-environnement.md) | Config specifique a l'environnement (mail, secrets) | Accepte | 2026-08-12 |
| [ADR-006](006-video-embed-facade.md) | Video — champ embed (video_embed_field) + facade | Accepte | 2026-08-13 |
| [ADR-007](007-storage-partage-elements.md) | Storage partage `field_elements` (paires Bloc/Element) | Accepte | 2026-08-13 |
| [ADR-008](008-slideshow-swiper.md) | Slideshow — Swiper vendorise (librairie unique) | Accepte | 2026-08-13 |
| [ADR-009](009-telechargements-nommes.md) | Telechargements nommes (bloc multi-documents) | Accepte | 2026-08-14 |
| [ADR-010](010-metatags.md) | Metatags — defauts a tokens + champ de surcharge | Accepte | 2026-08-17 |
| [ADR-011](011-titre-affiche-et-alias.md) | Titre affiche (`field_title`) distinct du libelle admin + motifs d'alias | **Remplace par [ADR-014](014-titre-unique-porte-par-le-title.md)** | 2026-08-17 |
| [ADR-012](012-presentation-admin-front.md) | Presentation de l'administration sur le front (gin_toolbar desinstalle, onglets locaux) | Accepte | 2026-08-17 |
| [ADR-013](013-espacement-et-unites.md) | Systeme d'espacement (3 tokens) et unites px / rem | Accepte | 2026-08-17 |
| [ADR-014](014-titre-unique-porte-par-le-title.md) | Titre unique porte par le `title` ; `<h1>` rendu par le bloc d'ouverture | Accepte | 2026-08-18 |
| [ADR-015](015-habillage-des-formulaires.md) | Habillage des formulaires en fondation, grille declaree par le formulaire, modale d'aide en SDC | Accepte | 2026-08-18 |
| [ADR-016](016-colonne-de-contenu.md) | Colonne de contenu — token `--dm-content-column` retunable par gabarit | Accepte | 2026-08-19 |
| [ADR-017](017-recadrage-requis-par-champ.md) | Recadrage requis applique par champ (validation dediee), pas par media | **Remplace par [ADR-018](018-images-locales-par-paragraphe.md)** | 2026-08-19 |
| [ADR-018](018-images-locales-par-paragraphe.md) | Images locales par paragraphe (sans mediatheque) pour les 9 champs a ratio impose + node.news | Accepte | 2026-08-19 |
| [ADR-019](019-legals-body-metatags.md) | `legals` passe de paragraphes a body + metatags, et s'etend a 4 pages (amende ADR-002, ADR-010) | Accepte | 2026-08-20 |
| [ADR-020](020-footer-riche-menus.md) | Footer riche : menus Drupal (custom + core `footer`) plutot que liens en dur | Accepte | 2026-08-20 |
| [ADR-021](021-cartes-mega-menu.md) | Cartes du mega-menu (header F2) : champ image sur `menu_link_content` | Accepte | 2026-08-20 |
| [ADR-022](022-gabarit-email-webform.md) | Gabarit HTML inline pour les 8 e-mails webform (F10, F11), logo PNG en URL absolue | Accepte | 2026-08-21 |

## Quand creer un ADR

- Choix de lib ou d'approche technique significatif
- Changement d'interface entre modules
- Tout choix qu'on pourrait regretter dans 6 mois sans trace du raisonnement

## Comment

1. Creer `NNN-titre-court.md` en suivant `TEMPLATE.md`
2. Ajouter une ligne dans ce tableau
3. Commiter avec le code qui implemente la decision
