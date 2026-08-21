# PRD — DRIVE-MATIC

## 1. One-liner

Site vitrine de la marque DRIVE-MATIC (equipement de vehicules pour auto-ecoles et personnes a mobilite reduite) : il presente l'offre, les actualites et les marques partenaires au grand public, et offre aux clients autorises un espace authentifie pour creer et suivre leurs devis — sans achat ni paiement en ligne.

## 2. Contexte et probleme

### Utilisateurs

| Profil | Volume | Besoin principal |
|--------|--------|------------------|
| Visiteur grand public (anonyme) | Trafic public | Decouvrir l'offre (transformation auto-ecole, equipement PMR), consulter produits & marques, lire les actualites, contacter / demander a devenir partenaire |
| Partenaire (client autorise, authentifie) | ~100 | Acceder a un tableau de bord pour creer un devis et suivre ses devis a finaliser / en cours |
| Administrateur dirigeant DRIVE-MATIC | Quelques-uns _(droits a preciser)_ | Gerer contenus, produits, partenaires et devis via le back-office Drupal |
| Super-administrateur (Passerelle) | 1 — audrey@passerelle.com | Tous les droits : administration technique et fonctionnelle |

### Probleme

Le site actuel (drivematiclegrand.com) se reduit aujourd'hui a une page de transition « en refonte » : aucun menu, aucun produit, aucune actualite, aucune marque, aucun espace client, aucun devis en ligne — le seul contact possible se fait par telephone ou e-mail. L'offre de DRIVE-MATIC est une offre de niche (conversion de vehicules pour auto-ecoles, equipement PMR) qui n'est ni valorisee ni consultable en ligne, et les clients autorises n'ont aucun outil en self-service pour demander et suivre leurs devis.

### Pourquoi les solutions existantes ne suffisent pas

L'existant ne propose ni presentation structuree de l'offre, ni espace partenaire authentifie, ni outil de creation/suivi de devis : les demandes passent par des canaux hors ligne (telephone/e-mail), sans suivi centralise. DRIVE-MATIC veut une experience a la fois plus moderne (offre, actualites, marques) et plus complete (espace client cloisonne avec tableau de bord de devis), sans pour autant basculer vers un e-commerce transactionnel — son modele de vente repose sur des devis negocies et de l'equipement sur-mesure, incompatible avec un panier/paiement en ligne.

## 3. Decisions d'architecture (verrouillees)

<!-- Decisions fondatrices du projet. L'agent ne doit PAS les remettre en question. -->
<!-- Chaque decision = un choix + sa justification -->

| # | Decision | Justification |
|---|----------|---------------|
| 1 | **Drupal 11** (derniere version stable) — socle de compatibilite minimal impose au client : **PHP 8.3**, **MariaDB 10.11**, deploiement via **SSH + Composer** | Socle CMS mur, support long terme. Le couple PHP 8.3 / MariaDB 10.11 est le minimum requis pour Drupal 11 ; aucun hebergeur specifique n'est verrouille (acces serveur cote client « en attente ») |
| 2 | Front **sans framework JS** : Twig + Vanilla JS (comportements Drupal) + SCSS. Composants structures en **SDC** (cf. #10) ; nommage **BEM** au sein de chaque composant ; **SMACSS reduit aux fondations globales** (reset, tokens/variables, typographie) | Simplicite, pas de dette front, conforme aux standards Drupal |
| 3 | **Pas de paiement en ligne.** Le site permet de configurer, chiffrer et **enregistrer une commande** (bouton « Commander » → date de commande, e-mail de confirmation, PDF du devis) ; mais **aucun paiement n'y transite**. Le bon de commande (avec frais de livraison) et la facturation sont traites **hors site** par DRIVE-MATIC | Modele de vente = devis negocies avec conditions commerciales par partenaire, equipement sur-mesure |
| 4 | **Comptes partenaires crees uniquement dans le back-office Drupal** (pas d'auto-inscription) | Les partenaires sont des clients _autorises_, valides par DRIVE-MATIC |
| 5 | **Cloisonnement strict des donnees partenaires**, autorisation systematiquement re-verifiee cote serveur | Securite : jamais d'exposition de donnees partenaire a un utilisateur anonyme |
| 6 | **Site en francais uniquement** (pas de traduction de contenu) ; le code reste traduisible (`t()` / `\|t`) par convention | Audience et marche francophones ; evite la complexite multilingue non requise |
| 7 | Theme front = **theme custom autonome** genere via `starterkit`, rendu **independant** (templates copies, `base theme: false`, **sans dependance a `stable9`** deprecie en Drupal 12) | Markup 100% maitrise pour un design sur-mesure + bibliotheque SDC ; perenne pour Drupal 12 |
| 8 | **Accessibilite visee : RGAA / WCAG 2.1 niveau AA** | Audience incluant des personnes a mobilite reduite (PMR) ; exigence structurante pour le theme et les composants |
| 9 | Theme d'administration **Gin**, configure avec la **toolbar d'administration horizontale** (en haut de l'ecran) | Back-office moderne et ergonomique pour les administrateurs Drive Matic |
| 10 | **Tout le front (CSS + Twig) est gere via des SDC** (Single Directory Components) : chaque composant = un dossier autonome (`*.component.yml`, Twig, CSS, JS, props/slots). Aucun CSS/Twig hors SDC, hormis les fondations globales | Composants isoles, reutilisables et testables ; co-localisation CSS/Twig ; reduit la dette front et facilite la maintenance |
| 11 | **Gestion des images industrialisee** : stockage en **media-library reutilisable**, **crop en back-office a l'import** (ratios definis), **image styles responsives** alignes sur les breakpoints du site, **conversion WebP** systematique. Le detail est etabli par une **etude prealable** (cf. section 7) | Performance (poids, LCP), coherence visuelle, autonomie editoriale, accessibilite |

## 4. Features — Format comportemental

> Source : specifications fonctionnelles Passerelle v1.2 (29/07/2026). Annotations : `[INFERE]` = deduit, a valider ; `[A PRECISER]` = absent des specs, decision requise. Perimetre = **tout en V1**.

---

### Socle editorial & navigation (public)

### F1 : Systeme de contenu par Paragraphes

**Trigger** : Quand un administrateur compose une page de contenu en back-office.

**Action** :
1. Il assemble la page a partir des modeles de Paragraphes mis a disposition.
2. Il renseigne les champs de chaque paragraphe (titres, textes CK Editor, images/documents issus de la mediatheque, CTA a selectionner).

**Resultat attendu** : Une page publiee composee de blocs conformes aux maquettes, sans intervention CSS.

**Criteres d'acceptation** :
- [ ] Les modeles de paragraphes disponibles sont ceux de la **bibliotheque validee** (27 paragraphes, dont des paires Bloc/Element), issue du recoupement specs ↔ maquettes — voir [ADR-001](../.claude/decisions/001-bibliotheque-paragraphes.md) et `docs/active/paragraphs/library.md`.
- [ ] Les CTA selectionnables sont : Demander un devis, Nous contacter, Lire la suite, En savoir plus, Configurez votre vehicule, Marques partenaires.
- [ ] Les textes riches sont edites via CK Editor ; images et documents proviennent de la mediatheque Drupal.
- [ ] Pour tout document telechargeable, le CMS calcule et affiche automatiquement nom, format et poids.

**Cas limites** :
- Champs optionnels laisses vides → le bloc s'affiche sans l'element concerne (pas d'espace vide casse).

---

### F2 : Navigation (menu, fil d'Ariane, footer)

**Trigger** : Quand un visiteur navigue sur le site.

**Action** :
1. Il utilise le menu principal multi-niveaux (Auto-ecole, Vehicule PMR, Drive Matic, Actualites, Demander un devis, Espace partenaire).
2. Il se repere via le fil d'Ariane et accede aux liens du footer.

**Resultat attendu** : Une navigation coherente sur tout le site.

**Criteres d'acceptation** :
- [x] Le menu reprend l'arborescence des specs (rubriques niveau 1 et 2).
- [ ] Le fil d'Ariane est present sur toutes les pages **sauf** la home page.
- [x] Le footer contient : coordonnees, solutions auto-ecole/PMR, assistance (contact, FAQ), reseaux sociaux (Instagram, TikTok, LinkedIn, YouTube), et les liens legaux (CGV, CGU, mentions legales, donnees personnelles).
- [x] Drive Matic peut creer des rubriques de **niveau 2** et des pages en autonomie ; la creation de rubriques de **niveau 1** requiert une intervention CSS de Passerelle.

**Cas limites** :
- Item « Espace partenaire » : affiche le sous-menu authentifie (Tableau de bord, Mes devis, Mes informations personnelles, Me deconnecter, Supprimer mon compte) **uniquement** pour un partenaire connecte.

- **Mise en oeuvre — header du 2026-08-20** (maquettes desktop 433-7989 + 5 dropdowns, mobile 526-20394 + tiroir), [ADR-021](../.claude/decisions/021-cartes-mega-menu.md) : menu principal (4 rubriques, niveau 2 mixte cartes/liens) et menu compte (5 liens) crees en base, non versionnes (script Drush ponctuel, meme decision que le footer). Liens actives vers du contenu reel pour tout ce qui existe deja (produits, corporate, FAQ, contact, actualites, connexion/deconnexion, edition du compte) ; « Tableau de bord », « Mes devis » et « Supprimer mon compte » restent en `<nolink>`, aucune page/route ne les portant encore (F13/F15/F16). Le bouton « Demander un devis » pointe vers la page `configurator` (F14, `/configurer`). Les 7 visuels de carte (Auto-ecole/PMR) sont recadres (16:9, `field_nav_card_image`). **Corrige le meme jour** (addendum ADR-021) : le dropdown « Drive Matic » (sans carte) a 3 colonnes de liens, pas une liste a plat ; les flyouts couvrent toute la largeur de la page, pas seulement la largeur plafonnee du bandeau ; l'ouverture d'un dropdown ne decale plus les autres rubriques du nav.

---

### F3 : Home page

**Trigger** : Quand un visiteur arrive sur la page d'accueil.

**Action** : Il parcourt les blocs specifiques de la home (template dedie, non composable librement).

**Resultat attendu** : Une vitrine synthetique de l'offre, des actualites et des marques.

**Criteres d'acceptation** :
- [ ] Blocs presents : titre generique ; jusqu'a 3 jumbos (visuel + titre + CTA optionnel) ; bloc solutions auto-ecole & PMR (3 blocs produits avec liens) ; bloc configurateur ; bloc actualites (5 plus recentes + lien « voir toutes ») ; carrousel marques partenaires (ordre alpha, navigation fleches) ; bloc image a gauche fond gris ; accordeons SEO.
  - **Composition faite** (maquette Figma `303-5967`), 8 blocs de haut en bas :

    | Section de la maquette | Paragraphe |
    |---|---|
    | Titre « Equipements de conduite pour auto-ecoles et vehicules adaptes » | `text_centered` |
    | Slider de 2 bannieres (fleche + points) | `jumbo_home` |
    | « Nos solutions auto-ecole et PMR » — 3 vignettes titrees, 3/2/2 liens | `grid` |
    | Bandeau « Configurez votre vehicule et obtenez votre tarif » | `image_text_100` |
    | « Actualites Drive Matic Legrand » | `news_home` |
    | « Nos marques partenaires » | `brands_home` |
    | « Un savoir-faire et des certifications » (fond gris, image 1:1 a gauche) | `image_text_50` |
    | Accordeons SEO | `accordion` |

  - Les **ratios de la maquette confirment la bibliotheque** : bannieres 900x506 et vignettes 440x246 (16:9), visuel savoir-faire 510x510 (1:1).
  - `[OUVERT]` **Huit liens sont des placeholders** vers `/` : banniere PMR, cinq des sept liens du bloc solutions, bouton configurateur, lien d'accordeon — leurs pages n'existent pas encore. Cables pour de vrai : banniere auto-ecole → page solution, « Double pedalier auto-ecole » → fiche produit, actualites → `/actualites`, marques → `/marques-partenaires`, savoir-faire → « Qui sommes-nous ».
  - `[OUVERT]` Le visuel du configurateur sort au ratio de sa source (1440x640) et non au cadrage de la maquette (1,78) : `image_text_100` est un bloc **sans crop** (ADR-001).
- [ ] Accordeons SEO : l'ouverture d'un accordeon ferme le precedent.
- [ ] Jumbos : navigation par **fleches et points de pagination** ; **pas de boucle** (la fleche en bout de course disparait) ; la banniere suivante est visible en **apercu coupe** a droite.
- [ ] Carrousel marques (integration maquette) : titre **centre**, rangee de **tuiles carrees** (logo centre, bordure fine, coins arrondis) defilant horizontalement, **fleche a chaque extremite** de la rangee, lien en **bouton gris centre** dessous. Le **nom** de la marque n'est pas affiche (la maquette ne montre que le logo) mais reste disponible pour les technologies d'assistance.
- [ ] Bloc actualites (integration maquette) : titre **centre** avec la **paire de fleches a droite**, cartes defilant horizontalement (l'actualite suivante en **apercu coupe**), et le lien « voir toutes » en **bouton gris centre sous la piste**. Chaque carte n'affiche que le **visuel 16:9** et le **titre** de l'actualite (ni date, ni chapo), plus le lien « Lire la suite ».

**Cas limites** :
- Moins de 5 actualites publiees → le bloc affiche celles disponibles. `[INFERE]`
- Un seul jumbo → pas de slideshow : ni fleches, ni points de pagination.

---

### F4 : Pages « Transformer un vehicule en auto-ecole » & « Equiper un vehicule pour PMR »

**Trigger** : Quand un visiteur consulte une page de solution.

**Resultat attendu** : Presentation de la solution avec acces aux produits et au configurateur.

**Criteres d'acceptation** :
- [ ] Template dedie : image 100 % largeur ; bloc informations generales ; bloc solutions (3 a 6 blocs produits avec liens vers les fiches) ; bloc configurateur ; bloc FAQ (accordeons, fermeture du precedent a l'ouverture).

---

### F5 : Pages Produit

**Trigger** : Quand un visiteur consulte une fiche produit (auto-ecole ou PMR).

**Resultat attendu** : Argumentaire produit complet, sans prix.

**Criteres d'acceptation** :
- [ ] Template commun a tous les produits : image 100 % largeur ; bloc argumentaires (`product_arguments`) ; bloc swipe (max 5, visuel ou video + titre + texte + CTA — `product_features`) ; bloc caracteristiques techniques (donnees titre/texte + notice technique et documentation telechargeables avec nom/format/poids auto — `product_characteristics`) ; bloc titre + CTA ; bloc configurateur ; bloc cross-selling (1 a 5 produits avec lien vers fiche — `product_cross`).
  - Note d'arbitrage : le « bloc argumentaires » a ete acte comme **1 a 3 titres seuls** (`product_arguments`) dans la bibliotheque validee ([ADR-001](../.claude/decisions/001-bibliotheque-paragraphes.md) #14), et non « image/texte » comme le suggerait la formulation initiale des specs. Les blocs produit sont fournis par la vague V5 des paragraphes (F1).
  - **Type `product` livre** : la page est un **assemblage de paragraphes**, sans template dedie (le template generique ne rend pas le libelle du node en pleine page). Allowlist = les 4 blocs V5 + `text_centered`, `text_left_aligned`, `image_text_50/100`, `accordion`, `image_full` — **sans `grid`**. Le **corps de texte est masque a l'affichage** : il alimente la meta description, la page etant entierement composee de blocs.
  - **« Bloc configurateur » — arbitre** : il n'existe pas comme paragraphe dans la bibliotheque ADR-001. Ce contenu se compose avec **`image_text_100`** (autorise sur `homepage`, `transform` et `product`). Son lien reste un placeholder tant que le configurateur (F14) n'existe pas.
  - **Nom du document — arbitre** : le libelle de **tout bouton de telechargement du site** est **saisi en back-office** par la personne qui depose le fichier (champ « description » du champ fichier), et il est **obligatoire** des qu'un document est joint ; le **format et le poids** restent calcules automatiquement. Cf. [ADR-009](../.claude/decisions/009-telechargements-nommes.md).
- [ ] Bloc cross-selling (`product_cross`, integration maquette) : titre puis **grille de deux colonnes** de cartes ; chaque carte = visuel **16:9 arrondi** et **une seule ligne cliquable** (le **titre** de la carte en est le libelle, son champ lien la destination) — le libelle du champ lien n'est donc **pas** affiche. Au-dela de deux cartes, elles passent a la ligne.
- [ ] Bloc « swipe » (`product_features`, integration maquette) : diapositives de 900px **calees a gauche** sur le conteneur et **debordantes a droite** (aperçu coupe de la suivante) ; chaque diapositive = visuel **16:9 arrondi**, titre, description et **bouton gris** (le lien) ; une diapositive **video** affiche une **façade** (plaque blanche translucide + glyphe de lecture au centre du visuel), l'iframe n'etant chargee qu'au clic. La fleche est **centree sur le visuel**, pas sur la diapositive, et **disparait en bout de course**.
- [ ] Bloc caracteristiques (`product_characteristics`, integration maquette) : **bandeau anthracite pleine largeur**, visuel produit a gauche (sans crop), titre blanc + caracteristiques en **2 colonnes** (libelle gris / valeur blanche) a droite, puis les **deux boutons de telechargement** alignes sur ces memes colonnes.
- [ ] **Aucun prix** n'est affiche sur les pages produit publiques.

---

### F6 : Pages Documentations & F7 : Page Marques partenaires

**Criteres d'acceptation** :
- [ ] **Documentations** : template dedie listant les documents Auto-ecole et PMR, ordre gere en back-office, chaque document affiche nom/format/poids (calcul auto).
  - **Mise en oeuvre — revue le 2026-08-18** : deux **champs Fichier a iteration illimitee** (`field_documents_school`, `field_documents_pmr`) portes par le node `documents`, et non un paragraphe « section » — la bibliotheque ADR-001 reste close a 27. Les fichiers sont **saisis dans leur ordre d'affichage**. Les **titres de section sont en dur** dans `node--documents.html.twig` (« Auto-ecoles », « PMR ») : le gabarit ne depend donc pas de la configuration. Une section vide n'affiche **rien** (pas de titre orphelin) — teste.
  - **Integration visuelle — 2026-08-19, maquette Figma `398-12119`** : chapo dans le SDC `page-intro`, sections dans le nouveau SDC `documents-list` — liste **zebree**, chaque fichier en **ligne entierement cliquable** (nom + format + poids a gauche, « Telecharger » + icone a droite, decision de l'utilisatrice). Les deux champs ne passent plus par leur formatter (masques dans le view display) : `drive_matic_preprocess_node()` construit `sections` via le nouveau helper `_drive_matic_field_downloads()`, qui reattache les cache tags des fichiers. Rendu distinct du bouton contour+icone de `product_characteristics`, malgre la reutilisation anticipee par [ADR-009](../.claude/decisions/009-telechargements-nommes.md) (addendum 2026-08-19).
  - **Plus de type de contenu `document`** (supprime le 2026-08-18, avec ses nodes) : un document n'est plus une entite mais une **valeur du champ Fichier**. Son libelle public est la **description du fichier** ([ADR-009](../.claude/decisions/009-telechargements-nommes.md)), saisie a l'upload ; le rendu est un lien portant ce libelle, suivi du **format et du poids calcules**. ⚠️ Ne pas confondre avec le **media type `document`**, conserve : c'est le bundle « document » de la bibliotheque de medias.
- [ ] **Marques partenaires** : bloc informations generales + liste des marques sous forme de logos, **ordre alphabetique**.
  - **Mise en oeuvre** : Vue `brands` embarquee, tuiles `brand-logo` (les memes qu'en home) enveloppees par le SDC **`brands-grid`** — la page passe a la ligne la ou la home defile. Logos **non cliquables** (page canonique du fragment bloquee).
  - **Jeu de demonstration** : **12 marques** portant les logos reels de la maquette Home (Aixam, BYD, Citroen, Cupra, Dacia, DS Automobiles, Fiat, Ford, Hyundai, Jeep, Lexus, Ligier). La maquette n'en fournit pas davantage : les quatre marques factices d'origine ont ete supprimees et trois autres renommees. Douze tuiles debordent toujours la rangee de la home, le test des fleches reste donc valable.

---

### F8 : Actualites (liste + detail)

**Trigger** : Quand un visiteur consulte les actualites.

**Criteres d'acceptation** :
- [ ] **Liste** : derniere publiee/modifiee en tete ; chaque item = photo principale + titre + date + lien « Lire la suite » ; pagination **10 par page**.
  - **Mise en oeuvre** : node `all_news` (titre, corps, metatags) + Vue `all_news` embarquee par `drupal_view()`. Ligne = SDC **`news-teaser`** (visuel 16:9, titre, date, « Lire la suite ») en mode d'affichage `teaser` — a distinguer de `news-card`, la carte du bloc home, qui n'affiche **pas** la date. Liste vide → message « Aucune actualite n'est publiee pour le moment. ».
- [ ] **Detail** : titre, date, visuel principal, blocs (titre/texte/lien/document/video optionnels), possibilite d'ajouter des paragraphes texte/image/video.
  - **Mise en oeuvre** : `news` porte `field_paragraphs` (allowlist `text_left_aligned`, `image_centered`, `video_centered`). La date affichee est `changed`, format **`dm_long`** (`j F Y`), rendue en `<time datetime>`. Le titre vient du bloc titre de page ; le reste est enveloppe par le SDC **`news-article`** (integration maquette 438-10665, 2026-08-19).
  - **Ordre d'affichage** : titre, date **centree**, visuel + legende **ferree en bas a droite**, corps de texte, puis les blocs. Le corps ne figure pas sur la maquette : il est affiche a la demande de l'utilisatrice, entre la legende et les blocs.
  - **Le visuel est affiche sans recadrage** sur le detail (formatter `responsive_image`, style `dm_free`, largeur de la colonne, hauteur proportionnelle) : ses proportions sont celles du fichier envoye, le ratio devient donc une **decision editoriale au choix du fichier**. Le recadrage 16:9 exige a l'import ne sert plus qu'aux **vignettes** des listes (`teaser`, `card`) et du carrousel home. Depuis [ADR-018](../.claude/decisions/018-images-locales-par-paragraphe.md) (2026-08-19), `news.field_photo` (renomme, ex-`field_image`) est un **champ image local** sans mediatheque — cf. decision #11.
  - **Colonne unique de 960** sur ce gabarit, contre 900 ailleurs : cf. [ADR-016](../.claude/decisions/016-colonne-de-contenu.md).
  - **Alias** : `/actualites/[node:title]`, sous la page `all_news` servie a `/actualites` — seul type a porter un prefixe (cf. [ADR-014](../.claude/decisions/014-titre-unique-porte-par-le-title.md)).
  - La date sort « 17 aout 2026 » depuis la bascule du site en francais (2026-08-17).
- **Cas limites du detail** `[VERIFIE 2026-08-19]` :
  - Actualite **sans legende** → pas de `<figcaption>` orphelin.
  - Actualite **sans bloc** → c'est l'enveloppe des champs du node qui pose l'ecart au pied de page (deux paddings de gabarit ne s'additionnent pas).
  - Visuel **en portrait** → le rendu est tres haut, sans debordement : consequence assumee du « sans recadrage ».
- [ ] Une actualite dispose d'une fonction publier / ne pas publier en back-office.

---

### F9 : Pages editoriales « Drive Matic » & FAQ

**Criteres d'acceptation** :
- [ ] Pages composables via Paragraphes (F1) : Qui sommes-nous, Nos ateliers, Recherche & developpement, Savoir-faire et certifications.
  - **Mise en oeuvre** : type `corporate`, 9 paragraphes autorises (dont `triptych`, `history`, `image_centered`, `video_centered`). Corps de texte masque a l'affichage (source de la meta description).
- [ ] FAQ : accordeons (fermeture du precedent a l'ouverture).
  - **Mise en oeuvre** : node `faq` (titre, corps, metatags) + fragments `question` (question, reponse, lien opt., fichier opt., **categorie obligatoire** → taxonomie `categories`). La Vue `faq` filtre par categorie avec un **filtre expose BEF rendu en liens**, et ses lignes sont enveloppees dans le SDC `accordion` : le comportement de fermeture du precedent est celui de F1, **sans JS supplementaire**. Le formulaire expose reste hors de l'accordeon. Categorie inconnue ou sans resultat → « Aucune question ne correspond a cette categorie. ».
- [ ] Frise « Notre histoire » (`history`) : en-tete titre + **paire de fleches**, filet pointille, entrees defilant horizontalement ; l'entree suivante est visible en **apercu coupe** a droite. Chaque entree = titre + description **puis** le visuel 16:9 (les visuels ne sont pas alignes entre colonnes : chacun suit son propre texte).
- [ ] **Pas de boucle** : en bout de course, la fleche concernee reste en place mais devient inerte et atenuee (l'en-tete ne se reorganise pas).

**Cas limites** :
- Une seule entree → pas de slideshow : les fleches restent masquees.

---

### Formulaires publics

### F10 : Formulaire de contact (devis / SAV / question)

**Trigger** : Quand un visiteur soumet le formulaire de contact.

**Action** :
1. Il choisit le type de demande : **demande de devis**, **SAV**, ou **question**.
2. Le formulaire adapte ses champs au type choisi.
3. Il coche l'autorisation d'utilisation des donnees, resout le captcha et envoie.

**Resultat attendu** : Demande enregistree et double e-mail envoye (accuse a l'internaute + notification a `info@drivematiclegrand.com`).

**Criteres d'acceptation** :
- [ ] Champs identite communs : « Vous etes » (concession / auto-ecole / taxi / particulier), nom d'entreprise (obligatoire sauf particulier), adresse, complement, CP, ville, civilite, prenom, nom, tel, e-mail.
- [ ] **Demande de devis** : marque, modele, motorisation (manuelle / automatique / automatique hybride), n° de chassis + type de chassis (infobulles « carte grise »), message.
- [ ] **SAV** : marque, modele, motorisation, n° de chassis, 1 document optionnel (PDF/JPG, 5 Mo max, supprimable), message.
- [ ] **Question** : message seul.
- [ ] Les champs marques par `*` sont obligatoires ; captcha requis ; case d'autorisation des donnees requise.
- [x] Les e-mails suivent les modeles des specs (objet, expediteur `no-reply`, contenu) pour chacun des 3 cas.

- **Mise en oeuvre — integration du 2026-08-18** ([ADR-015](../.claude/decisions/015-habillage-des-formulaires.md)) : deux groupes symetriques (`identite` « Vous etes », `demande` « Votre demande concerne »), dont les listes deroulantes portent le libelle « Selectionner » comme la maquette. Les **infobulles « carte grise »** sont remplacees par une **modale illustree** (SDC `help-modal`) montrant le certificat d'immatriculation, la case entouree. La mention « *Champs obligatoires » est rendue en tete par Webform et **descendue en pied** par `order`, l'ordre de tabulation n'etant pas affecte (texte non focusable).
- **Mise en oeuvre — integration du 2026-08-20** (maquettes 433-7637/438-9060/438-9465) : la ligne « adresse + visuel » au-dessus du formulaire, jusque-la non mise en page, est desormais le SDC `contact-intro` (2 colonnes). Le visuel du node `contact` (champ `field_photo`) impose depuis cette date un crop **16:9** (addendum [ADR-018](../.claude/decisions/018-images-locales-par-paragraphe.md)) — auparavant sans crop. Marge sous le formulaire alignee sur `--dm-space-page` (meme rythme que le pager avant le footer, ADR-013), pas sur le pixel exact de la maquette (qui varie selon l'etat devis/SAV/question).
- **Mise en oeuvre — integration du 2026-08-21** ([ADR-022](../.claude/decisions/022-gabarit-email-webform.md), maquettes Figma « Modele Email... » 810:9388 et suivants) : les 6 e-mails (accuse + notification, x3 cas) suivent desormais ce modele — logo (PNG, compatibilite clients mail), sans encadre gris ni centrage (demande explicite), ordre commun du bloc identite (Statut/Entreprise/Nom/Adresse/E-mail/Tel, separateur `-` avant CP+ville). La piece jointe SAV est desormais reellement jointe (`attachments: true`), cf. resolution ci-dessous.

**Cas limites** :
- « Vous etes = particulier » → le nom d'entreprise n'est pas obligatoire.
- Document SAV hors format/taille → rejet avec message d'erreur. `[INFERE]`
- ⚠️ **Sans `file_private_path` configure, le champ document n'existe pas** : Drupal retire silencieusement tout element en `#uri_scheme: private`, sans log ni message. Reglage a poser sur **chaque** environnement (`settings.php` n'etant pas versionne).
- Le plafond de taille annonce (5 Mo) est **borne par `upload_max_filesize`** du PHP qui sert le site. ⚠️ Un controle fait via `drush runserver` interroge le PHP **CLI**, pas celui du vhost : il ne dit pas ce que voit un visiteur.

> NB : cette « demande de devis » publique est un **e-mail de demande** (pas de chiffrage) — distincte du devis chiffre du configurateur partenaire (F14/F15).

> ✅ **Resolu le 2026-08-21** : la piece jointe du SAV est desormais jointe a l'e-mail interne **et** a l'accuse demandeur (`attachments: true` sur `sav_interne` et `sav_demandeur`) — auparavant `attachments: false` sur les deux, malgre le plan F10 §4.

---

### F11 : Formulaire « Devenir partenaire »

**Trigger** : Quand un prospect demande a devenir partenaire.

**Action** : Il renseigne entreprise, adresse, identite, contact, la question « Etes-vous amenageur qualifie vehicule auto-ecole ? » (Oui/Non), un message ; coche l'autorisation ; resout le captcha ; envoie.

**Resultat attendu** : Message de confirmation a l'ecran + double e-mail (accuse a l'internaute + notification a `info@drivematiclegrand.com`). **Aucun compte n'est cree automatiquement** (cf. decision #4).

**Criteres d'acceptation** :
- [ ] Champs obligatoires marques `*` ; captcha ; case d'autorisation.
- [ ] Message de confirmation affiche : « Votre demande de partenariat a bien ete envoyee !... ».
- [x] E-mails conformes aux modeles des specs.

- **Mise en oeuvre — integration du 2026-08-20** (maquette 438-9838) : le webform heritait deja de l'habillage generique (ADR-015) mais sans ses `#wrapper_attributes` — la grille (Civilite/Prenom/Nom sur leur propre ligne, message sur 2 colonnes, consentement pleine largeur) et l'ecart titre -> formulaire (absent, faute d'un bloc intro comme sur `contact`) ont ete corriges. Les radios Oui/Non, jamais stylees ailleurs sur le site (seul champ `#type: radios`), ont recu leur propre traitement (rond 20px, cote-a-cote via `#options_display: side_by_side`).
- **Mise en oeuvre — integration du 2026-08-21** ([ADR-022](../.claude/decisions/022-gabarit-email-webform.md), maquette Figma 810:10324/810:10435) : les 2 e-mails reprennent le meme gabarit que le formulaire de contact. Ce formulaire n'a pas de champ « Vous etes » (pas de ligne Statut) ; la ligne Adresse, absente de la maquette de ces 2 e-mails, a ete ajoutee par coherence (les 4 champs adresse sont obligatoires au formulaire).

---

### Espace partenaire (authentifie)

### F12 : Authentification & gestion de compte

**Trigger** : Quand Drive Matic cree un compte partenaire en back-office, ou quand un partenaire gere son compte.

**Action** :
1. A la creation du compte en back-office, le site envoie automatiquement un e-mail d'activation avec lien de definition du mot de passe (valable **72 h**).
2. Le partenaire definit son mot de passe, se connecte, et accede a son espace cloisonne.
3. Il peut consulter/modifier « Mes informations personnelles », utiliser « Mot de passe perdu », se deconnecter, ou supprimer son compte.

**Resultat attendu** : Acces authentifie et cloisonne a l'espace partenaire ; autorisation re-verifiee cote serveur (decision #5).

**Criteres d'acceptation** :
- [ ] L'e-mail d'activation suit le modele des specs ; lien valable 72 h ; au-dela, passage par « Mot de passe perdu ».
- [ ] Drive Matic peut modifier, suspendre et supprimer un compte en back-office.
- [ ] L'adresse de **facturation** est affichee mais **non modifiable** en front (mise a jour via back-office uniquement).
- [ ] « Supprimer mon compte » demande une confirmation, puis **supprime le compte** tout en **anonymisant les documents associes** (devis/commandes conserves de maniere anonyme, pour la tracabilite et la conservation legale/comptable).

**Cas limites** :
- Acces direct a une URL partenaire par un anonyme → refus cote serveur (jamais de fuite de donnees partenaire).
- Compte suspendu → connexion refusee. `[INFERE]`

---

### F13 : Tableau de bord partenaire

**Trigger** : Quand un partenaire se connecte.

**Resultat attendu** : Affichage automatique du tableau de bord.

**Criteres d'acceptation** :
- [ ] Affiche : bouton « Creer un nouveau devis », nombre de devis a finaliser, nombre de devis et commandes en cours, visuel de valorisation du configurateur.

---

### F14 : Configurateur (3 etapes)

**Trigger** : Quand un partenaire cree ou modifie un devis.

**Action** :
1. **Configuration** : selection vehicule (marque / modele / type) + equipements avec quantites (retrovision exterieure 1-2, retrovision interieure 1, telecommande VOR 1, double pedalier 1) + nombre de vehicules identiques. Possibilite d'ajouter plusieurs configurations au meme devis (**max 10**).
2. **Devis** : recapitulatif tableau par configuration (tarif catalogue unitaire HT, tarif remise HT, quantites, totaux) ; totaux par vehicule et par configuration ; total general (HT, remise, remise HT, TVA 20 %, TTC).
3. **Livraison** : adresse de facturation (non modifiable en front) ; choix/ajout/modification d'adresse de livraison (persistee en back-office).

**Resultat attendu** : Un devis chiffre selon les conditions commerciales du partenaire, pret a finaliser ou commander.

**Criteres d'acceptation** :
- [ ] Le tarif remise applique = remise commerciale du partenaire (back-office), **ou** remise « exceptionnelle » saisie par Drive Matic pour ce devis precis.
- [ ] Les tarifs **n'incluent pas** les frais de livraison (integres hors site, dans le bon de commande).
- [ ] Chaque configuration est modifiable / supprimable dans l'etape Devis.
- [ ] Une adresse de livraison ajoutee/modifiee en front est automatiquement enregistree en back-office.
- [ ] Calculs TVA a 20 %.

**Cas limites** :
- Tentative d'ajouter une 11e configuration → bloquee (max 10).
- Quantite retrovision exterieure hors bornes 1-2 → refus. `[INFERE]`

---

### F15 : Devis (onglets, cycle de vie, commande)

**Trigger** : Quand un partenaire gere ses devis depuis « Mes devis ».

**Action** : Il consulte 3 onglets et agit sur chaque devis selon son statut.

**Resultat attendu** : Suivi complet du cycle de vie devis → commande → archive.

**Criteres d'acceptation** :
- [ ] Numerotation devis : `WAAAAMMJJ-001`.
- [ ] **Onglet « A finaliser »** : devis non commandes ; dernier en tete ; colonnes date / marque / modele / type / equipements / statut ; pagination 10 ; fonctions **Modifier, Dupliquer, Supprimer** (avec confirmation).
- [ ] **Onglet « En cours »** : devis finalises non commandes **et** commandes ; ajoute colonnes n° devis + montant HT ; statuts « A commander » ou « Commande le jj/mm/aaaa ».
  - Devis non commande : **Commander** (renvoi a l'etape Devis du configurateur), Modifier, Dupliquer, Supprimer.
  - Devis commande : Dupliquer, Archiver (confirmation).
- [ ] **Commander** : enregistre la date de commande, affiche « Felicitations, votre commande a bien ete enregistree... », envoie l'e-mail de confirmation **avec PDF du devis** (au partenaire + `info@drivematiclegrand.com`).
- [ ] **Onglet « Archives »** : devis archives (manuellement ou **auto a 30 jours** pour les commandes) ; dernier en tete ; **Telecharger le devis (PDF)** ; un devis archive **ne peut plus etre duplique**.
- [ ] Un devis peut etre archive manuellement qu'il soit commande ou non.

**Cas limites** :
- Remise supplementaire : le partenaire appelle Drive Matic ; DM saisit une remise temporaire par ligne en back-office tant que le devis n'a **pas** ete commande (statut « a commander ») ; le taux de remise par defaut du client reste inchange.
- Devis commande depuis > 30 jours → archivage automatique.

---

### Back-office & transverse

### F16 : Gestion des partenaires & conditions commerciales

**Trigger** : Quand Drive Matic administre un partenaire en back-office.

**Resultat attendu** : Comptes et conditions commerciales pilotes cote serveur.

**Criteres d'acceptation** :
- [ ] Creation / modification / suspension / suppression des comptes partenaires.
- [ ] Gestion des conditions commerciales : **taux de remise par defaut** par partenaire + **remise exceptionnelle temporaire par ligne** sur un devis donne.
- [ ] Donnees partenaire structurees selon le fichier « partenaires » (structure a integrer). `[A PRECISER]` : champs exacts du fichier.

---

### F17 : Referentiel vehicules & catalogue produits

**Criteres d'acceptation** :
- [ ] Referentiel marques / modeles / types alimente depuis le fichier Excel fourni (aussi utilise par les listbox du formulaire de contact).
- [ ] Catalogue produits (auto-ecole & PMR) avec tarifs catalogue HT, utilises par le configurateur.
- [ ] `[A PRECISER]` : modalites d'import/mise a jour du fichier Excel (import manuel, script, saisie back-office).

---

### F18 : Analytics, consentement cookies & SEO

**Criteres d'acceptation** :
- [ ] Integration d'un outil d'analytics — **outil a trancher (Matomo ou GA4)** `[A PRECISER]`.
- [ ] Bandeau de consentement cookies (CMP) — **outil a trancher (Axeptio, tarte au citron ou similaire)** `[A PRECISER]`.
- [ ] Accordeons SEO en home ; metadonnees editables ; **plan de redirection** a realiser par Passerelle.
  - **Metadonnees editables — fait** : chaque node public affiche par defaut `titre | nom du site` en balise title et un extrait du corps de texte en description ; un champ **« Balises meta »** permet de surcharger au cas par cas (vide = calcul automatique). Cf. [ADR-010](../.claude/decisions/010-metatags.md), amende par [ADR-014](../.claude/decisions/014-titre-unique-porte-par-le-title.md) : la balise title suit le `title` du node, redevenu titre unique.
  - `[OUVERT]` **Longueur des descriptions** non bornee : l'extrait suit la troncature du champ corps de texte, sans garantie de rester sous ~160 caracteres.
  - **Sitemap — fait** : indexation **opt-in par bundle**. Les 12 nodes publics sont inclus, les 3 fragments et le bac a sable exclus (par absence de reglage). L'accueil figure en **lien personnalise** sur `/` (priorite 1.0) plutot qu'en reglage de bundle, pour eviter un doublon avec `/node/<id>`.
  - `[OUVERT]` **URL de base du sitemap** : `simple_sitemap.settings.base_url` est vide. En CLI, les URL generees sortent donc en `http://default/`. A verifier en preprod : si l'hote n'est pas correct, renseigner `base_url`.
- [ ] Liens vers les reseaux sociaux (Instagram, TikTok, LinkedIn, YouTube).

## 5. Modele de donnees

> Vue fonctionnelle des entites (le mapping technique Drupal — types de contenu, entites custom, taxonomies, Webform, Paragraphes — releve du README/ADR). Annotations : `[INFERE]` a valider.

### Entites principales

| Entite | Champs cles | Relations |
|--------|-------------|-----------|
| **Partenaire** (compte utilisateur + profil) | Entreprise, adresse de facturation, taux de remise par defaut, statut (actif/suspendu), donnees du fichier « partenaires » `[A PRECISER]` | 1 → N adresses de livraison ; 1 → N devis |
| **Adresse de livraison** | Libelle, adresse, CP, ville | N → 1 partenaire (gerees back-office + ajout/maj front) |
| **Devis** | N° `WAAAAMMJJ-001`, statut (`a finaliser` / `a commander` / `commande le jj/mm/aaaa`), date de creation, date de commande, archive (bool) + date, totaux (HT, remise HT, TVA 20 %, TTC), remise exceptionnelle eventuelle | N → 1 partenaire ; 1 → 1..10 configurations ; N → 1 adresse de livraison |
| **Configuration** (groupe vehicule d'un devis) | Vehicule (marque/modele/type/motorisation), nombre de vehicules identiques | N → 1 devis ; 1 → N lignes d'equipement |
| **Ligne d'equipement** | Produit, quantite par vehicule, quantite totale, tarif catalogue HT, tarif remise HT | N → 1 configuration ; N → 1 produit |
| **Produit / Equipement** | Nom, categorie (auto-ecole / PMR), tarif catalogue HT, bornes de quantite, fiche produit editoriale | Configurables au devis : retrovision ext. (1-2), retrovision int. (1), telecommande VOR (1), double pedalier (1) |
| **Referentiel vehicule** | Marque, modele, type, motorisation | Alimente le configurateur + les listbox du formulaire contact (source : fichier Excel) |
| **Marque partenaire** | Nom, logo | Affichage ordre alpha (distinct des marques de vehicules) |
| **Actualite** | Titre, date, visuel principal, corps (paragraphes), publie/non | — |
| **Page de contenu** | Composee de Paragraphes (F1) ; templates specifiques : home, solutions, produit, docs, corporate | 1 → N paragraphes |
| **Document / media** | Fichier, nom, format, poids (auto) | Mediatheque, reference par paragraphes/produits/actualites |
| **Soumission de formulaire** (Webform) | Contact (devis / SAV / question), devenir partenaire — **stockees + e-mail** | — |

### Contenu editorial (public)
Le modele de contenu editorial (types de contenu, taxonomie, mapping paragraphes) est acte dans [ADR-002](../.claude/decisions/002-types-de-contenu.md) et detaille dans `docs/content-model.md` : **12 nodes publics** (`homepage`, `transform`, `product`, `faq`, `documents`, `corporate`, `brands`, `contact`, `partner`, `legals`, `news`, `all_news`), **2 nodes « fragments »** sans page publique (`question`, `brand` — hors sitemap, URL bloquee ; le fragment `document` a ete supprime le 2026-08-18), **1 taxonomie** (`categories`). **Livre en totalite.** Conventions transverses : champ « lien » interne/externe + cible d'onglet ; « fichier telechargeable » avec nom/format/poids ; **titre unique porte par le `title`**, qui alimente l'affichage, l'alias, le fil d'Ariane et la balise title ([ADR-014](../.claude/decisions/014-titre-unique-porte-par-le-title.md), qui remplace l'ADR-011) ; metatags (body→description) ; sitemap = nodes inclus / entites exclues.

### Referentiel vehicules (partage)
Trois taxonomies reutilisables (cf. [ADR-003](../.claude/decisions/003-referentiel-vehicules.md)), partagees par le webform contact (F10) et le configurateur (F14/F17) : `vehicle_brand` (31 marques), `vehicle_model` (124 modeles ; champs `field_brand` + `field_motorisations`), `motorisation` (4 : Manuelle, Automatique, Hybride, Électrique). Vocabulaires + champs versionnes ; termes = contenu (seeds depuis l'Excel, a recreer en prod).

### Regles de coherence
- Un devis porte 1 a **10** configurations maximum.
- Le tarif remise d'une ligne = remise par defaut du partenaire **ou** remise exceptionnelle saisie par Drive Matic pour ce devis (n'altere pas le taux par defaut).
- Un devis **commande** est archive automatiquement a 30 jours ; un devis **archive** n'est plus duplicable (PDF toujours telechargeable).
- Les frais de livraison ne sont **pas** stockes dans le devis (integres hors site au bon de commande).

## 6. Interface et design

### Patterns de navigation
- **Menu principal multi-niveaux** (niveaux 1 et 2), avec sous-menu authentifie pour l'espace partenaire.
- **Fil d'Ariane** present partout **sauf** en home page.
- **Footer** riche : coordonnees, solutions auto-ecole/PMR, assistance (contact, FAQ), reseaux sociaux, liens legaux.
- Comportement responsive attendu (audience grand public + exigence RGAA/WCAG AA). `[INFERE]`

### Structure de la page
- **Systeme de Paragraphes** (~13 modeles, cf. F1) pour la composition editoriale libre.
- **Templates specifiques** (non librement composables) : home page, pages solutions (auto-ecole / PMR), pages produit, page documentations, liste + detail d'actualite, marques partenaires, formulaires, espace partenaire (tableau de bord, configurateur, devis).
- **Composants recurrents** : CTA selectionnables ; accordeons (SEO en home, FAQ) avec fermeture du precedent a l'ouverture ; carrousel de marques (ordre alpha, fleches) ; bloc « swipe » produit (max 5) ; affichage document = nom + format + poids (auto).
- **Composants front en SDC** (Single Directory Components, cf. decision #10) : CSS et Twig co-localises et scopes par composant.
- **Breakpoints responsive** (valides) : 6 paliers, multiplicateurs 1x/2x ; ils pilotent les image styles responsives et le comportement des SDC.

| Palier | Media query | Poids |
|--------|-------------|-------|
| xs | `(max-width: 575px)` | 0 |
| sm | `(min-width: 576px) and (max-width: 767px)` | 1 |
| md | `(min-width: 768px) and (max-width: 991px)` | 2 |
| lg | `(min-width: 992px) and (max-width: 1199px)` | 3 |
| xl | `(min-width: 1200px) and (max-width: 1439px)` | 4 |
| xxl | `(min-width: 1440px)` | 5 |

Materialisation dans `drive_matic.breakpoints.yml` (prefixe = machine name du theme ; les tirets sont interdits dans les machine names Drupal, d'ou `drive_matic.*`) :

```yaml
drive_matic.xs:
  label: xs
  mediaQuery: '(max-width: 575px)'
  weight: 0
  multipliers:
    - 1x
    - 2x
drive_matic.sm:
  label: sm
  mediaQuery: '(min-width: 576px) and (max-width: 767px)'
  weight: 1
  multipliers:
    - 1x
    - 2x
drive_matic.md:
  label: md
  mediaQuery: '(min-width: 768px) and (max-width: 991px)'
  weight: 2
  multipliers:
    - 1x
    - 2x
drive_matic.lg:
  label: lg
  mediaQuery: '(min-width: 992px) and (max-width: 1199px)'
  weight: 3
  multipliers:
    - 1x
    - 2x
drive_matic.xl:
  label: xl
  mediaQuery: '(min-width: 1200px) and (max-width: 1439px)'
  weight: 4
  multipliers:
    - 1x
    - 2x
drive_matic.xxl:
  label: xxl
  mediaQuery: '(min-width: 1440px)'
  weight: 5
  multipliers:
    - 1x
    - 2x
```

### Rythme et espacement

Systeme acte dans [ADR-013](../.claude/decisions/013-espacement-et-unites.md),
porte par trois tokens :

| Token | Valeur | Role |
|---|---|---|
| `--dm-space-element` | 24px | ecart entre les elements d'un meme bloc |
| `--dm-space-block` | 32px | respiration verticale d'un bloc, donc **64px** entre deux blocs |
| `--dm-gutter` | 40px | gouttiere laterale |

La gouttiere est toujours un **`padding-inline`** : `margin-inline: auto` ne fait
que centrer une largeur plafonnee et ne garantit aucun ecart au bord en dessous
de ce plafond. Exceptions dictees par le design : gouttiere d'un seul cote quand
une piste de slideshow deborde volontairement, bloc pleine largeur quand le fond
court d'un bord a l'autre.

Deux tokens de gabarit completent ces trois-la :

| Token | Valeur | Role |
|---|---|---|
| `--dm-space-page` | 49px | rythme vertical de la charpente de page (au-dessus du titre, autour d'un filtre expose, sous une liste, avant le pied de page) |
| `--dm-content-column` | 900px | largeur de la colonne de contenu, **retunable par gabarit** — 960px sur le detail d'une actualite (cf. [ADR-016](../.claude/decisions/016-colonne-de-contenu.md)) |

**Unites : espacement en `px`, typographie en `rem`.** Les tailles de police
suivent ainsi la preference du navigateur (WCAG 1.4.4, decision #8) sans que la
mise en page bouge : a 20px de police racine, le H1 passe de 45 a 56,25px et le
corps de 16 a 20px, gouttiere et padding inchanges.

### Charte graphique
Source : maquettes Figma Passerelle (fichier `ZmmVBSOWSsHVkok6EU2Ays` — Drive Matic Legrand ; le logotype vit dans le meme fichier). Tokens extraits :

**Couleurs**

| Token | Hex | Usage |
|-------|-----|-------|
| Bleu acier | `#2F3A45` | Couleur principale (footer, aplats sombres) |
| Noir anthracite | `#1A1A1A` | Textes titres, elements sombres |
| Red | `#AA0000` | Accent / CTA (« Demander un devis »), statut « a finaliser » |
| Gris clair | `#E8E8E8` | Fonds de sections |
| Gris metallise | `#B5B5B5` | Bordures, elements secondaires |
| Gris texte | `#666666` | Texte courant secondaire |
| Blanc | `#FFFFFF` | Fonds, texte sur aplats sombres |

Couleurs de statut des devis (cf. F15) : rouge « a finaliser », orange « a commander », vert « commande ».

**Typographie**

| Style | Police | Taille / interligne |
|-------|--------|---------------------|
| Titre H1 | Exo 2 Bold (700) | 45 / 58 |
| Titre H2 | Exo 2 Bold (700) | 30 / 44 |
| Titre H3 | Exo 2 Bold (700) | 22 / 32 |
| Corps | Inter Regular (400) | 16 / 28 |
| Emphases | Inter Bold (700) | 14, 15, 16 |

Effet titres : ombre portee `drop-shadow rgba(0,0,0,0.35)`, rayon 25. Polices **Exo 2** (titres) et **Inter** (corps) — a **auto-heberger** (pas de CDN Google Fonts) pour la conformite RGPD. `[INFERE]`

Le theme (custom autonome via starterkit, decision #7) applique ces tokens, dans le respect du niveau **RGAA / WCAG 2.1 AA** (decision #8 : contrastes, navigation clavier, semantique).

## 7. Milestones

Perimetre livre en **une seule version (V1)** couvrant l'ensemble des features.

| Milestone | Features incluses | Critere de completion |
|-----------|-------------------|-----------------------|
| **V1** | F1 a F18 (site public complet + espace partenaire : configurateur, devis, back-office partenaires) | Toutes les features recettees selon leurs criteres d'acceptation ; scenarios E2E (docs/E2E_SCENARIOS.md) au vert ; `npm run lint`, `npm run format:check` et `npm test` passent ; conformite RGAA/WCAG 2.1 AA verifiee ; cloisonnement des donnees partenaires valide cote serveur |

> Elements a trancher avant/pendant la V1 (cf. `[A PRECISER]`) : outils analytics (Matomo/GA4) et consentement cookies ; champs du fichier « partenaires » ; modalites d'import du referentiel vehicules.

### Bascule linguistique (2026-08-17)

Le site est passe en **francais**, seule langue, sans traduction de contenu
(decision #6). `language` + `locale` installes, francais par defaut, 15 309
chaines importees depuis 27 projets ; les traductions de configuration vivent
dans `config/sync/language/fr/`.

Deux pieges rencontres, a connaitre avant de rejouer l'operation sur un autre
environnement :

1. **Le site devient inaccessible si un service de surcharge de configuration
   injecte des dependances.** `config.factory` devient une dependance du
   traducteur de chaines des que `locale` est installe : toute
   `ConfigFactoryOverride` qui injecte un service referme la boucle et le
   conteneur refuse de se construire (plus une page, plus de bootstrap Drush).
   Corrige sur `drivematic_home`. Consequence secondaire : l'installation de
   `locale` s'etait interrompue en cours de route, laissant le module enregistre
   **sans aucune de ses tables ni sa configuration** — et sa desinstallation
   echouait pour la meme raison.
2. **La bascule casse toutes les URL.** Les alias sont enregistres avec le
   langcode du contenu : passes en `fr`, ils ne sont plus trouves pour du contenu
   reste en `en`, et chaque page retombe sur `/node/N`. Il faut repasser le
   contenu existant en francais (nodes, medias, paragraphes), ce qui regenere les
   alias, puis forcer ceux des nodes marques « alias defini manuellement »
   (`pathauto = 0`), que rien ne regenere.

`[OUVERT]` `pathauto.settings.ignore_words` reste une liste de mots-outils
**anglais** : les alias francais gardent « un », « en », « de ». Reglage
editorial, a arbitrer.

### Ecarts ouverts constates a l'implementation

| # | Ecart | Decision concernee |
|---|-------|--------------------|
| 1 | ~~Le site tourne en anglais~~ — **resolu le 2026-08-17** : `language` + `locale` installes, francais pose par defaut et seule langue du site, 15 309 chaines importees. Dates, poids de fichiers, barre d'administration et onglets locaux sont en francais. Deux pieges rencontres, decrits en section 7 (« Bascule linguistique »). | #6 (site en francais uniquement) |
| 2 | ~~La page d'accueil n'a aucun `<h1>`~~ — **resolu le 2026-08-18** ([ADR-014](../.claude/decisions/014-titre-unique-porte-par-le-title.md)) : les SDC `text_centered` et `image_full` portent une prop `heading_level`, mise a 1 pour le premier paragraphe titre des bundles a titre-heros. Un seul `<h1>` par page, verifie sur 10 types. | #8 (RGAA / WCAG 2.1 AA) |
| 3 | ~~Le titre de page est rendu apres le contenu, dans un `<aside>`~~ — **resolu le 2026-08-18** : le bloc `drive_matic_page_title` est passe en region `content`, poids -10, donc au-dessus du contenu. La region `sidebar_first` ne porte plus que le bloc d'aide. | #8 |
| 4 | **« Conforme » a ete confondu avec « integree »** — constate le 2026-08-18 sur `/actualites`. Les pages du chantier d'integration etaient validees sur leur **contenu** (bons blocs, bons textes, un seul `<h1>`, bons alias) sans qu'aucune **mesure de mise en page** ne soit relevee. Le bloc titre de page n'avait aucun CSS et la Vue `all_news` aucune enveloppe SDC. **Regle de recette qui en decoule** : une page n'est integree que si ses mesures ont ete relevees sur la maquette **et comparees au rendu**. Reprises sur ce protocole a ce jour : la liste d'actualites et les marques partenaires (2026-08-18), la FAQ et le detail d'une actualite (2026-08-19) — soit 4 des 13 pages du chantier. Suivi page par page dans `docs/active/maquette-integration/progress.md`. | #10 (SDC-first), decision #11 |
| 5 | ~~Le recadrage est optionnel~~ — **tranche le 2026-08-18** : obligatoire et **manuel**, effectue par l'editeur a l'import (cf. [ADR-004](../.claude/decisions/004-pipeline-images.md)). Le formulaire l'imposait deja ; le seul contournement etait la **creation programmatique**, d'ou provenaient tous les trous (aucune entite `crop_12_5` en base, donc 17 blocs `image_full` rendus au ratio de leur source). Audit remis a plat : 45 couples (fichier, ratio) conformes, 31 images verifiees sur les 29 pages publiques, 0 ecart. | #11 (gestion des images) |
| 6 | ~~La mediatheque reutilisable couvre tous les champs image~~ — **tranche le 2026-08-19** ([ADR-018](../.claude/decisions/018-images-locales-par-paragraphe.md)) : le recadrage Drupal est rattache au **fichier**, pas a l'usage — une meme image reutilisee dans deux paragraphes du meme ratio y est forcement cadree pareil. Les 9 paragraphes a ratio impose + `news.field_photo` (renomme) passent donc en **champ image local sans mediatheque** (upload direct par usage). Les champs sans ratio (`image_centered`, `image_text_100`, `product_characteristics`, `brand`, `contact`) restent en mediatheque, non concernes. **Addendum du 2026-08-20** : `contact` en sort a son tour — son visuel impose desormais un crop 16:9 (`field_photo`, meme mecanisme que `news`), a la demande de l'utilisatrice en integrant la maquette F10. Seul `brand` reste en mediatheque sans ratio impose. | #11 (gestion des images) |

### Prealables techniques (avant le developpement front)

Livrables a produire et acter (README/ADR) **avant** la production des templates :

1. **Etude prealable de gestion des images** (cf. decision #11), etablissant :
   - les **ratios de crop** (definis par la bibliotheque de paragraphes — ADR-001 : **1:1, 16:9, 12:5**, + cas « sans crop » a largeur fixe) → declinaisons responsive a produire ;
   - la **liste des images cropables en back-office** a l'import ;
   - les **image styles** a appliquer + leurs **declinaisons responsive**, en accord avec la **liste des breakpoints** du site (definie en section 6) ;
   - la regle d'**optimisation WebP** appliquee a chaque image importee ;
   - l'organisation de la **media-library reutilisable** (stockage, nommage, reutilisation) — **exception** posee le 2026-08-19 ([ADR-018](../.claude/decisions/018-images-locales-par-paragraphe.md)) : les 9 paragraphes a ratio impose + `news.field_photo` sortent de la mediatheque (champ image local, un fichier par usage) ; les champs sans ratio y restent.
   - **Contrainte editoriale du recadrage** : le recadrage est **manuel** (effet `crop_crop`, sans fournisseur automatique). Un media **non recadre** pour un ratio donne ne declenche **aucune erreur** : il est simplement rendu au ratio de sa source, ce qui se voit a peine. Consequence a integrer au process de recette : chaque media doit etre recadre **pour chaque ratio auquel il sera rendu**, et un controle visuel de composant n'est concluant que sur des medias recadres.
2. **Mise en place du socle SDC** (cf. decision #10) : structure des composants, conventions de props/slots, fondations globales (reset, tokens, typographie), avant la production des templates.
3. **Etude de rationalisation des Paragraphes** (cf. F1) : les modeles decrits dans les specs ne sont pas tous necessaires et imposent parfois des contraintes techniques non justifiees. Recouper chaque modele avec les **maquettes validees** (verite visuelle) pour ne retenir que l'utile, ecarter le superflu, **puis optimiser** la bibliotheque (mutualisation, variantes via props plutot que duplication, mapping vers SDC). Livrable a acter (ADR/README) avant la production des paragraphes.
