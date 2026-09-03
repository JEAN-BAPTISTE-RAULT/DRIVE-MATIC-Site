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
| [ADR-023](023-fil-ariane-style.md) | Fil d'Ariane stylise : ecart porte par lui-meme (couvre les gabarits hero), aligne sur la boite du header (pas la colonne de contenu) | Accepte | 2026-08-21 |
| [ADR-024](024-mutualisation-formulaire-simple.md) | Mutualisation du bundle `partner` en `simple_form` (page login, F2) : nouveau bundle + migration, multi-instance | Accepte | 2026-08-25 |
| [ADR-025](025-roles-back-office-et-email-activation.md) | Roles back-office (Admin/Partenaire) et e-mail d'activation de compte via `mailer_override` | Accepte | 2026-08-25 |
| [ADR-026](026-profil-partenaire-mes-informations.md) | Profil partenaire (« Mes informations personnelles ») : 10 champs User, lecture seule etendue au bloc entreprise, restriction du formulaire core d'auto-edition | Accepte | 2026-08-25 |
| [ADR-027](027-confirmation-deconnexion.md) | Confirmation de deconnexion (`/user/logout/confirm`) : question injectee via form-alter (pas de bloc titre sur route non-node), alignement sur la boite du header | Accepte | 2026-08-26 |
| [ADR-028](028-configurateur-formbase-vs-webform.md) | Configurateur de devis (F14, ecran 1) : FormBase custom plutot que Webform, `/configurer` repris du node placeholder | Accepte | 2026-08-26 |
| [ADR-029](029-mixin-boutons-partage.md) | Mixin Sass partage pour les boutons (couleurs + hauteur), `--load-path` supplementaire pour les SDC | Accepte | 2026-08-26 |
| [ADR-030](030-catalogue-tarifs-import.md) | Catalogue de tarifs (F17) — entite custom et import par rapprochement | Accepte | 2026-08-27 |
| [ADR-031](031-devis-tempstore.md) | Ecran Devis (F14 etape 2/3) — PrivateTempStore, pas de persistance avant l'etape 3 | Accepte | 2026-08-27 |
| [ADR-032](032-espacement-metriques-devis.md) | Bandeaux de totaux du devis — espace identique entre metriques plutot qu'alignement colonne par colonne | Accepte | 2026-08-31 |
| [ADR-033](033-entites-devis-livraison.md) | Entites Devis/Configuration/Ligne d'equipement/Adresse de livraison (F14 etape 3/3) : modele normalise, gel des donnees, IDOR | Accepte | 2026-09-01 |
| [ADR-034](034-modale-drupal-core.md) | Modales d'adresse de livraison : dialogue Drupal core (`use-ajax`) plutot que `help-modal` | Accepte | 2026-09-01 |
| [ADR-035](035-recap-adresses-livraison-admin.md) | Recapitulatif en lecture seule des adresses de livraison sur `/user/{uid}/edit` (consolidation back-office, entite conservee) | Accepte | 2026-09-01 |
| [ADR-036](036-email-confirmation-commande.md) | E-mail de confirmation de commande via hook_mail() + mailer_policy, jeton `quote` custom | Accepte | 2026-09-01 |
| [ADR-037](037-page-detail-devis-admin.md) | Page de detail d'un devis (back-office) : Controller + render array plutot que view_builder/view modes | Accepte | 2026-09-02 |
| [ADR-038](038-cycle-de-vie-devis-4-statuts.md) | Cycle de vie du devis a 4 statuts (renommage en_cours->a_commander, statut Commande, remise DM par ligne calculee a la lecture) | Accepte | 2026-09-02 |
| [ADR-039](039-deploiement-preprod-rsync.md) | Deploiement local -> preprod par rsync (git ls-files), sans plus jamais synchroniser la base de donnees | Accepte | 2026-09-02 |
| [ADR-040](040-tracabilite-remise-exceptionnelle.md) | Tracabilite des remises exceptionnelles Drive Matic (entite `quote_discount_change`, historique fusionne avec les statuts) | Accepte | 2026-09-02 |
| [ADR-041](041-pdf-devis.md) | PDF du devis (dompdf, champ Reference gele, generation au clic Commander + regeneration sur remise DM) | Accepte | 2026-09-02 |
| [ADR-042](042-smtp-preprod.md) | Transport SMTP preprod (mails.passerelle.com, secret hors config versionnee) | Accepte | 2026-09-02 |
| [ADR-043](043-remises-partenaire-par-equipement.md) | Remises partenaire par equipement (4 champs), remise DM en remplacement (plus de cumul), formulaire regroupe par type, snapshot fige a la creation (pas de suivi live) | Accepte | 2026-09-03 |
| [ADR-044](044-historique-remises-partenaire.md) | Historique des changements de remise partenaire (entite dediee, hook_user_update generique, affiche sur /user/{uid}/edit) | Accepte | 2026-09-03 |

## Quand creer un ADR

- Choix de lib ou d'approche technique significatif
- Changement d'interface entre modules
- Tout choix qu'on pourrait regretter dans 6 mois sans trace du raisonnement

## Comment

1. Creer `NNN-titre-court.md` en suivant `TEMPLATE.md`
2. Ajouter une ligne dans ce tableau
3. Commiter avec le code qui implemente la decision
