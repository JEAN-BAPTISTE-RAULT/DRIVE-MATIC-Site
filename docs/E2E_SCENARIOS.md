# Scenarios E2E de non-regression

> Parcours utilisateur a rejouer pour valider le site DRIVE-MATIC. Un scenario = un parcours distinct. Reference : features F1-F18 de [docs/PRD.md](PRD.md). La matrice de couverture (fin de document) relie chaque scenario aux features.

## Prerequis

- [ ] Site Drupal 11 deploye (environnement de recette)
- [ ] Jeu de donnees seed : produits auto-ecole & PMR avec tarifs catalogue, referentiel vehicules (marques/modeles/types issus de l'Excel), marques partenaires (logos), actualites, pages de contenu, documents
- [ ] Au moins 1 **compte partenaire de test** avec conditions commerciales (taux de remise par defaut), adresse de facturation et >= 1 adresse de livraison
- [ ] 1 compte **administrateur** (theme Gin) pour le back-office
- [ ] Boite mail de test accessible pour verifier les e-mails (accuse au demandeur **et** notification `info@drivematiclegrand.com`)

---

## Contenus publics

## S1 — Navigation principale & fil d'Ariane

**Objectif** : Verifier la navigation multi-niveaux, le fil d'Ariane et le footer.

**Etapes** :
1. Depuis la home, ouvrir le menu et deployer chaque rubrique niveau 2 (Auto-ecole, Vehicule PMR, Drive Matic, Assistance), desktop puis mobile (tiroir).
2. Sur Drive Matic (desktop), verifier les 3 colonnes de liens separees par des filets (pas une liste a plat).
3. Ouvrir successivement 2 dropdowns differents et observer si les AUTRES boutons du nav se decalent.
4. Acceder a une page de niveau 2 (ex. « Double-pedalier »).
5. Observer le fil d'Ariane, puis parcourir les liens du footer (solutions, assistance, reseaux sociaux, liens legaux).

**Resultats attendus** :
- Le menu reflete l'arborescence du PRD ; les liens mènent aux bonnes pages.
- Chaque flyout desktop couvre toute la largeur de la page (pas seulement la largeur du bandeau du header) ; son contenu reste aligne avec le logo.
- Le dropdown Drive Matic affiche 3 colonnes de liens ; aucun bouton du nav ne bouge quand un autre dropdown s'ouvre ou se ferme.
- Le fil d'Ariane est present sur la page de contenu **et absent sur la home**, en desktop (>= 992px).
- En mobile (< 992px), le fil d'Ariane n'affiche **aucun lien** sur la page de contenu, mais l'ecart vertical vers le titre (ou le premier paragraphe sur un gabarit hero) reste identique a avant son masquage.
- Le footer expose coordonnees, solutions auto-ecole/PMR, assistance (contact, FAQ), reseaux sociaux et liens legaux.

**Mise en oeuvre (a rejouer) — fil d'Ariane stylise le 2026-08-21** ([ADR-023](../.claude/decisions/023-fil-ariane-style.md)) : liens gris, element courant en acier gras, separateur `»` gris metallise. Verifier sur une page **sans** bloc titre (ex. `/telecommande-vor-auto-ecole`, bundle `product`) que l'ecart sous le fil existe malgre tout (porte par le fil lui-meme, pas par le titre). En desktop, le bord gauche du fil doit tomber exactement sous le **D** du logo du header, a toutes les largeurs (verifie a 1440px et 1920px) — pas necessairement sous le titre de page, qui suit une autre colonne au-dela d'environ 980px de large.

**Mise en oeuvre (a rejouer) — fil d'Ariane masque en mobile le 2026-08-31** (addendum [ADR-023](../.claude/decisions/023-fil-ariane-style.md)) : sous 992px, `.breadcrumb ol` passe a `display: none` (le conteneur `.breadcrumb` reste present et garde son `padding-block`). Verifier sur `/faq` (page sans hero) que la liste est bien absente du rendu mobile et que l'espace entre le menu et le titre n'a pas change de hauteur par rapport a la version desktop du meme ecart.

---

## S2 — Home page

**Objectif** : Verifier l'affichage des blocs specifiques de la home.

**Etapes** :
1. Ouvrir la home page.
2. Parcourir : jumbos, bloc solutions auto-ecole & PMR, bloc configurateur, bloc actualites, carrousel marques, bloc fond gris, accordeons SEO.
3. Ouvrir un accordeon SEO puis un second.

**Resultats attendus** :
- Le bloc actualites affiche les **5 plus recentes** + lien « voir toutes ».
- Le carrousel marques defile (fleches) en **ordre alphabetique**.
- A l'ouverture du 2e accordeon SEO, le **1er se ferme**.

**Blocs home V4 (slideshows `jumbo_home` / `news_home` / `brands_home`)** :
- `jumbo_home` : slideshow si **2 ou 3** bannieres (visuel 16:9 + titre + CTA opt) ; **1 seule** banniere → pas de slideshow, fleches masquees ; ajout d'une **4e** banniere bloque (cardinalite 3).
- `jumbo_home` (integration maquette) : la piste est **calee a gauche sur le conteneur** (40px a 1440) et **deborde a droite** jusqu'au bord de la fenetre → la diapo suivante est visible en **apercu coupe** ; **points de pagination** sous le slider (actif rouge, clic = navigation) ; la fleche **« precedent » est absente sur la 1re diapo** et « suivant » sur la derniere (fleche en bout de course masquee, pas de boucle). Sans JS : diapos empilees pleine largeur, points **et** fleches masques.
- `news_home` : slideshow des **5** actualites les plus recentes (image 16:9 WebP responsive + titre + « Lire la suite » vers le detail) + lien « voir toutes » ; **0 actualite** → bloc vide propre.
- `news_home` (integration maquette) : titre **centre sur le conteneur** avec la **paire de fleches a droite** (bord droit aligne sur le conteneur) ; piste calee a gauche et **debordante a droite** → carte suivante en **apercu coupe** ; lien « voir toutes » en **bouton gris centre** sous la piste. Carte = visuel 16:9 arrondi + titre + « Lire la suite » (chevron rouge) — **ni date ni chapo**. Fleche en bout de course **atenuee et inerte** (l'en-tete ne se reorganise pas). **Points de pagination** sous la piste, ajoutes le 24/08 (meme mecanisme que `jumbo_home`). Sans JS : cartes empilees pleine largeur, fleches et points masques. **Depuis le 27/08, toute la carte est cliquable** (visuel + titre), pas seulement « Lire la suite » — lien etire (`::before` sur `.news-card__more`, `.news-card` en `position: relative`) ; le chevron glisse de 6px vers la droite au survol du lien. **Depuis le 31/08, le titre, les points de pagination et le bouton « voir toutes » sont reellement centres sur le vrai centre de page** (mesure via `getBoundingClientRect`, pas a l'oeil) — ils etaient decales de `gutter/2` (20px a 1440px) vers la droite, distinct du debordement de piste corrige le 20/08 (cf. historique).
- `brands_home` : slideshow de **toutes** les marques en **ordre alpha** (logo + nom) ; les logos ne sont **pas** cliquables (page canonique du fragment `brand` en **403**).
- `brands_home` (integration maquette) : titre **centre**, rangee de **tuiles carrees de 118px** (logo sans crop centre et contenu, bordure fine, coins arrondis) espacees de 16 ; **une fleche a chaque extremite** de la rangee, la piste demarrant apres la fleche gauche et **debordant a droite** (derniere tuile coupee) ; lien en **bouton gris centre** sous la rangee. Le **nom de la marque n'est pas visible** (la maquette ne montre que le logo) mais reste dans le DOM pour les lecteurs d'ecran — a verifier au lecteur d'ecran, pas a l'oeil. Fleche en bout de course **atenuee et inerte**. Sans JS : les tuiles **passent a la ligne** (et non en colonne), fleches masquees.
- Slideshows accessibles clavier ; sans JS, les diapositives s'empilent (tout visible) et les fleches restent masquees (amelioration progressive).

---

## S3 — Page solution (auto-ecole / PMR)

**Objectif** : Verifier le template de page solution et l'acces aux produits/configurateur.

**Etapes** :
1. Ouvrir « Transformer un vehicule en auto-ecole ».
2. Cliquer sur un bloc produit, revenir, cliquer sur le bloc configurateur.
3. Ouvrir un accordeon FAQ.

**Resultats attendus** :
- Image 100 % largeur, bloc informations generales, 3 a 6 blocs produits avec liens, bloc configurateur, FAQ (fermeture du precedent a l'ouverture).

**Frise `history` (integration maquette — pages corporate type « Qui sommes-nous »)** :
- En-tete : titre a gauche, **paire de fleches a droite** (alignee sur le bord du conteneur), **filet pointille** en dessous qui court jusqu'au bord de la fenetre.
- Piste **calee a gauche sur le conteneur** et **debordante a droite** : l'entree suivante est visible en **apercu coupe**.
- Chaque entree : titre + description **puis** le visuel 16:9 arrondi. Les visuels ne sont **pas alignes** d'une colonne a l'autre (chacun suit la hauteur de son texte).
- **Bout de course** : la fleche concernee reste en place mais devient **inerte et atenuee** (pas de boucle) — contrairement a `jumbo_home` ou la fleche disparait.
- **Une seule entree** → pas de slideshow, fleches masquees. Sans JS : entrees empilees pleine largeur dans le conteneur, fleches masquees.

---

## S4 — Fiche produit (sans prix)

**Objectif** : Verifier le template produit et l'absence de prix en public.

**Etapes** :
1. Ouvrir une fiche produit (ex. « Telecommande VOR »).
2. Parcourir : argumentaires, swipe, caracteristiques techniques, cross-selling.
3. Telecharger une notice technique.

**Resultats attendus** :
- Bloc swipe limite a 5 elements ; cross-selling 1 a 5 produits avec liens.
- Notice/documentation affichent **nom + format + poids** : le nom est la **description saisie en BO**, le format et le poids sont calcules.
- **Aucun prix** n'est affiche sur la page produit publique.

**Bloc `product_cross` (integration maquette)** :
- Titre puis **grille de deux colonnes** de cartes de 440px espacees de 20 dans un conteneur de 900 centre ; la **3e carte passe a la ligne**.
- Carte = visuel **16:9 arrondi 16px** puis **une seule ligne cliquable** : le **titre** de la carte suivi du chevron rouge. Le **libelle du champ lien n'est pas affiche** (le titre en tient lieu), mais la **cible d'ouverture** saisie en BO est conservee — a rejouer en cochant « nouvel onglet ».

**Bloc `product_features` (integration maquette)** :
- Diapositives de **900px** espacees de 20, calees a gauche sur le conteneur et **debordantes a droite** → diapositive suivante en **aperçu coupe**.
- Chaque diapositive : visuel **16:9 arrondi 16px**, puis titre, description et **bouton gris** (le lien). Le champ **fichier** garde, lui, le bouton de telechargement global.
- Diapositive **video** : la miniature porte une **façade** — plaque blanche translucide + glyphe de lecture, **centres sur le visuel** ; l'iframe n'est injectee **qu'au clic** (perf + RGPD), accessible au clavier.
- **Fleche centree sur le visuel** (et non sur la diapositive, qui porte aussi le texte) et posee a 40px du bord de la fenetre ; elle **disparait en bout de course** (pas de boucle), comme sur `jumbo_home`.
- Sans JS : les diapositives s'empilent pleine largeur, fleches masquees.

**Bloc `product_characteristics` (integration maquette)** :
- **Bandeau anthracite pleine largeur** (deborde le conteneur de la page, blanc au-dessus et en dessous).
- Visuel produit a gauche (**sans crop**, 380px de large), colonne de droite = titre blanc + caracteristiques en **2 colonnes** : libelle gris metallise puis valeur blanche.
- Les **deux boutons de telechargement** (contour blanc + icone) sont alignes sur les deux memes colonnes et portent le **libelle saisi en back-office** (description du fichier) suivi du **format et du poids calcules** — et non deux boutons « Télécharger » identiques.
- **A rejouer en BO** : le libelle est le champ « Libellé du bouton de téléchargement » saisi sous le fichier ; il est **obligatoire** des qu'un document est joint (enregistrer sans lui est refuse), et un champ fichier laisse **vide** n'en demande pas.
- Un document **absent** ne laisse pas de bouton vide ; les deux absents supprime la rangee.

---

## S5 — Documentations & telechargement

**Objectif** : Verifier la page documentations et le telechargement.

**Etapes** :
1. Ouvrir la page Documentations.
2. Verifier les listes Auto-ecole et PMR, telecharger un document.

**Resultats attendus** :
- Documents presentes avec nom/format/poids, dans l'ordre defini en back-office.
- Le telechargement fonctionne.

**Integration maquette (Figma `398-12119`, 2026-08-19)** :
- Chapo centre dans la colonne de contenu (SDC `page-intro`), puis deux sections en liste **zebree** (SDC `documents-list`) : fond gris clair un rang sur deux, radius 8px.
- Chaque fichier est une **ligne entierement cliquable** (decision de l'utilisatrice) : nom + format + poids a gauche, « Telecharger » + icone a droite — un seul lien par ligne, pas un second element interactif imbrique.
- Ecart entre les deux sections et avant/apres la liste sur le rythme unique du gabarit (`--dm-space-page`, `--dm-space-block`), pas les valeurs brutes de la maquette (convention transverse, cf. CLAUDE.md).

**Mise en oeuvre (a rejouer)** :
- La page porte **deux champs Fichier a cardinalite illimitee** (`field_documents_school`, `field_documents_pmr`) — **pas** des references vers un node `document` (type supprime le 2026-08-18). Les **titres de section restent en dur** dans `node--documents.html.twig` (« Auto-ecoles », « PMR »), pas pilotes par un libelle de champ. Reordonner les fichiers en BO doit reordonner la liste.
- **Section vide** → ni titre de section, ni espace mort (le SDC `documents-list` ne recoit que des sections non vides, filtrees en preprocess).
- Le libelle de chaque ligne est la **description du fichier** (ADR-009), saisie a l'upload ; repli sur le nom du fichier si vide. Format et poids restent calcules.
- ⚠️ Les deux champs ne passent plus par leur formatter (`file_default`) : ils sont **masques** dans le view display, leur rendu vient de `drive_matic_preprocess_node()`. Modifier la description ou remplacer un fichier en back-office doit se refleter **sans** `drush cr` (cache tags du fichier reattaches explicitement).

---

## S6 — Marques partenaires

**Objectif** : Verifier l'affichage des marques.

**Etapes** :
1. Ouvrir la page Marques partenaires.

**Resultats attendus** :
- Liste de logos en **ordre alphabetique** + bloc informations generales.

**Mise en oeuvre (a rejouer)** :
- Les tuiles sont celles de la home, mais en **grille qui passe a la ligne** (SDC `brands-grid`) et non en piste defilante.
- Les logos ne sont **pas cliquables** : `/node/<id>` d'une marque repond **403**.
- Aucune marque publiee → « Aucune marque partenaire n'est publiee pour le moment. ».

---

## S7 — Actualites (liste + detail)

**Objectif** : Verifier liste, pagination et detail.

**Donnees de test** : > 10 actualites publiees + 1 non publiee.

**Etapes** :
1. Ouvrir la liste des actualites.
2. Verifier l'ordre (derniere publiee/modifiee en tete) et la pagination.
3. Ouvrir une actualite via « Lire la suite ».

**Resultats attendus** :
- **10 actualites par page** ; l'actualite **non publiee n'apparait pas**.
- Le detail affiche titre, date, visuel, contenu (paragraphes), documents/video eventuels.

**Mise en oeuvre (a rejouer)** :
- La liste vit a **`/actualites`**, les details a **`/actualites/<titre>`** — seul type a porter un prefixe d'URL.
- La ligne de liste (`news-teaser`) affiche **la date**, contrairement a la carte du bloc home (`news-card`) qui ne montre que visuel + titre.
- **Toute la ligne est cliquable depuis le 2026-08-21** (visuel, titre, date compris), pas seulement le lien « Lire la suite ». **Mecanisme corrige le 27/08** : le lien etire (`::before` sur `.news-teaser__more`) est ancre sur `.news-teaser__body` (pas sur `.news-teaser`, toute la ligne) — la colonne de texte (`1fr`) etant plus large que son contenu, l'ancien perimetre rendait cliquable le vide a droite d'un titre court, jusqu'au bord de la colonne de contenu. L'image, soeur du lien (pas descendante), reste couverte par un clic delegue en JS (`news-teaser.js`, amelioration progressive). Verifier un clic sur le visuel et sur le titre (doit naviguer), et un clic dans la marge a droite d'un titre court (ne doit **pas** naviguer), en desktop et mobile. `news-card` (bloc home) a recu le meme traitement le 27/08 (lien etire simple, sans le cas du vide de grille).
- Le lien **« Voir toutes les actualites »** de la home mene bien a `/actualites`.
- **0 actualite publiee** → « Aucune actualite n'est publiee pour le moment. », sans bloc casse.
- **Depublier une actualite** doit la retirer de la liste **sans vidage de cache**.
- ~~La date sort en anglais~~ — resolu avec la bascule linguistique du 2026-08-17.
- **Jeu de test en place depuis le 2026-08-18** : **32 actualites publiees**, soit **4 pages**. La pagination est donc verifiable pour de vrai (« Precedent » absent en page 1, « Suivant » absent en derniere, numeros cliquables).
- **Mise en page de la liste** (integration du 2026-08-18, maquette `438-10209`) : titre de page **centre** ; lignes dans une colonne de **1130** ; chaque ligne = visuel **325x183 (16:9, coins arrondis)** a gauche, puis titre, date et « Lire la suite » **aligne en bas** de la ligne ; **30** entre deux lignes. La pagination est centree, la page courante en **blanc sur pastille acier**, « Precedent »/« Suivant » en gris encadres d'un chevron.
- **Mise en page du detail** (integration du 2026-08-19, maquette `438-10665`) : titre **centre**, puis date **centree** en gris, visuel **sur toute la colonne** (coins arrondis 16) et legende **ferree en bas a droite**, corps de texte, puis les blocs. Une **seule colonne de 960** pour tout, y compris les blocs `text_left_aligned` et `video_centered` : les sept elements doivent commencer au **meme x**.
- ⚠️ **Le visuel du detail n'est PAS recadre** — contrairement a la vignette de la liste et de la home, qui restent en 16:9. Ne pas conclure au bug en voyant deux ratios differents pour la meme image. Controle : le `<img>` du detail doit sortir au ratio du **fichier source**, la vignette a **1,778**.
- ⚠️ **Le corps de texte ne figure pas sur la maquette** : il est affiche a la demande de l'utilisatrice, entre la legende et les blocs. Ne pas le retirer en s'appuyant sur la maquette.
- **Cas limites du detail** : actualite **sans legende** → pas de legende vide ; **sans bloc** → l'ecart au pied de page reste pose (49) ; visuel **en portrait** → rendu tres haut, sans debordement horizontal.
- ⚠️ **A rejouer en mesurant, pas a l'oeil** : c'est precisement ce controle qui manquait quand ces pages avaient ete declarees conformes (cf. PRD, ecart #4).

---

## S24 — FAQ & filtre par categorie

> Ajoute apres coup (numerotation a la suite, position thematique). Couvre le volet FAQ de F9,
> jusque-la sans scenario dedie.

**Objectif** : Verifier la page FAQ, son filtre par categorie et le comportement d'accordeon.

**Donnees de test** : au moins 2 questions par categorie (General, Auto-ecole, PMR).

**Etapes** :
1. Ouvrir la page FAQ.
2. Ouvrir une question, puis une seconde.
3. Cliquer sur le filtre « Auto-ecole », puis revenir a toutes les categories.

**Resultats attendus** :
- Toutes les questions publiees sont listees, la **derniere modifiee en tete**.
- A l'ouverture de la 2e question, la **1re se ferme** (meme comportement que les accordeons de F1).
- Le filtre est rendu en **liens** (un par categorie), **au-dessus** de l'accordeon et non dedans ; il filtre la liste sans quitter la page.
- Une categorie sans resultat → « Aucune question ne correspond a cette categorie. ».
- Une question est un **fragment** : `/node/<id>` repond **403** en anonyme.
- Sans JS : toutes les reponses restent lisibles (amelioration progressive), et les liens de filtre fonctionnent en pur GET.

---

## Formulaires publics

## S8 — Formulaire de contact : demande de devis

**Objectif** : Verifier la soumission « demande de devis » (publique), le stockage et les e-mails.

**Etapes** :
1. Ouvrir le formulaire de contact, choisir « demande de devis ».
2. Renseigner « Vous etes » (ex. auto-ecole), les champs identite, marque/modele/motorisation, n° et type de chassis, message.
3. Cocher l'autorisation, resoudre le captcha, envoyer.

**Resultats attendus** :
- Les champs `*` sont obligatoires ; l'**aide ⓘ** des champs chassis ouvre une **modale** montrant la case concernee du certificat d'immatriculation.
- La soumission est **enregistree en back-office** (consultable).
- Deux e-mails partent (accuse au demandeur + notification `info@`), conformes au modele « demande de devis ».

**Cas limites** :
- « Vous etes = particulier » → le nom d'entreprise n'est **pas** obligatoire.

**Mise en oeuvre (a rejouer)** — habillage du 2026-08-18, maquettes `433-7637` / `438-9060` / `438-9465` :
- Carte grise clair a coins arrondis, **trois colonnes** de champs, deux groupes titres (« Vous etes », « Votre demande concerne »), mention « *Champs obligatoires » **en bas** de la carte.
- **Sans JS** : le declencheur ⓘ disparait et la phrase d'aide s'affiche a sa place — le champ reste utilisable.
- **Les trois variantes** (devis / SAV / question) doivent etre rejouees : elles ne different que par les champs affiches, mais c'est la que se voient les ruptures de ligne de la grille.

**Mise en oeuvre (a rejouer) — integration du 2026-08-20** : au-dessus de la carte, une ligne 2 colonnes (adresse/horaires a gauche, visuel a droite) rendue par le SDC `contact-intro`. Le visuel impose desormais un **crop 16:9** obligatoire (auparavant sans crop) — verifier en back-office que le champ bloque l'enregistrement sans ce crop.

**Mise en oeuvre (a rejouer) — integration du 2026-08-21** ([ADR-022](../.claude/decisions/022-gabarit-email-webform.md)) : les 2 e-mails (accuse + notification) suivent desormais le modele Figma « Modele Email... » (810:9388/810:9541) — logo, sans encadre ni centrage, ordre Statut/Entreprise/Nom/Adresse/E-mail/Tel. Verifier avec et sans `complement` renseigne (pas de logique conditionnelle, tokens simples).

---

## S9 — Formulaire de contact : SAV avec piece jointe

**Objectif** : Verifier le cas SAV et l'upload de document.

**Etapes** :
1. Choisir « demande de SAV », renseigner marque/modele/motorisation, n° de chassis, message.
2. Ouvrir l'**aide ⓘ** du champ « N° de chassis » : une **modale** montre le certificat d'immatriculation, case E entouree. La fermer par la croix, par Echap et par un clic sur le fond.
3. Joindre un document valide (PDF/JPG, <= 5 Mo), envoyer.

**Resultats attendus** :
- Soumission stockee + e-mails SAV (demandeur + `info@`) avec la piece jointe referencee.

**Cas limites** :
- Document hors format ou > 5 Mo → **rejet avec message d'erreur** ; tentative d'ajouter un 2e document → bloquee (1 max). `[INFERE]`

**Mise en oeuvre (a rejouer)** :
- ⚠️ **Verifier d'abord que le champ « Ajouter un document » existe.** Sans `file_private_path` configure, Drupal retire l'element **silencieusement** — pas de log, pas de message : le champ n'apparait simplement pas. A controler apres tout deploiement, `settings.php` n'etant pas versionne.
- Le plafond affiche (« 5 Mo maximum ») est **borne par `upload_max_filesize`** du PHP qui sert le site : si le formulaire annonce « Limite a 2 Mo », c'est le PHP, pas la configuration Webform. ⚠️ Un controle via `drush runserver` interroge le PHP **CLI**, pas celui du vhost — il ne dit pas ce que voit un visiteur.
- ✅ **Resolu le 2026-08-21** : la piece jointe est desormais jointe a l'e-mail interne **et** a l'accuse demandeur (`attachments: true` sur `sav_demandeur` et `sav_interne`) — a rejouer pour confirmer sa presence reelle dans les 2 e-mails (pas seulement la mention texte « Piece jointe : nom-du-fichier », ajoutee le meme jour).

---

## S10 — Formulaire de contact : question

**Objectif** : Verifier le cas « une question » (champs reduits).

**Etapes** :
1. Choisir « une question », saisir le message, cocher l'autorisation, captcha, envoyer.

**Resultats attendus** :
- Seul le message est demande ; soumission stockee + e-mails « question ».

---

## S11 — Devenir partenaire

**Objectif** : Verifier la demande de partenariat (sans creation de compte).

**Etapes** :
1. Ouvrir le formulaire « Devenir partenaire », renseigner entreprise/identite/contact, repondre « amenageur qualifie ? » (Oui/Non), message, autorisation, captcha, envoyer.

**Resultats attendus** :
- Message de confirmation affiche ; soumission stockee + e-mails (demandeur + `info@`).
- **Aucun compte partenaire n'est cree automatiquement** (cf. decision #4).

**Mise en oeuvre (a rejouer) — integration du 2026-08-21** ([ADR-022](../.claude/decisions/022-gabarit-email-webform.md)) : les 2 e-mails suivent le meme gabarit que le formulaire de contact — sans ligne Statut (pas de champ « Vous etes » sur ce formulaire), avec une ligne Adresse ajoutee par coherence (absente de la maquette 810:10324/810:10435, alors que les 4 champs adresse sont obligatoires).

---

## Espace partenaire

## S12 — Activation du compte partenaire

**Objectif** : Verifier le parcours d'activation (e-mail 72 h → mot de passe → connexion).

**Donnees de test** : un compte partenaire cree en back-office (voir S22).

**Etapes** :
1. Recevoir l'e-mail « Votre compte personnel », cliquer sur le lien de definition du mot de passe.
2. Definir le mot de passe, se connecter.

**Resultats attendus** :
- L'e-mail suit le modele des specs ; le tableau de bord s'affiche apres connexion.

**Cas limites** :
- Lien utilise apres **72 h** → invalide ; le parcours « mot de passe perdu » permet de regenerer un acces.

**Mise en oeuvre (a rejouer) — integration du 2026-08-25** ([ADR-025](../.claude/decisions/025-roles-back-office-et-email-activation.md), maquette Figma 810:10544) : l'e-mail d'activation (`register_admin_created`) est reecrit selon la maquette via `mailer_policy`/`mailer_override`, teste bout-en-bout via Mailpit ; le role **« Partenaire »** ne porte aucune permission back-office (authentification seule) ; le role `content_editor` (relabellise **« Admin »** en S22) est etoffe et ajoute en `view_any` sur les 3 webforms pour consulter les demandes de compte.

---

## S13 — Tableau de bord & compteurs

**Objectif** : Verifier l'affichage automatique du tableau de bord.

**Etapes** :
1. Se connecter en tant que partenaire.

**Resultats attendus** :
- Affichage : bouton « Creer un nouveau devis », **nombre de devis a finaliser**, **nombre de devis/commandes en cours**, visuel configurateur.
- Les compteurs refletent l'etat reel des devis du partenaire.

---

## S14 — Configurateur : creer un devis (3 etapes)

**Objectif** : Verifier la creation d'un devis chiffre selon les conditions commerciales.

**Etapes** :
1. Etape Configuration : selectionner vehicule (marque/modele/type), equipements + quantites, nombre de vehicules identiques.
2. Ajouter une 2e configuration (autre vehicule). Cliquer « Voir mon devis ».
3. Etape Devis : verifier le tableau (tarif catalogue, tarif remise, quantites, totaux HT/remise/TVA 20 %/TTC).

**Resultats attendus** :
- Le **tarif remise** applique correspond au taux du partenaire **pour l'equipement concerne** (4 taux independants depuis ADR-043, un par equipement — plus un seul taux global) — fige a la creation de ce devis, ne suit plus les changements ulterieurs du compte.
- Les totaux par vehicule, par configuration et general sont exacts (TVA 20 %).
- Les frais de livraison **ne figurent pas** dans le devis.
- Le devis recoit un numero `WAAAAMMJJ-001` et le statut « a finaliser ».

**Cas limites** :
- Tentative d'ajouter une **11e** configuration → bloquee (max 10).
- Quantite retrovision exterieure hors bornes 1-2 → refus. `[INFERE]`

**Mise en oeuvre (a rejouer) — ecran 1 « Configuration » livre le 2026-08-26** (module `drivematic_configurator`, [ADR-028](../.claude/decisions/028-configurateur-formbase-vs-webform.md), maquettes 493:16990/606:36813/508:13222) :
- Les **3 etapes** de ce scenario sont aujourd'hui rejouables, sur `/configurer`, `/configurer/devis` et `/configurer/livraison` (role `partenaire`) — voir S15/S16 pour le detail de l'etape 3, livree le 2026-09-01.
- Cascade vehicule (marque/modele/motorisation) : memes taxonomies et meme mecanisme que le formulaire de contact (F10), generalise pour plusieurs cascades independantes sur une page.
- **Quantite retrovision exterieure bornee 1-2, verifiee par contournement reel du controle client** (attribut HTML `max` retire puis valeur 5 soumise) : refusee cote serveur avec le message natif Drupal de l'element `#type: number` — pas seulement desactivee visuellement.
- **11e configuration bloquee cote serveur**, pas seulement par la desactivation du bouton « Ajouter une configuration ».
- Suppression possible **des le 2e bloc** de configuration (jamais sur le 1er).
- Anonyme sur `/configurer` : redirige vers la connexion (mecanisme sitewide, cf. addendum S20 ci-dessous), pas de 403 brut.

**Mise en oeuvre (a rejouer) — ecran 2 « Devis » finalise le 2026-08-31** ([ADR-031](../.claude/decisions/031-devis-tempstore.md), maquettes desktop 508:13961/mobile 606:37565) :
- Tableau par configuration (tarif catalogue, tarif remise, quantites, total remise HT par ligne) + bandeaux de totaux (« Tarif par vehicule », « Tarif total vehicules » si plusieurs vehicules, « Total configuration(s) ») — HT/remise/remise HT/TVA 20 %/TTC. Seul le debut de « Total HT » s'aligne au pixel pres sur la colonne « Equipement(s) » du tableau (et « Total TTC », pousse a part au bord droit, sur la colonne « Total remise € HT ») ; les 3 metriques intermediaires n'alignent plus leur position entre les bandeaux depuis le 2026-08-31, cf. correction ci-dessous.
- **3 chemins de retour vers l'etape 1**, tous rechargent le meme brouillon `PrivateTempStore` prerempli : bouton « Modifier » d'une configuration, « Ajouter une configuration », pastille « Configuration » du fil d'etapes (desormais cliquable).
- Suppression d'une configuration (bouton « Supprimer ») : depuis le 2026-09-01, ouvre une **modale de confirmation** (« Voulez-vous vraiment supprimer cette configuration ? », `QuoteConfigurationDeleteForm`, meme mecanisme qu'ADR-034) avant de retirer l'entree du brouillon — auparavant suppression immediate, sans confirmation.
- Note « Devis hors frais de livraison. » toujours affichee ; aucune entite Devis/Configuration creee a ce stade (donnees perdues si le brouillon expire sans passage a l'etape 3).
- Repli mobile : cartes repliables par configuration (`quote-toggle.js`), amelioration progressive.

**Corrections (a rejouer) — ecran 2, post-livraison le 2026-08-31** ([ADR-032](../.claude/decisions/032-espacement-metriques-devis.md)) :
- Mobile : plus de scroll horizontal de page sur `/configurer/devis` (fil d'etapes et tableau d'equipements resserres, testes jusqu'a 320px de large sans troncature visible).
- Desktop : espace identique (20px) apres chaque texte « Total HT »/« Remise HT »/« Total remise HT »/« TVA 20 % » d'un meme bandeau — au prix de l'alignement colonne par colonne entre bandeaux pour ces 4 metriques (compromis assume, voir ligne ci-dessus).

---

## S15 — Adresses de livraison

**Objectif** : Verifier la gestion des adresses a l'etape Livraison.

**Etapes** :
1. A l'etape Livraison, verifier l'adresse de **facturation** (non modifiable en front).
2. Choisir une adresse de livraison existante, puis en ajouter une nouvelle, puis en modifier une.

**Resultats attendus** :
- L'adresse de facturation est en lecture seule.
- Une adresse de livraison ajoutee/modifiee en front est **automatiquement enregistree en back-office**.

**Mise en oeuvre (a rejouer) — livre le 2026-09-01** ([ADR-033](../.claude/decisions/033-entites-devis-livraison.md), [ADR-034](../.claude/decisions/034-modale-drupal-core.md)) :
- `/configurer/livraison` : adresse de facturation en lecture seule + lien
  « Contactez-nous » (ecart utilisatrice — pas de formulaire d'edition,
  pointe vers le node `contact`).
- Liste « Sélectionner une adresse de livraison » **toujours affichee**
  (meme a une seule adresse), radios + liens Modifier/Supprimer par ligne
  (meme pattern que les configurations de l'ecran 2). A la 1re visite, si le
  partenaire n'a aucune adresse, une **vraie entite** `delivery_address` est
  amorcee automatiquement depuis les champs du compte — traitee ensuite
  comme n'importe quelle autre (aucun cas particulier). Le bloc « Mon
  adresse de livraison » + bouton « Modifier l'adresse de livraison »
  isole de la maquette (671:21277) est un residu retire (retour
  utilisatrice).
- Ajout/edition d'adresse via une **modale Drupal core** (`use-ajax`,
  premiere utilisation de ce pattern dans le projet) : verifie en conditions
  reelles (requete AJAX authentifiee) — ouverture, revalidation avec message
  d'erreur sur un code postal invalide (sans fermer la modale), fermeture +
  redirection au succes.
- **IDOR verifie** : un partenaire ne peut ni voir ni modifier/supprimer
  l'adresse d'un autre (403, `DeliveryAddressAccessControlHandler`), teste
  avec 2 comptes distincts.
- Suppression d'une adresse deja utilisee par un devis existant : le devis
  garde ses propres donnees gelees (`Quote::delivery_*`), jamais affecte.

**Mise en oeuvre (a rejouer) — suite le 2026-09-01** (addendum [ADR-034](../.claude/decisions/034-modale-drupal-core.md)) :
- Les 3 modales (ajout/edition/suppression d'adresse) realignees **au pixel
  pres** sur la maquette 521:17375 (`getBoundingClientRect()` face aux
  coordonnees exactes de `get_metadata`) : bordure fantome, largeur de
  titre figee, croix de fermeture surdimensionnee et boutons « Oui »/« Non »
  mal alignes, tous corriges (CSS brut jQuery UI plus specifique que le
  notre — voir CLAUDE.md).
- Texte de la modale de suppression change en « Voulez-vous vraiment
  supprimer cette adresse ? ».
- Un admin (`administer users`) dispose desormais d'un recapitulatif en
  lecture seule des adresses de livraison d'un partenaire sur
  `/user/{uid}/edit` — voir [ADR-035](../.claude/decisions/035-recap-adresses-livraison-admin.md)
  et S26.

---

## S16 — Finaliser & commander un devis

**Objectif** : Verifier le passage a la commande (statut, message, e-mail + PDF).

**Etapes** :
1. Depuis « Mes devis / commandes en cours », ouvrir un devis « a commander » et cliquer « Commander ».

**Resultats attendus** :
- Message « Felicitations, votre commande a bien ete enregistree... ».
- Statut passe a « Commande le jj/mm/aaaa » ; le montant HT s'affiche.
- E-mail de confirmation au partenaire + copie interne (`info@`) [x] — **avec PDF du devis en piece jointe** [x] (ADR-041, voir plus bas).

**Mise en oeuvre partielle (a rejouer) — livre le 2026-09-01** (ADR-033) :
sur `/configurer/livraison`, les boutons **« Enregistrer le devis »**
(statut « À finaliser ») et **« Commander »** (statut « En cours »,
`date_commande` posee) materialisent le brouillon en entites `quote`/
`quote_configuration`/`quote_equipment_line` (prix geles, identiques a
l'ecran 2) et affichent le message de confirmation attendu (avec un lien
« Contactez-nous » pour le bon de commande). Redirige vers l'etape 1 (pas de
page « Mes devis » pour l'instant). **Hors perimetre, confirme avec
l'utilisatrice** : page « Mes devis »/« Tableau de bord » (F13/F15), e-mail
de confirmation, PDF du devis, statut intermediaire « A commander » distinct
de « Commande le jj/mm/aaaa » (2 statuts implementes pour l'instant : « À
finaliser »/« En cours »). Archivage automatique a J+30 apres
`date_commande` **implemente et verifie** (`hook_cron`, J+29 non archive
vs J+31 archive).

**Verifie de bout en bout — meme jour (2026-09-01)** : parcours navigateur
reel complet (Configuration → Devis → Livraison → « Commander »), sans
detour par curl — reference generee (`W20260901-001`), adresses de
facturation/livraison gelees correctement, totaux `QuoteConfiguration`/
`QuoteEquipmentLine` identiques a ceux affiches a l'ecran 2. Un devis
enregistre n'etait consultable **nulle part** (pas meme en back-office) :
ajout d'un listing admin `/admin/content/devis` (Vue Drupal `quotes`, tri
par colonne, filtre expose Statut, recherche par reference — permission
`view drivematic configurator quotes`). Voir S26.

**E-mails de confirmation implementes — le 2026-09-02** (ADR-036) : au
clic « Commander », deux e-mails sont desormais envoyes — au partenaire et
une copie interne a Drive Matic Legrand (`hook_mail()` + Mailer Policy,
gabarit identique aux e-mails webform existants). Verifie via Mailpit sur
un devis reel (`W20260901-001`) : sujet, corps HTML et texte brut corrects
pour les deux e-mails, jeton `[quote:reference]` et bloc identite
« Demandeur » (raison sociale/nom/adresse/e-mail/telephone) resolus
correctement, y compris avec un champ optionnel vide (complement
d'adresse). « Enregistrer le devis » ne declenche aucun envoi (verifie par
lecture du code — aucun appel sur ce chemin). **A rejouer** :
parcours navigateur reel complet jusqu'a reception effective des deux
e-mails (le test de cette session a appele `MailManagerInterface::mail()`
directement avec les memes parametres que `DeliveryForm`, pas via un clic
navigateur).

**PDF du devis implemente — le 2026-09-02** ([ADR-041](../.claude/decisions/041-pdf-devis.md)) :
conforme a la maquette Figma 714:9296, genere via `dompdf/dompdf` (nouveau
service `QuotePdfGenerator`) **uniquement** au clic « Commander » (jamais
« Enregistrer le devis »), et **regenere** (fichier ecrase) a chaque remise
Drive Matic accordee sur un devis existant (S17) — seul autre evenement qui
change les prix apres coup. Stocke sur `private://devis-pdf/{reference}.pdf`,
telechargeable depuis la page de detail admin (`/admin/content/devis/{id}/pdf`,
lien « Voir le PDF du devis », ouvert dans un nouvel onglet — voir S26) et
joint aux 2 e-mails de commande. Nouveau champ gele
`QuoteEquipmentLine::reference` (copie du catalogue a la persistance, `hook_update_N`)
pour la colonne « Reference » de la maquette — vide sur les devis crees avant
ce champ. Verifie de bout en bout sur un devis reel (`W20260902-001`) :
generation, telechargement authentifie (200) et anonyme (403), regeneration
sur remise DM (montants et bandeau de totaux mis a jour dans le fichier). Logo
en SVG (export Figma, pas le PNG des e-mails — celui-ci, rasterise a basse
resolution, laissait apparaitre un residu visuel une fois agrandi pour un
rendu print 300 DPI).

**Numero de TVA intracommunautaire ajoute — le 2026-09-03** : nouveau champ
gele `Quote::billing_vat` (meme pattern que `billing_siret`), copie de
`field_vat` du compte partenaire a la creation du devis. Dans le PDF, le
libelle du SIRET est renomme `SIRET : 38752953000066` (au lieu de
`Siret 38752953000066`) et une ligne `TVA : FRXXXXXX` apparait juste en
dessous (omise si le compte n'a pas de TVA renseignee, meme garde que le
SIRET — cas des comptes crees avant ce champ). L'e-mail de confirmation
interne (ADR-036) n'avait **aucune ligne SIRET** jusqu'ici : ajoutee en tete
du bloc « Demandeur », suivie de la TVA, avant la raison sociale (nouveaux
jetons `[quote:siret]`/`[quote:tva]`). Meme ajout, par coherence, sur l'ecran
« Livraison » (bloc « Mon adresse de facturation ») et la table de detail
admin d'un devis (S26). Verifie de bout en bout (PDF genere via `pdftotext`,
e-mail recu dans Mailpit, fragments prives rendus via Reflection) sur un
devis de test, donnees nettoyees ensuite.

---

## S17 — Remise par equipement sur un devis

**Objectif** : Verifier que Drive Matic peut ajuster une remise par equipement sur UN devis precis, sans jamais alterer le compte partenaire ni cumuler avec le taux deja fige.

**Etapes** :
1. Partenaire : disposer d'un devis au statut « a commander » (non commande), avec au moins 2 configurations partageant un meme equipement (ex. 2x « Retrovision exterieure »).
2. Admin (back-office, page de detail du devis) : ouvrir la section « Remises par équipement ».
3. Verifier les 4 lignes affichees (une par equipement, jamais une par ligne/configuration) et leur valeur preremplie.
4. Modifier le taux d'un des 4 equipements et enregistrer, sans rien changer aux 3 autres.
5. Admin : rouvrir la page de detail, verifier la section « Historique ».
6. Ouvrir la page de detail d'un devis **deja Commande** (ou Archive).

**Resultats attendus** :
- Etape 3 : exactement 4 lignes (Retrovision ext./int., Telecommande VOR, Double pedalier auto-ecole) — jamais une par configuration. Chaque valeur preremplie correspond au taux fige sur ce devis a sa creation (pas necessairement le taux courant du compte partenaire, s'il a change depuis).
- Etape 4 : le nouveau taux s'applique a **toutes** les lignes de cet equipement sur **ce devis uniquement**, meme si elles appartiennent a des configurations differentes (totaux recalcules en consequence) ; les 3 autres equipements et tout autre devis du meme partenaire restent inchanges ; le **compte partenaire n'est jamais modifie** par cette action — **remplacement**, jamais de cumul avec le taux precedent (verifie : un equipement a 10% fige, remplace par 20%, donne un prix a -20% du tarif catalogue brut, jamais -28%).
- La remise n'est modifiable que **tant que le devis n'a pas ete commande** — en lecture seule sur tout autre statut (etape 6 : les 4 lignes s'affichent sans aucun champ ni bouton).
- Etape 5 : une ligne apparait dans « Historique » PAR LIGNE d'equipement dont le taux a reellement change (date, « Remise Drive Matic : « <equipement> » <ancien>% → <nouveau>% », l'admin ayant agi) — 2 lignes distinctes si l'equipement modifie existe dans 2 configurations ; une resoumission sans changement de taux ne cree AUCUNE nouvelle ligne.

---

## S17bis — Historique des remises du compte partenaire

**Objectif** : Verifier qu'un admin peut retracer QUAND un taux de remise partenaire a change, pour justifier un ecart avec un devis fige avant ce changement.

**Etapes** :
1. Admin : ouvrir `/user/{uid}/edit` d'un compte partenaire (pas le sien).
2. Modifier une ou plusieurs des 4 remises (dont au moins un passage vers/depuis une valeur vide) et enregistrer.
3. Recharger la page.
4. Ouvrir `/user/{uid}/edit` de **son propre** compte (partenaire ou admin en auto-edition).

**Resultats attendus** :
- Etape 3 : une section « Historique des remises » liste chaque changement reel (date, equipement, ancien taux → nouveau taux, administrateur) — une ligne par champ modifie si plusieurs remises ont change dans la meme sauvegarde ; un passage vers/depuis une valeur vide s'affiche explicitement « — (vide) », jamais confondu avec 0%.
- Une sauvegarde du compte qui ne touche aucune des 4 remises ne cree AUCUNE ligne.
- Etape 4 : la section **n'apparait jamais** en auto-edition, quel que soit le compte (donnee commerciale interne).

---

## S18 — Gestion des devis (modifier/dupliquer/supprimer/archiver)

**Objectif** : Verifier les fonctions par onglet et l'archivage.

**Etapes** :
1. Onglet « A finaliser » : modifier, dupliquer, puis supprimer un devis (avec confirmation).
2. Onglet « En cours » : archiver un devis commande.
3. Onglet « Archives » : telecharger le PDF d'un devis archive.

**Resultats attendus** :
- Suppression demande confirmation ; duplication cree un nouveau devis « a finaliser ».
- Un devis commande est **auto-archive a 30 jours** ; un devis archive **n'est plus duplicable** mais son **PDF reste telechargeable**.

**Etat au 2026-09-02** : le PDF existe et se telecharge desormais reellement
(ADR-041), mais uniquement depuis le back-office (`/admin/content/devis/{id}`,
S26) — l'onglet « Archives » lui-meme (page « Mes devis » partenaire, F13)
n'est pas implemente ; l'etape 3 telle que decrite reste **a rejouer** une
fois cette page construite.

---

## S19 — Mes informations, mot de passe perdu & suppression de compte

**Objectif** : Verifier la gestion de compte cote partenaire.

**Etapes** :
1. Consulter/modifier « Mes informations personnelles ».
2. Tester « Mot de passe perdu ».
3. ~~Declencher « Supprimer mon compte » et confirmer.~~ **Non rejouable depuis le 27/08** : le lien a ete retire du menu (stub `<nolink>`, feature jamais implementee) — voir Resultats attendus.
4. Depuis le dropdown « Espace partenaire », cliquer sur « Me deconnecter ».

**Resultats attendus** :
- L'adresse de facturation reste **non modifiable** en front.
- « Supprimer mon compte » **n'existe plus dans le menu** (retire le 27/08) ; le comportement attendu (suppression du compte + anonymisation des devis/commandes associes) reste un critere d'acceptation F12 non implemente — a rejouer quand la feature et son lien seront (re)construits.
- « Me deconnecter » mene a une page de confirmation (`/user/logout/confirm`, question visible + boutons « Se deconnecter »/« Annuler ») avant toute deconnexion effective — pas de deconnexion immediate au clic.
- En mobile, les boutons « Modifier mon mot de passe » et « Mettre a jour mes informations » occupent toute la largeur de la carte et font la meme hauteur. En desktop, ils sont sur la meme ligne, alignes a droite, bord a bord avec les champs.

**Mise en oeuvre (a rejouer) — page « Mes informations personnelles » livree le 2026-08-25** (maquette Figma 524:20069, [plan](../docs/plans/partner-personal-information.md)) :
- Route `/user/mes-informations-personnelles` (module `drivematic_partner`), reservee au role `partenaire` (`_role` sur la route, 403 pour anonyme et pour tout autre role, y compris `administrator`).
- Reprend les champs du webform `account_request`. Seuls **Civilite/Prenom/Nom/Fonction/Telephone** sont modifiables et enregistres par « Mettre a jour mes informations ». **E-mail + tout le bloc « Votre entreprise »** (Siret, Raison sociale, Adresse, Complement, Code postal, Ville) sont en lecture seule (`readonly`, fond grise) — perimetre volontairement **plus large** que la seule « adresse de facturation » mentionnee au PRD (decision actee lors du plan, a refleter au PRD au `/sync`).
- « Modifier mon mot de passe » redirige vers `/user/password` (formulaire core de reinitialisation par e-mail), pas de champ mot de passe sur cette page.
- ⚠️ **A verifier a chaque rejeu** : `/user/{uid}/edit` (formulaire core d'auto-edition) ne doit **plus** afficher les 11 champs du profil partenaire pour le proprietaire du compte (`hook_form_user_form_alter`, `drivematic_partner.module`) — sinon un partenaire peut contourner le caractere lecture-seule en visitant directement cette URL. Un compte avec la permission `administer users` (editant un AUTRE compte) doit au contraire toujours les voir.
- « Supprimer mon compte » **non implemente** (hors scope de cette tache — chantier F12 restant).

**Mise en oeuvre (a rejouer) — confirmation de deconnexion et corrections responsive, 2026-08-26** ([ADR-027](../.claude/decisions/027-confirmation-deconnexion.md)) :
- Le lien « Me deconnecter » pointe vers `/user/logout/confirm` (`menu_link_content` id 48, contenu — pas config) au lieu d'une deconnexion immediate.
- Boutons de « Mes informations personnelles » corriges : pleine largeur + meme hauteur en mobile, meme ligne + alignes a droite sur les champs en desktop. Root cause : `<input type="submit">` non stylise reservait une marge horizontale invisible (~15px) empechant `width: 100%` d'atteindre les bords — corrige par `appearance: none` + `margin: 0`, generalise a `_reset.scss`.

**Mise en oeuvre (a rejouer) — lien de definition de mot de passe (e-mail d'activation), addendum du 2026-08-25** ([ADR-026](../.claude/decisions/026-profil-partenaire-mes-informations.md)) :
- Le lien de l'e-mail d'activation mene a `/user/{uid}/edit` (meme `user_form`, jeton `pass-reset-token`) : ne doit afficher que E-mail (lecture seule) + Mot de passe + Confirmer le mot de passe — plus d'Image/Langue du site/Fuseau horaire.
- Habillage stylise (`_user-edit-form.scss`), coherent avec le reste du site.
- Apres sauvegarde du mot de passe, redirection vers **« Mes informations personnelles »** (pas la page de compte par defaut du cœur).

**Mise en oeuvre (a rejouer) — numero de TVA intracommunautaire, 2026-09-03** :
nouveau champ `field_vat` (13 caracteres, pattern `FR` + 2 caracteres + 9
chiffres) ajoute au bloc « Votre entreprise », entre Siret et Raison sociale
— lecture seule ici comme les autres champs du bloc. **Corollaire verifie** :
masque sur `/user/{uid}/edit` en auto-edition (`_drivematic_partner_profile_field_names()`),
toujours visible/modifiable par un compte `administer users` editant un autre
compte. Verifie via curl authentifie (compte partenaire et admin) : absent
d'un cote, present et dans le bon ordre de l'autre.

## Securite & cloisonnement

## S20 — Cloisonnement : anonyme bloque sur les ressources partenaire

**Objectif** : Verifier que l'autorisation est re-verifiee cote serveur (decision #5).

**Etapes** :
1. Se deconnecter (ou session anonyme).
2. Acceder **directement** aux URL de l'espace partenaire (tableau de bord, un devis, PDF d'un devis, configurateur) en devinant/collant l'URL.

**Resultats attendus** :
- Chaque acces est **refuse cote serveur** (redirection connexion ou 403).
- **Ne doit PAS observer** : aucune donnee partenaire (devis, montants, adresses, PDF) exposee a l'anonyme, ni dans le HTML, ni dans `drupalSettings`, ni via une URL directe.

**Mise en oeuvre (a rejouer) — redirection sitewide le 2026-08-26** (`PartnerAccessRedirectSubscriber`, module `drivematic_partner`, addendum [ADR-028](../.claude/decisions/028-configurateur-formbase-vs-webform.md)) : tout anonyme sur **toute** route `_role: partenaire` (pas seulement `/configurer`) est **redirige vers `/user/login?destination=...`**, plus jamais un 403 brut — comportement uniforme, verifie sur `/configurer` et `/user/mes-informations-personnelles`.

---

## S21 — Compte suspendu

**Objectif** : Verifier qu'un compte suspendu ne peut pas se connecter.

**Etapes** :
1. Admin : suspendre un compte partenaire en back-office.
2. Tenter de se connecter avec ce compte.

**Resultats attendus** :
- Connexion **refusee** ; aucun acces a l'espace partenaire. `[INFERE]`

---

## Back-office & conformite

## S22 — Creation d'un compte partenaire & conditions commerciales

**Objectif** : Verifier la creation de compte en back-office et l'envoi de l'e-mail d'activation.

**Etapes** :
1. Admin (Gin) : creer un compte partenaire, renseigner conditions commerciales (4 remises par equipement — ADR-043, plus un seul taux par defaut), adresse de facturation.
2. Verifier l'envoi automatique de l'e-mail d'activation.
3. Modifier une remise, verifier l'entree d'historique (S17bis).
4. Modifier puis suspendre le compte.

**Resultats attendus** :
- L'e-mail d'activation (lien 72 h) part automatiquement a la creation.
- Les 4 remises par equipement, laissees vides ou renseignees independamment, sont prises en compte par le configurateur (cf. S14) — jamais un seul taux global.
- Etape 3 : chaque modification de remise cree une entree dans « Historique des remises » (cf. S17bis).
- La toolbar d'administration Gin est horizontale, en haut de l'ecran (decision #9).

---

## S23 — Consentement cookies & analytics

**Objectif** : Verifier le bandeau de consentement et le respect du choix (approche privacy-first).

**Etapes** :
1. Premiere visite anonyme : le bandeau de consentement s'affiche.
2. Refuser les cookies non essentiels.

**Resultats attendus** :
- Aucun traceur non essentiel (analytics) n'est depose avant consentement.
- Le choix est respecte et memorise. `[A PRECISER]` : outils analytics (Matomo/GA4) et CMP a trancher.

---

## S25 — Page de connexion (Espace partenaire) et demande de creation de compte

**Objectif** : Verifier la page `/user/login` (maquettes 472:12636 desktop / 602:33089 mobile) et les 3 parcours de navigation qu'elle expose, en anonyme.

**Etapes** :
1. Depuis n'importe quelle page, cliquer sur « Espace partenaire » (header, anonyme) → arrivee sur `/user/login`.
2. Cliquer sur « FAITES UNE DEMANDE » (lien dans la carte de connexion) ou sur le bouton « Créer un compte » (carte d'action) → page « Demande de création de compte » (`/demande-de-creation-de-compte`).
3. Retour sur `/user/login`, cliquer sur « Devenir partenaire » (carte d'action) → page « Devenir partenaire » (`/devenir-partenaire`).
4. Retour sur `/user/login`, cliquer sur « Demander un devis » (carte « auto-école ») → page Contact (`/contact`).
5. Saisir des identifiants invalides et cliquer sur « Me connecter » → message d'erreur core affiche, lien « Mot de passe oublié » fonctionnel (vers `/user/password`).
6. Cliquer sur le declencheur d'affichage du mot de passe → le mot de passe saisi devient visible, puis a nouveau masque au second clic.
7. Se connecter avec des identifiants valides d'un compte role `partenaire`.

**Resultats attendus** :
- Apres connexion (etape 7), redirection vers « Mes informations personnelles » (`/user/mes-informations-personnelles`), pas la page de compte par defaut du cœur.
- En mobile, les 3 cartes d'action font toutes la meme hauteur (verifier notamment que la carte « Vous êtes une auto-école », au texte plus court, n'est pas plus basse que les 2 autres).
- Les 3 cartes d'action et le lien « FAITES UNE DEMANDE » resolvent vers les bonnes pages, sans lien mort (`#`).
- Fil d'Ariane et titre d'onglet affichent « Me connecter » (pas le « Se connecter » par defaut du cœur — `easy_breadcrumb.replaced_titles` et `hook_preprocess_html()`).
- Aucune boite d'onglets locaux ("Se connecter"/"Réinitialiser votre mot de passe", libelles core inchanges) ni double titre ne s'affiche sur `/user/login`.
- Les 3 boutons des cartes d'action sont alignes sur une meme ligne basse, quelle que soit la longueur du texte au-dessus.
- La bascule d'affichage du mot de passe reste inerte si le JS ne s'execute pas (repli sans JS : champ mot de passe standard).
- « Demande de création de compte » et « Devenir partenaire » n'aboutissent **jamais** a une creation de compte immediate — ce sont des demandes, instruites en back-office (decision #4, aucune auto-inscription).

**Cas limites** :
- Renommer le node « Devenir partenaire » ou « Demande de création de compte » en back-office casse la recherche par titre (`_drive_matic_simple_form_node_url()`) : le lien retombe sur `#`. A surveiller, meme classe de fragilite que la recherche par bundle deja acceptee sur le bouton « Demander un devis » du header.

**Mise en oeuvre (a rejouer) — integration du 2026-08-25** ([ADR-024](../.claude/decisions/024-mutualisation-formulaire-simple.md), addendum [ADR-015](../.claude/decisions/015-habillage-des-formulaires.md)) : bundle `partner` mutualise en `simple_form` (multi-instance), nouveau SDC `login-panel`, nouvelle fondation `_user-login-form.scss` pour le formulaire core. Les 2 pages `simple_form` portent chacune leur propre webform depuis le meme jour : `partner` (Devenir partenaire) et le webform dedie `account_request` (Demande de creation de compte, addendum ADR-024) — le partage temporaire initial est termine.

**Mise en oeuvre (a rejouer) — corrections responsive du 2026-08-26** : egalisation JS de la hauteur des 3 cartes d'action en mobile (`login-panel.js`, degradation gracieuse sans JS — chaque carte garde sa hauteur naturelle) ; bouton « Valider » du webform `account_request` en pleine largeur en mobile sur `/demande-de-creation-de-compte` (scope a ce seul webform, verifier que `/contact` et les autres webforms n'ont pas bouge).

**Mise en oeuvre (a rejouer) — numero de TVA intracommunautaire, 2026-09-03** :
nouveau champ obligatoire `tva` sur `/demande-de-creation-de-compte`, juste
apres le Siret (validation `FR` + 2 caracteres + 9 chiffres, 13 caracteres).
En desktop (≥992px), la grille 3 colonnes du formulaire donne Siret + TVA
(TVA sur 2 colonnes, `dm-form-span-2`, pour que son libelle tienne sur une
ligne) sur la 1re ligne, Raison sociale/Adresse/Complement sur la 2e,
Code postal/Ville sur la derniere (`dm-form-row-start` deja present sur
Code postal) ; **corrige le meme jour** : un 1er jet mettait Siret/TVA/Raison
sociale sur une seule ligne — le libelle TVA n'y tenait pas malgre un
`dm-form-row-start` sur Raison sociale (repartir sur 2 colonnes elargit
un champ, ne pas en exclure un 3e de la ligne). En mobile, tous les champs
restent empiles, inchange. Les 2
e-mails du webform (accuse demandeur + notification interne) affichent la
TVA sous le Siret. Verifie au navigateur (desktop et mobile).

---

## S26 — Back-office : consultation des devis et des adresses de livraison

**Objectif** : Verifier qu'un admin (`administer users`) retrouve, cote back-office, des donnees jusque-la invisibles hors de l'espace partenaire.

**Etapes** :
1. En tant qu'admin, ouvrir `/admin/content/devis`.
2. Trier sur chaque colonne (N° de devis, Partenaire, Statut, Total TTC, Date de creation).
3. Filtrer par Statut (« Archive ») ; verifier qu'un devis « A commander » disparait de la liste.
4. Rechercher un devis par une portion de sa reference (« N° de devis »).
5. Cliquer la reference d'un devis « A commander » pour ouvrir sa page de detail.
5bis. Si le devis a deja ete commande : verifier le lien « Voir le PDF du
devis » (ouvre `/admin/content/devis/{id}/pdf` dans un nouvel onglet, PDF
valide) ; sur un devis jamais commande, ce lien n'apparait pas.
6. Sur cette page : verifier « Resume » (partenaire cliquable, 4 lignes de remise par equipement — reflet LIVE du compte partenaire, pas du devis —, statut, totaux — sans les dates individuelles), « Historique » (colonne « Evenement » : une ligne par changement de statut ET par remise DM accordee, fusionnees et triees du plus ancien au plus recent, chacune avec date + auteur), facturation/livraison, puis les configurations et leurs lignes d'equipement (colonne « Reference » entre Equipement et Prix unitaire — tiret si absente du catalogue a la source, cf. F17).
7. Dans « Remises par équipement » (devis a au moins 2 configurations partageant un equipement homonyme, ex. 2x « Retrovision exterieure ») : verifier les 4 lignes fixes (jamais une sous-section par configuration), modifier le taux d'UN equipement homonyme.
7bis. Ouvrir un devis « Commande » ou « Archive » : verifier que « Remises par équipement » reste visible mais en lecture seule (aucun champ, aucun bouton).
8. Cliquer « Marquer comme commande », confirmer.
9. Sur un **autre** devis « A commander », cliquer « Archiver », confirmer.
10. Tenter d'acceder directement a l'URL d'archivage d'un devis « Commande » (pas « A commander »).
11. Ouvrir la page de detail d'un devis cree **avant** l'ajout de l'historique (aucune entree `quote_status_change`).
12. Ouvrir `/user/{uid}/edit` d'**un autre** compte partenaire (pas le sien).
13. Ouvrir `/user/{uid}/edit` de **son propre** compte admin.

**Resultats attendus** :
- Etape 1 : les devis enregistres s'affichent (reference, partenaire, statut lisible, total TTC, date).
- Etape 2 : chaque colonne est triable, sans erreur.
- Etape 3 : le filtre Statut exclut correctement les autres statuts.
- Etape 4 : la recherche par reference fonctionne (correspondance partielle).
- Etape 5 : la reference est un lien cliquable vers `/admin/content/devis/{id}`.
- Etape 5bis : le lien n'apparait que si le fichier PDF existe reellement sur
  le disque (genere au clic « Commander », S16) — jamais base sur le seul
  statut du devis.
- Etape 6 : chaque section s'affiche correctement, notamment le lien partenaire (vers `/user/{uid}/edit`) et les 4 lignes de remise partenaire (tiret si absente pour un equipement) ; la colonne « Reference » affiche la valeur gelee du devis, jamais relue depuis le catalogue courant.
- Etape 7 : le taux modifie s'applique a **toutes** les lignes de cet equipement sur ce devis (verifiable aux totaux recalcules), y compris les 2 lignes homonymes de configurations differentes — jamais une remise par sous-section/configuration ; `date de commande` remise a l'heure actuelle (redemarre le delai des 30 jours) ; une ligne d'« Evenement » apparait dans « Historique » PAR ligne dont le taux a reellement change (jamais pour une ligne resoumise a l'identique), avec l'ancien/nouveau taux et l'admin courant.
- Etape 7bis : les 4 taux affiches sont ceux reellement figes sur ce devis, pas les taux courants du compte partenaire s'ils ont change depuis.
- Etape 8 : statut devient « Commande le [date du jour] », les boutons d'action et le formulaire de remise disparaissent de la page ; une nouvelle ligne apparait dans « Historique » (date + « Commande » + le compte admin courant).
- Etape 9 : statut devient « Archive », date d'archivage posee, nouvelle ligne d'historique correspondante.
- Etape 10 : refuse cote serveur (« Ce devis n'est pas (ou plus) au statut « A commander » : action impossible. »), pas seulement en cachant le bouton.
- Etape 11 : « Historique » affiche au moins une ligne (date de creation du devis + statut initial deduit + partenaire), jamais une section vide.
- Etape 12 : un bloc « Adresses de livraison » en lecture seule (aucun lien Modifier/Supprimer) liste les adresses du partenaire, ou l'etat vide « Aucune adresse de livraison enregistree. » ; un bloc « Historique des remises » liste chaque changement reel d'une des 4 remises (date, equipement, ancien → nouveau taux, auteur), ou l'etat vide « Aucune modification enregistree. » (cf. S17bis).
- Etape 13 : ni le bloc « Adresses de livraison » ni « Historique des remises » **n'apparaissent** (jamais sur son propre compte, meme admin).
- Un compte sans la permission `view drivematic configurator quotes` (role `partenaire`) recoit un 403 sur `/admin/content/devis`, sur `/admin/content/devis/{id}` et sur `/admin/content/devis/{id}/pdf`.
- Un compte avec `view` mais sans `edit drivematic configurator quotes` voit la page de detail mais aucun bouton d'action ni formulaire de remise.

**Mise en oeuvre (a rejouer) — livre le 2026-09-01** ([ADR-035](../.claude/decisions/035-recap-adresses-livraison-admin.md), addendum [ADR-033](../.claude/decisions/033-entites-devis-livraison.md)) :
- `/admin/content/devis` : Vue Drupal (`views.view.quotes`), pas un simple
  `EntityListBuilder` — necessaire pour le tri par colonne, le filtre
  Statut (filtre **groupe** sur un champ `string`, le plugin `list_field`
  n'existant pas pour un champ `list_string` de base d'entite custom) et la
  recherche par reference. Lien menu enfant de `system.admin_content`,
  visible depuis `/admin/content`.
- Recapitulatif adresses sur `/user/{uid}/edit` : ajoute dans
  `drivematic_partner_form_user_form_alter()`, visible uniquement quand un
  compte `administer users` edite le compte de **quelqu'un d'autre**.
  `DeliveryAddressAccessControlHandler` ouvre l'operation `view` (pas
  `update`/`delete`) a tout compte `administer users`.
- **Piege corrige en construisant** : une entite content custom n'expose
  rien a Views (pas meme ses champs de base) sans
  `handlers: ['views_data' => \Drupal\views\EntityViewsData::class]`
  explicite sur l'entite — absent au depart, la Vue s'importait sans erreur
  mais plantait en 500 a l'affichage. Voir CLAUDE.md (section PHP/Drupal) ;
  `equipment_price`/`delivery_address`/`quote_configuration` n'ont pas
  encore ce handler.

**Mise en oeuvre (a rejouer) — page de detail du 2026-09-02** ([ADR-037](../.claude/decisions/037-page-detail-devis-admin.md)) : la reference, dans le listing, ouvre desormais `/admin/content/devis/{id}` (`QuoteDetailController`, 1er `Controller` du projet — tout le reste etait en `_form`) — resume, facturation/livraison gelees, une section par configuration avec ses lignes d'equipement. Aucun handler d'acces custom : le handler par defaut de Drupal accorde deja toutes les operations a qui a l'`admin_permission` de l'entite (`view drivematic configurator quotes`).

**Mise en oeuvre (a rejouer) — 4 statuts + actions + remise du 2026-09-02** ([ADR-038](../.claude/decisions/038-cycle-de-vie-devis-4-statuts.md)) : statut renomme en base (`en_cours` → `a_commander`, `hook_update_N`, 1er fichier `.install` du module), nouveau statut « Commande » (`date_confirmation`, distinct de `date_commande`). Nouvelle permission `edit drivematic configurator quotes` (moindre privilege, distincte de la permission de lecture). Verifie reellement via un serveur de test local (pas seulement en cliquant a l'oeil) : remise 10% DM sur une ligne deja remisee 10% partenaire → totaux exacts (Remise HT 164,35€, TTC 840,78€), `date_commande` remise a l'heure actuelle ; marquer commande puis tentative d'archivage direct par URL → refuse cote serveur ; devis « A commander »/« Commande » de 31 jours → seul le premier s'archive automatiquement par cron.

**Mise en oeuvre (a rejouer) — PDF du devis du 2026-09-02** ([ADR-041](../.claude/decisions/041-pdf-devis.md)) : lien « Voir le PDF du devis » (`target="_blank"`), affiche seulement si le fichier existe deja sur le disque. Route dediee `drivematic_configurator.quote_pdf`, meme `_entity_access: quote.view` que la page de detail — pas de `hook_file_download()`, le fichier n'est jamais servi par `system.private_file_download`. Reponse en `Content-Disposition: inline` (ouverture directe, pas de telechargement force). Verifie via curl authentifie (200, PDF valide) et anonyme (403).

**Retours utilisatrice, meme jour** (addendum ADR-038) : 4 points corriges d'un coup — section « Historique » (nouvelle entite `quote_status_change`, une entree par transition avec date+auteur, remplace les dates isolees de « Resume ») ; ligne « Remise partenaire » + partenaire cliquable vers `/user/{uid}/edit` ; colonne « dont remise DM » retiree (redondante) ; `QuoteDiscountForm` regroupe par configuration (verifie sur un devis a 2 configurations partageant un equipement homonyme — chaque ligne recoit sa propre remise, totaux exacts 400,00€ HT / 324,50€ remise / 64,90€ TVA). **Piege trouve en testant** : une cellule `#type: table` dont la valeur est un render array doit etre enveloppee dans `['data' => ...]`, sinon 500 (voir CLAUDE.md, section PHP/Drupal).

**Correctif du 2026-09-02 (suite)** : les devis crees avant l'ajout de cet historique n'affichaient aucune ligne, pas meme la date de creation — `buildCreationRow()` synthetise desormais systematiquement cette 1ere ligne quand le journal est vide (statut initial deduit de la presence de `date_commande`, jamais de doublon pour un devis cree depuis). Verifie sur les devis existants ET sur un devis passe par le vrai `QuotePersister::persist()` (une seule ligne, pas de doublon).

**Mise en oeuvre (a rejouer) — remises par equipement + historique du 2026-09-03** ([ADR-043](../.claude/decisions/043-remises-partenaire-par-equipement.md), [ADR-044](../.claude/decisions/044-historique-remises-partenaire.md)) : `QuoteDiscountForm` (« Remises par équipement ») regroupe desormais par TYPE d'equipement (4 lignes fixes) au lieu d'une section par configuration, et reste affichee (lecture seule, table simple sans formulaire) meme hors statut « A commander » ou sans droit d'edition. `dm_discount_rate` est fige une seule fois, a la creation du devis (`QuoteCalculator`/`QuotePersister`), et ne suit plus jamais le compte partenaire ensuite — remplace (jamais cumule) le taux fige, calcule depuis le tarif catalogue brut. Nouvelle colonne « Reference » dans le tableau des lignes (valeur gelee `QuoteEquipmentLine::reference`, deja existante pour le PDF mais pas affichee ici jusque-la). Nouvelle entite `partner_discount_change` (historique des remises du compte, detecte par `hook_ENTITY_TYPE_update()` generique — capte un changement via ce formulaire, `drush`, ou tout autre canal), affichee sur `/user/{uid}/edit` a cote du recapitulatif des adresses de livraison (S17bis). **Verifie de bout en bout via un parcours navigateur reel** (pas seulement en base) : preremplissage live tant qu'un devis n'a jamais ete retouche, remplacement sans cumul (100€ remise 20% → 80€, jamais 70€), 2 lignes homonymes recevant chacune leur propre entree d'historique, compte modifie a 99% sans effet sur un devis deja fige, section « Historique des remises » absente en auto-edition.

---

## Matrice de couverture (scenario → feature)

| Scenario | Features couvertes |
|----------|--------------------|
| S1 | F2, F9 |
| S2 | F3 |
| S3 | F4 |
| S4 | F5 |
| S5 | F6 |
| S6 | F7 |
| S7 | F8 |
| S8, S9, S10 | F10 |
| S11 | F11 |
| S12, S19 | F12 |
| S13 | F13 |
| S14, S15 | F14, F17 |
| S16, S17, S18 | F15 |
| S17, S17bis, S22 | F16 |
| S20, S21 | F12, decision #5 (cloisonnement) |
| S23 | F18 |
| S24 | F9 (volet FAQ) |
| S25 | F2, F11, F12 (page login, ADR-024) |
| S26 | F14, F15 (back-office : devis + adresses de livraison) |
| Transverse (S1-S26) | F1 (Paragraphes), decision #8 (RGAA/WCAG AA) |

## Historique des modifications

| Date | Modification | Scenarios impactes |
|------|--------------|---------------------|
| 2026-09-03 | **F16 — historique des remises du compte partenaire** ([ADR-044](../.claude/decisions/044-historique-remises-partenaire.md)) : nouvelle entite `partner_discount_change`, detectee par `drivematic_configurator_user_update()` (`hook_ENTITY_TYPE_update()` generique pour `user` — capte un changement via `/user/{uid}/edit`, `drush`, ou tout autre canal, pas seulement une soumission de formulaire). Affichee en lecture seule sur `/user/{uid}/edit` (admin editant un AUTRE compte, jamais en auto-edition), a cote du recapitulatif des adresses de livraison : date, equipement, ancien → nouveau taux (« — (vide) » si absent, jamais confondu avec 0%), auteur. But : justifier un ecart entre le taux affiche aujourd'hui sur le compte et celui reellement fige sur un devis cree avant ce changement. Verifie via impersonation d'une session admin reelle (`\Drupal::currentUser()->setAccount()`, pour eviter tout risque sur le vrai compte partenaire via un POST HTML reconstruit a la main) : auteur correctement capture, aucune entree pour un champ non modifie, 2 champs changes dans la meme sauvegarde produisant 2 entrees, passage vide↔valeur affiche correctement, section absente en auto-edition. **A rejouer** : S17bis, S22, S26 | S17bis, S22, S26 |
| 2026-09-03 | **F14/F15/F16 — remises partenaire par equipement, remplacement (plus de cumul), snapshot fige a la creation** ([ADR-043](../.claude/decisions/043-remises-partenaire-par-equipement.md)) : `field_discount_rate` (taux unique) supprime (config + donnees purgees, pas de migration) et remplace par 4 champs independants (`field_discount_retrovision_ext`/`retrovision_int`/`telecommande_vor`/`pedalier`), nouveau service `PartnerDiscountResolver`. `QuoteDiscountForm` (« Remises par équipement », renommee) regroupe desormais par TYPE d'equipement — **exactement 4 lignes**, jamais une par ligne/configuration — un taux saisi s'applique a toutes les lignes de ce type sur CE devis uniquement (jamais au compte partenaire), tout en generant une entree `quote_discount_change` par LIGNE reellement modifiee (granularite ADR-040 inchangee). Section desormais **toujours visible** : lecture seule des que le devis n'est plus « A commander » ou sans droit d'edition. Le taux partenaire par equipement est resolu et **fige une seule fois, a la creation du devis** (`QuoteCalculator`/`QuotePersister`) — un devis ne suit plus jamais les changements ulterieurs du compte, y compris tant qu'il reste « A commander » (correction d'un 1er jet en resolution live, jugee trop surprenante par l'utilisatrice en cours de session). `dm_discount_rate` remplace desormais integralement le taux applique a `unit_price` brut, plus de cascade avec la remise partenaire. `hook_update_11009` retro-complete `QuoteEquipmentLine::equipment_type` (deduit du libelle, mapping fiable) sur les lignes anterieures ; `hook_update_11010` fige au taux partenaire courant les lignes de devis existantes jamais reellement retouchees par un administrateur. Nouvelle colonne « Reference » dans le detail de devis admin (valeur deja existante pour le PDF, ADR-041, non affichee ailleurs jusque-la) — **constat en verifiant** : le catalogue n'a jamais eu de reference pour retrovision ext./int. et telecommande VOR (139 lignes sur 414, faute de donnee source, anticipe des la conception) contrairement au pedalier (274/274) — pas un bug d'import. Verifie de bout en bout via un parcours navigateur reel : preremplissage live tant qu'un devis n'a jamais ete retouche, remplacement sans cumul (100€ remise 20% → 80€, jamais 70€ via cascade), 2 lignes homonymes de 2 configurations recevant chacune leur propre entree d'historique lors d'une modification groupee par type, compte partenaire modifie a 99% sans le moindre effet sur un devis deja fige. **A rejouer** : S14, S17, S22, S26 | S14, S17, S22, S26 |
| 2026-09-03 | **F12/F15 — numero de TVA intracommunautaire du profil partenaire** (commit `d02345c`) : nouveau champ `field_vat` (13 caracteres, pattern `FR` + 2 caracteres + 9 chiffres) suivant exactement le pattern deja etabli pour `field_siret` — obligatoire sur le webform `account_request` (juste apres Siret, grille desktop Siret+TVA sur une ligne — TVA sur 2 colonnes pour que son libelle tienne, corrige le meme jour apres un 1er jet a 3 champs par ligne trop etroit — Raison sociale/Adresse/Complement sur la suivante), lecture seule sur `/user/{uid}/edit` (BO) et « Mes informations personnelles » (partenaire), gele sur `Quote::billing_vat` a la creation du devis (`QuotePersister`). PDF du devis : libelle SIRET renomme `SIRET : ...` (au lieu de `Siret ...`) et ligne `TVA : ...` ajoutee en dessous. E-mail de confirmation de commande interne (ADR-036) : ajout de SIRET (absent jusqu'ici) puis TVA en tete du bloc « Demandeur », nouveaux jetons `[quote:siret]`/`[quote:tva]`. Etendu par coherence (decision explicite) a l'ecran « Livraison » (bloc facturation) et a la table de detail admin d'un devis, qui affichaient deja le Siret dans le meme contexte. **Corollaire verifie** : tout champ du bloc « Votre entreprise » doit etre ajoute a la fois a `_drivematic_partner_profile_field_names()` (masque en auto-edition) et a `PersonalInformationForm` (sinon le partenaire perd toute visibilite sur sa propre valeur) — voir CLAUDE.md. Verifie de bout en bout sans navigateur multi-etapes : formulaires via curl authentifie, PDF via `pdftotext`, e-mail via Mailpit, fragments prives (`DeliveryForm`/`QuoteDetailController`) via `ReflectionMethod` + `renderInIsolation()`. **Deploye en preprod le meme jour** (`scripts/deploy-preprod.sh --no-backup`, sur demande explicite — pas de dump de securite pour ce deploiement precis). **A rejouer** : S16, S19, S25, S26 | S16, S19, S25, S26 |
| 2026-09-02 | **Deploiement — transport SMTP preprod** ([ADR-042](../.claude/decisions/042-smtp-preprod.md)) : nouvelle entite versionnee `mailer_transport.mailer_transport.smtp_passerelle` (`mails.passerelle.com`, port 465/TLS implicite) **sans mot de passe** (jamais commite) — le secret et la selection du transport par defaut vivent uniquement dans le `settings.php` de la preprod (meme mecanisme deja en place pour la cle secrete reCAPTCHA), pour survivre a chaque `config:import` du script de deploiement. Local (Mailpit) inchange. **Aucune modification necessaire du script `scripts/deploy-preprod.sh`** : `rsync` ne touche jamais `settings.php` (non suivi par git, ADR-039), et une surcharge `$config[...]` runtime n'est jamais effacee par `config:import`. Verifie localement : import propre (seule cette entite creee), lecture de l'entite conforme, surcharge simulee confirmee lue en priorite sur le stockage | Hors matrice (deploiement) |
| 2026-09-02 | **F15 — PDF du devis** ([ADR-041](../.claude/decisions/041-pdf-devis.md)) : conforme a la maquette Figma 714:9296, `dompdf/dompdf` (aucune lib PDF vendorisee jusque-la), nouveau service `QuotePdfGenerator`. Genere **uniquement** au clic « Commander » (jamais « Enregistrer le devis »), **regenere** (fichier ecrase) a chaque remise Drive Matic accordee sur un devis existant — seul autre evenement qui change les prix apres coup. Stocke sur `private://devis-pdf/{reference}.pdf`, telechargeable depuis la page de detail admin (route dediee, meme controle d'acces, `Content-Disposition: inline`) et joint aux 2 e-mails de commande (`$message['params']['attachments']`). Nouveau champ gele `QuoteEquipmentLine::reference` (copie du catalogue a la persistance, `hook_update_N`) pour la colonne « Reference » de la maquette — vide sur les devis anterieurs a ce champ. **Corrige le meme jour** : le logo (PNG des e-mails, 633x73) laissait apparaitre un residu visuel une fois rendu par Dompdf en qualite print (300 DPI) — remplace par l'export SVG du logo de la maquette Figma, sans limite de resolution ; fichier des e-mails non touche. Verifie de bout en bout sur un devis reel (`W20260902-001`) : generation, telechargement authentifie (200) et anonyme (403), regeneration sur remise DM (montants/bandeau de totaux mis a jour). **A rejouer** : S16, S18, S26 | S16, S18, S26 |
| 2026-09-02 | **F15/F16 — tracabilite des remises Drive Matic dans l'historique du devis** ([ADR-040](../.claude/decisions/040-tracabilite-remise-exceptionnelle.md)) : nouvelle entite `quote_discount_change` (meme pattern que `quote_status_change` — une entite dediee plutot qu'un champ JSON serialise, aucun handler d'acces propre), une entree par LIGNE d'equipement dont `dm_discount_rate` a reellement change (jamais par soumission — une resoumission identique ne cree aucune entree). « Historique » fusionne desormais statuts et remises, tries chronologiquement (en-tete « Statut » → « Evenement »). Verifie reellement via soumission curl sur une session admin authentifiee (le clic Browser MCP sur ce bouton echouait silencieusement, sans requete POST) : entree creee avec les bonnes valeurs ancien/nouveau taux + auteur, resoumission identique confirmee sans doublon, devis restaure a son etat d'origine apres test. **A rejouer** : S17, S26 | S17, S26 |
| 2026-09-02 | **F15 — page de detail du devis, retours utilisatrice** (addendum [ADR-038](../.claude/decisions/038-cycle-de-vie-devis-4-statuts.md)) : nouvelle entite `quote_status_change` (historique des statuts, date+auteur, remplace les dates isolees de « Resume », toujours au moins une ligne de creation meme pour un devis anterieur a cette entite) ; ligne « Remise partenaire » + partenaire cliquable ; colonne « dont remise DM » retiree ; `QuoteDiscountForm` regroupe par configuration (verifie sur un devis a 2 configurations partageant un equipement homonyme, chaque ligne recoit sa propre remise). **2 bugs reels trouves en verifiant** (pas une relecture de code) : une cellule `#type: table` dont la valeur est un render array plante en 500 sans wrapper `['data' => ...]` (voir CLAUDE.md) ; l'historique d'un devis anterieur a la journalisation etait totalement vide, meme la date de creation — corrige par une ligne de repli synthetisee (`buildCreationRow()`), verifiee sans doublon sur un devis passe par le vrai `QuotePersister::persist()`. **A rejouer** : S26 | S26 |
| 2026-09-02 | **F15 — cycle de vie du devis a 4 statuts, actions manuelles et remise DM par ligne** ([ADR-038](../.claude/decisions/038-cycle-de-vie-devis-4-statuts.md)) : statut renomme en base (`en_cours` → `a_commander`, `hook_update_N`, 1er `.install` du module) + nouveau statut « Commande » (`date_confirmation`) ; 2 `ConfirmFormBase` (marquer commande, archiver — chacun re-verifie cote serveur que le devis est bien « A commander », pas seulement en cachant le bouton) ; remise Drive Matic par ligne (`QuoteDiscountForm`, embarquee dans la page de detail) calculee en cascade sur le prix deja remise par le partenaire, jamais stockee dans les champs geles (prix effectif calcule a la lecture, `QuoteEquipmentLine::getEffectiveDiscountedHt()`, pour eviter qu'une remise reappliquee ne se cumule sur elle-meme) ; enregistrer une remise remet `date_commande` a l'heure actuelle (redemarre le delai des 30 jours). Nouvelle permission `edit drivematic configurator quotes` (moindre privilege). **Divergence PRD signalee et tranchee avec l'utilisatrice** : l'archivage manuel n'est possible que depuis « A commander », jamais depuis « Commande » (corrige une ligne PRD ecrite avant cette distinction). Verifie reellement (serveur de test local, pas de clic a l'oeil) : calcul cascade exact (10%+10% → 700,65€ HT remise, TTC 840,78€), garde-fous serveur sur les 2 actions, cron n'archive jamais un devis « Commande » meme a 31 jours. **A rejouer** : S16, S26 (parcours navigateur reel complet) | S16, S26 |
| 2026-09-02 | **F15 — page de detail d'un devis en back-office** ([ADR-037](../.claude/decisions/037-page-detail-devis-admin.md)) : la reference, dans `/admin/content/devis`, ouvre desormais sa page de detail complete (`QuoteDetailController`, 1er `Controller` du projet) — resume, facturation/livraison gelees, configurations et lignes d'equipement. Aucun handler d'acces custom (le handler par defaut de Drupal accorde deja tout via `admin_permission`). **A rejouer** : S26 | S26 |
| 2026-09-02 | **F15 — e-mails de confirmation de commande** ([ADR-036](../.claude/decisions/036-email-confirmation-commande.md)) : au clic « Commander » (jamais « Enregistrer le devis »), `DeliveryForm` envoie un e-mail au partenaire et une copie interne a Drive Matic Legrand (`hook_mail()` + Mailer Policy, meme gabarit HTML que les e-mails webform existants — 1ere utilisation de ce mecanisme pour un module custom). Jetons dedies (`[quote:reference]`, `[quote:raison-sociale]`, etc.) : coordonnees de facturation gelees sur le devis, civilite/nom/e-mail/telephone lus sur le compte partenaire (pas d'equivalent gele). **2 pieges non triviaux trouves en verifiant** (voir CLAUDE.md, section « E-mails via hook_mail() + Mailer Policy ») : `BodyEmailAdjuster` de mailer_policy detourne une variable Twig si `hook_mail()` pose un corps HTML — texte de repli deplace sur `$message['plain']` ; `t()` plante (`TypeError`) sur un placeholder `NULL` (champ optionnel vide) — chaque valeur castee en `(string)`. Verifie via Mailpit sur un devis reel, y compris avec un champ optionnel vide. **Hors perimetre** : PDF du devis en piece jointe. **A rejouer** : S16, parcours navigateur reel jusqu'a reception effective des e-mails | S16 |
| 2026-09-01 | **F14/F15 — suite ecran 3 « Livraison » : persistance verifiee, modales realignees au pixel pres, listing admin des devis, recap admin des adresses** ([ADR-033](../.claude/decisions/033-entites-devis-livraison.md) addendum, [ADR-034](../.claude/decisions/034-modale-drupal-core.md) addendum, [ADR-035](../.claude/decisions/035-recap-adresses-livraison-admin.md)) : parcours complet (Configuration → Devis → Livraison → « Commander ») rejoue en navigateur reel, entites/reference/totaux confirmes corrects en base. Suppression d'une configuration (ecran Devis) dotee d'une modale de confirmation (`QuoteConfigurationDeleteForm`), remplace l'ancienne suppression immediate sans confirmation. Un devis n'etant consultable nulle part cote back-office, ajout d'un listing `/admin/content/devis` (Vue Drupal, tri/filtre Statut/recherche par reference) et d'un recap en lecture seule des adresses de livraison sur `/user/{uid}/edit` admin. 3 modales d'adresse realignees au pixel pres sur 521:17375 (bordure fantome, titre a largeur figee, croix surdimensionnee, boutons « Oui »/« Non » mal alignes — CSS brut jQuery UI plus specifique que le notre, regle generalisee dans CLAUDE.md). **Piege generalisable** : une entite content custom n'expose rien a Views sans `handlers: ['views_data' => EntityViewsData::class]` explicite. **A rejouer** : S15, S16, S26 | S15, S16, S26 |
| 2026-09-01 | **F14 — configurateur de devis, ecran 3 « Livraison » livre** ([ADR-033](../.claude/decisions/033-entites-devis-livraison.md), [ADR-034](../.claude/decisions/034-modale-drupal-core.md), maquettes 508:13965/671:21277/521:17375/671:22383) : route `/configurer/livraison`, 4 nouvelles entites custom (`quote`, `quote_configuration`, `quote_equipment_line`, `delivery_address` — premiere entite multi-instance par partenaire du projet, controle d'acces par proprietaire). Liste d'adresses de livraison toujours affichee (radios + Modifier/Supprimer par ligne), amorcee automatiquement depuis le compte a la 1re visite ; le bloc « Mon adresse de livraison » + bouton isole de la maquette est un residu retire (retour utilisatrice). Ajout/edition d'adresse en modale Drupal core (`use-ajax`, premiere utilisation de ce pattern, zero JS custom). « Enregistrer le devis »/« Commander » materialisent le brouillon `PrivateTempStore` en entites (prix geles, numerotation `WAAAAMMJJ-001`) et purgent le brouillon. Archivage automatique a J+30 (`hook_cron`). **Bug corrige en verifiant** : `$form_state->setRedirect()` dans `buildForm()` n'a aucun effet sur une requete GET (uniquement pris en compte apres soumission) — l'etat vide (brouillon absent) doit etre rendu inline, pas redirige, meme pattern que `QuoteForm`. **Hors perimetre, confirme avec l'utilisatrice** : F13 (Tableau de bord), reste de F15 (page « Mes devis », Dupliquer, PDF, e-mail de confirmation, archivage manuel). **A rejouer** : S15, S16 (mobile et desktop, IDOR avec 2 comptes partenaire) | S15, S16 |
| 2026-08-31 | **F2 — fil d'Ariane masque en mobile** (addendum [ADR-023](../.claude/decisions/023-fil-ariane-style.md), demande explicite) : `.breadcrumb ol` passe a `display: none` sous 992px, `.breadcrumb` conserve son `padding-block` (l'ecart vers le titre de page/premier paragraphe hero n'est pas affecte). **A rejouer** : S1, en mobile (< 992px) sur une page avec et sans bloc titre | S1 |
| 2026-08-31 | **F3 — `news_home` (bloc actualites home) : titre, points de pagination et bouton « voir toutes » recentres** : `.news-home` n'a qu'un `padding-left` (le droit est laisse libre pour le debordement volontaire de la piste de cartes) — ces 3 elements en heritaient et se retrouvaient decales de `gutter/2` (20px a 1440px) a droite du vrai centre de page. Corrige par un `padding-right: var(--dm-gutter)` fixe sur chacun (pas le pattern `calc(50vw - 50%)` habituel, qui s'annule a zero ici — cf. CLAUDE.md, section SCSS/SDC). Verifie par mesure DOM en desktop et mobile, pas a l'oeil. **A rejouer** : S2 (bloc actualites, desktop et mobile) | S2 |
| 2026-08-31 | **F14 — configurateur de devis, ecran 2 « Devis », 3 corrections post-livraison** ([ADR-032](../.claude/decisions/032-espacement-metriques-devis.md)) : (1) fil d'etapes debordant horizontalement en mobile (l'etape « Configuration » y passe en etat franchi, plus large que sur l'ecran 1) — gap/padding resserres, filet de securite `overflow-x: auto` corrige pour ne plus tronquer les deux pastilles extremes a parts egales au repos (`flex-start` + `width: fit-content` au lieu de `center` seul). (2) Tableau d'equipements debordant aussi en mobile (`table-layout: auto` sous son mot le plus long), invisible en scroll de page (`overflow: hidden` des coins arrondis rognait en silence) — padding des cellules resserre. (3) Espace inegal (13 a 31px) apres chaque texte des 4 premieres metriques des bandeaux de totaux, largeurs fixes remplacees par un espace uniforme (20px) — perte assumee de l'alignement colonne par colonne entre bandeaux pour ces 4 metriques (compromis presente et tranche par l'utilisatrice). **A rejouer** : S14 (etape 2, mobile 320-390px et desktop) | S14 |
| 2026-08-31 | **F14 — configurateur de devis, ecran 2 « Devis » finalise** ([ADR-031](../.claude/decisions/031-devis-tempstore.md), maquettes desktop 508-13961/mobile 606-37565) : pastille « Configuration » du fil d'etapes rendue cliquable (3e chemin de retour vers l'etape 1, en plus de « Modifier »/« Ajouter une configuration »). Titre `<h1>` uniformise sur les 3 etapes. Boutons de bas de page et note « Devis hors frais de livraison. » alignes sur la maquette. **Alignement pixel-parfait des totaux** (Total HT/Remise HT/Total remise HT/TVA 20 %/Total TTC) sur les 3 bandeaux : les bandeaux par configuration sont desormais de vraies lignes du tableau d'equipements (`#footer` Drupal → `<tfoot>`, garantit l'alignement par construction) ; le bandeau « Total configuration(s) », hors tableau, reutilise la fraction de largeur de la colonne vehicule via `calc()`. Corrige l'accent d'« Equipement(s) » → « Équipement(s) ». **A rejouer** : S14 (etape 2, mobile et desktop, plusieurs configurations pour verifier le bandeau « Tarif total vehicules ») | S14 |
| 2026-08-27 | **F2 — lien « Supprimer mon compte » retire du menu « Espace partenaire »** : stub `<nolink>` sans page (`menu_link_content` id 46, entite de contenu, pas de config a synchroniser), retire a la demande de l'utilisatrice plutot que de laisser un placeholder mort. Le menu ne montre plus que 4 liens. Reintegrera le menu quand F12 (suppression de compte) sera implemente | S1, S19 |
| 2026-08-27 | **Transverse — chevron anime au survol + cartes actualites entierement cliquables** (motif « Lien », Figma 243:5551 Variant2) : le chevron glisse de 6px vers la droite au survol sur les 4 composants qui reprennent ce motif (`news-card`, `news-teaser`, `grid-element`, `product-cross-element`). `news-card` (bloc home) recoit desormais le meme lien etire que `news-teaser` (visuel + titre cliquables, pas seulement « Lire la suite »). **Bug corrige au passage** sur `news-teaser` : le lien etire etait ancre sur toute la ligne (`.news-teaser`, colonne de texte `1fr` plus large que son contenu) au lieu du seul bloc de texte — un clic dans le vide a droite d'un titre court naviguait quand meme. Corrige en ancrant sur `.news-teaser__body` (retreci a son contenu via `justify-self: start`) ; l'image (soeur du lien, pas descendante) reste couverte par un clic JS delegue (`news-teaser.js`, amelioration progressive). **A rejouer** : S2 (cartes home), S7 (clic hors zone sur la liste actualites, desktop et mobile) | S2, S7 |
| 2026-08-27 | **F14 — corrections supplementaires du selecteur de quantite** (suite du point 12 du 26/08, `docs/active/configurateur-etape-1/verification.md`) : le champ central heritait du `border-radius: 8px` de la fondation `forms` (`input[type='number']`) faute d'etre reinitialise par l'override du configurateur — corrige a angles droits (maquette 508:12884). En mobile, le pilulier restait flush avec la case a cocher au lieu de s'aligner sous le libelle (maquette 606:37136, divergence assumee du desktop qui reste flush) — corrige (indentation 29px = largeur case + gap fondation). **A rejouer** : S14 (etape 1, mobile et desktop) | S14 |
| 2026-08-27 | **Transverse — images `jumbo_home`/`image_full` chargees en `eager`** (perf) : ces paragraphes rendent des visuels au-dessus de la ligne de flottaison (hero home, bandeau pleine largeur produit/transform) — le lazy-loading par defaut retardait leur affichage sans benefice. Reglage natif du formatter `responsive_image` (`image_loading.attribute`), pas de code custom | S2, S3, S4 |
| 2026-08-26 | **Transverse — boutons, checkboxes et radios harmonises** ([ADR-029](../.claude/decisions/029-mixin-boutons-partage.md), maquette Figma 243:5551) : couleurs de survol corrigees et unifiees pour les 3 familles de boutons (plein gris → rouge, plein rouge → bleu acier, contour blanc → bleu acier plein), plusieurs boutons sans aucun hover jusque-la (`text_centered`, `image_full`, `image_text_50/100`, `jumbo_home_element`, `site-header__account-trigger`, `configurator-form__add`). Checkboxes/radios coches passent du rouge au bleu acier (`_forms.scss`, seule implementation du depot — couvre aussi le configurateur). **Bug de hauteur corrige au passage** (hors demande initiale mais dans le perimetre « meme hauteur ») : plusieurs boutons heritaient du `line-height` du corps de texte (28px) au lieu d'un `line-height: normal`, et grimpaient a 56-58px au lieu de 46px. **Hors perimetre** (documente dans l'ADR, pas touche) : boutons pilule FAQ/accordeon, `product_characteristics__download` (variante fond anthracite). **A rejouer** : tout bouton/checkbox/radio du site, en particulier `/contact` (checkbox consentement), `/configurer` (checkboxes equipements, bouton « Ajouter une configuration », non rejoue en conditions reelles cette session faute de compte partenaire de test), `/user/login` (3 cartes d'action, bouton « Me connecter »), le header (CTA rouge + « Espace partenaire ») | Transverse |
| 2026-08-26 | **F14 — configurateur de devis, ecran 1 « Configuration » livre** ([ADR-028](../.claude/decisions/028-configurateur-formbase-vs-webform.md), commit `6380bda`, maquettes 493:16990/606:36813/508:13222) : nouveau module `drivematic_configurator`, `FormBase` (pas Webform) sur `/configurer` (role `partenaire`), reprend l'alias de l'ancien node placeholder desormais supprime — **13 liens en dur** vers ce node (`entity:node/69`) trouves et corriges sur 6 pages publiques. Cascade vehicule generalisee pour plusieurs instances sur une page (`drivematic_forms_vehicle_map()` rendue publique, `vehicle-select.js` ciblant par attribut plutot que par nom de champ), reutilisee telle quelle par le webform contact. Blocs de configuration repetables (max 10), 4 equipements en dur (F17 restant), quantite retrovision exterieure bornee 1-2 — plafonds verifies **cote serveur par contournement reel**, pas seulement lus dans le code. Redirection sitewide (anonyme → connexion, pas 403) generalisee a toute route partenaire. **~10 corrections post-livraison** (specificite CSS face aux fondations `forms`/`page-title`, rendu natif fieldset+legend, interaction `align-items: stretch` + marge par item, `justify-self: stretch` par defaut sur un item de grille CSS, conformite fine a la maquette du selecteur de quantite) detaillees dans `docs/active/configurateur-etape-1/verification.md`. **A rejouer** : S14 (etape 1 seulement), S20 (redirection sitewide) | S14, S20 |
| 2026-08-26 | **F12 — confirmation de deconnexion, redirection post-connexion et corrections responsive** ([ADR-027](../.claude/decisions/027-confirmation-deconnexion.md), commits `84d9b1d`/`d3320e8`) : « Me deconnecter » mene desormais a `/user/logout/confirm` (question visible via `hook_form_user_logout_confirm_alter`, le bloc titre de page etant absent sur cette route comme sur `/user/login`/`/user/password`) au lieu d'une deconnexion immediate. Un partenaire authentifie sur `/user/login` est redirige vers « Mes informations personnelles » (scope au role `partenaire`). **3 corrections responsive** : hauteur des 3 cartes d'action egalisee en mobile sur `/user/login` (JS, degradation gracieuse) ; boutons de « Mes informations personnelles » pleine largeur/meme hauteur en mobile et alignes a droite en desktop, apres correction d'une marge fantome sur les `<input type="submit">` non stylises (`appearance: none` + `margin: 0`, generalisee a `_reset.scss`, regle ajoutee au CLAUDE.md) ; bouton « Valider » du webform `account_request` en pleine largeur mobile. **A rejouer** : S19 (deconnexion, boutons) et S25 (redirection connexion, hauteur des cartes, bouton Valider) | S19, S25 |
| 2026-08-25 | **F12 — page « Mes informations personnelles » livree** ([ADR-026](../.claude/decisions/026-profil-partenaire-mes-informations.md), commit `aa3be74`, maquette Figma 524:20069) : nouveau module `drivematic_partner`, route `/user/mes-informations-personnelles` (role `partenaire`), `PersonalInformationForm` reprenant les champs du webform `account_request` (Civilite/Prenom/Nom/Fonction/Telephone modifiables ; e-mail + bloc « Votre entreprise » lecture seule — perimetre elargi par decision utilisatrice au-dela de la seule adresse de facturation prevue au PRD). 10 nouveaux champs sur l'entite `user`, exposes aussi en back-office. « Modifier mon mot de passe » → `/user/password`. **Restriction de securite** : `/user/{uid}/edit` (formulaire core partage) ne montre plus ces champs a un partenaire editant son propre compte (`hook_form_user_form_alter`), pour eviter un contournement. **Addendum le meme jour** : la meme page core `/user/{uid}/edit`, atteinte via le lien de definition de mot de passe de l'e-mail d'activation, a aussi ete stylisee et allegee (retrait Image/Langue/Fuseau horaire, e-mail en lecture seule) et redirige desormais vers « Mes informations personnelles » apres sauvegarde du mot de passe. **A rejouer** : S19 en entier (page + lien de mot de passe) | S19 |
| 2026-08-25 | **F12 — roles Admin/Partenaire et e-mail d'activation de compte partenaire** ([ADR-025](../.claude/decisions/025-roles-back-office-et-email-activation.md), maquette 810:10544) : role `content_editor` etoffe (CRUD sur les 16 types de contenu + medias) et relabellise **« Admin »**, ajoute en `view_any` sur les 3 webforms pour consulter les demandes de compte ; nouveau role **« Partenaire »** sans aucune permission back-office (authentification seule) ; e-mail `register_admin_created` reecrit selon la maquette via `mailer_policy`/`mailer_override`, teste bout-en-bout via Mailpit ; `password_reset_timeout` porte a 259200 (72 h). **A rejouer** : S12 (contenu de l'e-mail, lien 72h), S22 (creation du compte en tant qu'« Admin ») | S12, S22 |
| 2026-08-25 | **F12 — webform dedie `account_request` cree pour « Demande de creation de compte »** (maquettes 472:12922 / 602:33766) : remplace le partage temporaire du webform `partner` (addendum [ADR-024](../.claude/decisions/024-mutualisation-formulaire-simple.md)) — champs identite (civilite/prenom/nom/fonction/telephone/e-mail) + entreprise (siret/raison sociale/adresse/complement/code postal/ville), tous obligatoires sauf complement, sans placeholder. `drivematic_forms_update_11002()` rattache le node existant (nid 124) au nouveau webform ; message de confirmation et 2 e-mails (accuse + notification) ajoutes au meme gabarit ([ADR-022](../.claude/decisions/022-gabarit-email-webform.md)). ⚠️ Notifications internes des 3 webforms redirigees temporairement vers `audrey@passerelle.com` (a restaurer sur `info@drivematiclegrand.com` avant prod). **A rejouer** : S25 (demande de compte avec ses propres libelles/destinataires, distincts de « Devenir partenaire ») | S25 |
| 2026-08-25 | **F2/F11/F12 — la page de connexion (`/user/login`) est integree** ([ADR-024](../.claude/decisions/024-mutualisation-formulaire-simple.md), maquettes 472:12636 desktop / 602:33089 mobile) : nouveau SDC `login-panel` (carte de connexion + 3 cartes d'action), nouvelle fondation `_user-login-form.scss` (addendum [ADR-015](../.claude/decisions/015-habillage-des-formulaires.md)) pour habiller `user_login_form` (core, pas un webform). Bundle `partner` mutualise en `simple_form` (multi-instance) pour porter aussi la nouvelle page « Demande de création de compte ». **Decision actee** : 3 cartes aux deux gabarits (la maquette mobile n'en montre que 2, harmonisation demandee par l'utilisatrice). **Corrections du meme jour** (relecture face a la maquette) : largeur des champs/bouton alignee sur la maquette desktop (380px, pas 100% de la carte) ; alignement des libelles a gauche (pas centre, herite du conteneur) ; libelle « E-mail » (pas « Nom d'utilisateur »), collision de traduction fr rencontree (« Courriel ») ; lien « Mot de passe oublié » ajoute (absent de l'implementation initiale) ; boutons des 3 cartes alignes en bas via une grille `1fr auto` (texte centre dans l'espace restant) ; libelles « Demander un devis » et « Me connecter » (bouton, fil d'Ariane, titre d'onglet). **Nouveau scenario S25** | S25 |
| 2026-08-24 | **Transverse — bascule mobile-first des tokens** (addendum [ADR-013](../.claude/decisions/013-espacement-et-unites.md)) : `--dm-gutter`, `--dm-space-page` et l'echelle de titres (h1/h2/h3) ne portaient qu'une valeur desktop ; chacun recoit desormais une valeur mobile de base (mesuree sur les maquettes mobiles) et une surcharge `@media (width >= 992px)`. Repercute sur ~25 composants. `--dm-space-element` (24px) reste volontairement une valeur unique, y compris mobile/desktop. **A rejouer** : verifier le rythme mobile de toute page listee ci-dessous, en particulier gouttiere laterale et tailles de titre | Transverse (S1-S24) |
| 2026-08-24 | **F2 — corrections mobile du header** : le bouton « Demander un devis » du pied de tiroir mobile occupe desormais toute la largeur du menu (etait centre sur son contenu, maquette 526-23592) ; le sous-menu « Drive Matic » n'affiche plus ses filets entre les 3 groupes de liens en mobile (liste plate a ecart uniforme, maquette 526-23698) ; le bouton « Demander un devis » (barre + tiroir) pointe desormais vers `contact` (F10) au lieu de `configurator` (F14, inatteignable en anonyme). **A rejouer** : S1, tiroir mobile complet (racine + les 4 sous-panneaux) | S1 |
| 2026-08-21 | **F2 — le fil d'Ariane est stylise** ([ADR-023](../.claude/decisions/023-fil-ariane-style.md)) : nouvelle fondation `_breadcrumb.scss`, registre typographique repris de `pager` (liens gris, element courant acier gras, separateur `»` gris metallise). Ecart egal au-dessus et au-dessous (`--dm-space-element`, 24px), porte par le fil d'Ariane lui-meme — necessaire car les gabarits "hero" (`homepage`/`transform`/`product`) n'ont pas de bloc titre de page pour porter cet ecart ; `_page-title.scss` ne pose donc plus son propre `padding-block-start`. Alignement horizontal cale sur la boite du bandeau du header (plafond 1440px + gouttiere responsive 24/40px), pas sur la colonne de contenu : les deux ne coincident qu'en dessous d'environ 980px de large. **A rejouer** : S1, en desktop large (>1440px) et sur une page hero sans bloc titre | S1 |
| 2026-08-21 | **F8 — toute la ligne d'actualite devient cliquable** (pas seulement « Lire la suite ») : lien etire sur `news-teaser` (pseudo-element `::before` en `inset: 0` sur le lien existant, `.news-teaser` en `position: relative`) — un seul `<a>` par ligne, pas de lien imbrique/duplique. `news-card` (bloc home) n'a pas recu le meme traitement, non demande | S7 |
| 2026-08-21 | **F10, F11 — les 8 e-mails webform (contact x6, partenaire x2) sont stylises** ([ADR-022](../.claude/decisions/022-gabarit-email-webform.md), maquettes Figma « Modele Email... » 810:9388 et suivants) : gabarit HTML inline commun (logo PNG en URL absolue, sans encadre gris ni centrage — deviation deliberee de la maquette sur ce point, alignement a gauche impose), ordre unifie du bloc identite (Statut/Entreprise/Nom/Adresse/E-mail/Tel, separateur `-`), titres de section en capitales. **Ecarts maquette assumes** : ligne Adresse ajoutee sur les e-mails « demandeur » de devis/SAV/question et sur les 2 e-mails partenaire (absente de leur maquette, alors que le champ est collecte) ; ligne « Piece jointe » ajoutee aux 2 e-mails SAV (absente des maquettes devis/question). **Resolu au meme moment** : la piece jointe SAV est desormais reellement jointe (`attachments: true`), et non plus seulement mentionnee en texte. ⚠️ **Piege rencontre en cours de session** : un remplacement de texte partage entre plusieurs handlers du meme fichier YAML a fui vers 4 handlers non concernes (SAV/Question deja stylises differemment) — corrige avant commit, regle ajoutee au CLAUDE.md (verifier le nombre d'occurrences avant/apres toute edition de ce type) | S8, S9, S10, S11 |
| 2026-08-20 | **F2 — le header riche est integre** (maquettes desktop `433-7989` + 5 dropdowns, mobile `526-20394` + tiroir) : menu `main` (4 rubriques) charge directement via le service de menu (pas un `system_menu_block`), distinction carte/lien par `field_nav_card_image` sur `menu_link_content` ([ADR-021](../.claude/decisions/021-cartes-mega-menu.md)) ; menu `account` (5 liens) pour le dropdown « Espace partenaire », gate cote serveur par role. **3 bugs corriges le meme jour** (addendum ADR-021) : (1) le dropdown Drive Matic (sans carte) rendait ses 6 liens en une seule liste au lieu de 3 colonnes — l'arbre du menu passe a 3 niveaux, un enfant a enfants devient une colonne ; (2) l'ouverture du dropdown « Assistance » decalait de 10px les 3 AUTRES boutons du nav — un padding conditionnel changeait sa largeur dans un conteneur `justify-content: space-between`, remplace par un `::before` qui ne participe pas au flux (regle generale ajoutee au CLAUDE.md) ; (3) les flyouts ne couvraient que la largeur plafonnee du bandeau (1440px, centre) au lieu de toute la page — echappatoire `calc(50% - 50vw)` posee sur le panneau. **A rejouer** : S1 (menu principal, desktop et mobile) | S1 |
| 2026-08-20 | **F2 — le footer riche est integre** (maquettes desktop `303-5967`/nœud `317:6611` et mobile `526-20394`/nœud `526:23380`) : logo, coordonnees, 3 colonnes de liens (solutions auto-ecole, solutions PMR, assistance) via un nouveau menu custom `footer-solutions` (items de tete non cliquables, route `<nolink>`), 4 icones reseaux sociaux (Instagram, TikTok, LinkedIn, YouTube — la maquette mobile n'affichait encore que 3 icones au moment de l'integration, YouTube ajoute par coherence avec le desktop et le PRD) et 4 liens legaux via le menu core `footer`, desormais peuple. Les 14 liens ciblent des nodes reels deja publies (aucun lien invente). Logo et icones sociales extraits de Figma et vectorises en SVG mono-trait (`images/icons/{instagram,tiktok,linkedin,youtube}.svg`) pour le pattern `mask` + `currentColor`. **Ecart delibere avec la maquette** : pas de ligne de copyright (absente du design riche, presente dans l'ancienne coquille minimale). **Corrige au meme moment** : le titre « Restons connectes » sortait 3,2px plus haut que les 3 autres titres de colonne — le CSS du coeur pose `.menu-item { padding-top: 0.2em }`, neutralise depuis (voir CLAUDE.md). **A rejouer** : S1 est desormais verifiable pour de vrai (menu + footer) | S1 |
| 2026-08-20 | **F11 — le formulaire « Devenir partenaire » est mis en page** (maquette `438-9838`) : confirmation de l'effet de bord annonce par l'entree F10 ci-dessous — la grille manquait ses `#wrapper_attributes` (Civilite/Prenom/Nom sur leur propre ligne, message sur 2 colonnes, consentement pleine largeur), l'ecart titre -> formulaire etait absent (`.field--type-webform` sans `padding-block-start`, invisible sur `contact` ou `contact-intro` le pose deja), et les radios Oui/Non n'etaient pas stylees (seul champ `#type: radios` du site). Les 4 placeholders restants ont ete retires | S11 |
| 2026-08-20 | **`legals` — le body des pages legales est mis en page** (maquette `469-11689`, node--legals.html.twig + SDC `legal-text`) : colonne 1130 (pas les 960px retunes par l'ADR-019, restes non consommes — signale dans `_tokens.scss`), ecarts titre -> body et body -> footer sur `--dm-space-page`, titres de section en bleu acier 22/32. **Contenu du node 55 (CGV) corrige** : le texte migre par l'ADR-019 ne portait aucune balise `<p>` (texte brut aplati par le navigateur, phrases distinctes fusionnees) ; 38 paragraphes reinseres par script verifie (round-trip automatique avant enregistrement), aucun mot modifie. **A rejouer** : le meme controle (`innerHTML` du `.text-formatted`, pas seulement le rendu visuel) sera necessaire sur les 3 autres pages legales quand leur `body` sera redige | Hors matrice (cf. entree du 2026-08-20 ci-dessous) |
| 2026-08-20 | **F10 — la ligne « adresse + visuel » au-dessus du formulaire de contact est integree** (maquettes `433-7637`/`438-9060`/`438-9465`) : SDC `contact-intro` (2 colonnes), et le visuel du node `contact` (`field_photo`) impose desormais un **crop 16:9** — il en etait exempte jusque-la (addendum [ADR-018](../.claude/decisions/018-images-locales-par-paragraphe.md)). Marge sous la carte du formulaire alignee sur `--dm-space-page` (meme rythme que le pager avant le footer), effet de bord verifie et attendu sur `/devenir-partenaire` (meme fondation `_forms.scss`). **A rejouer** : le recadrage 16:9 du visuel reste a poser manuellement en back-office avant publication (regle du recadrage manuel, jamais par script) | S8, S9, S10, S11 (marge du formulaire) |
| 2026-08-20 | **`legals` — le type passe des paragraphes a `body` + `field_meta_tags`**, comme tout autre type public : `text_left_aligned` retire, contenu des CGV (node 55) migre dans `body` (verifie sans perte, cf. [ADR-019](../.claude/decisions/019-legals-body-metatags.md) et `docs/archive/legals-body-verification.md`), colonne de contenu alignee sur 960px. Les 3 autres pages legales du footer (CGU, mentions legales, donnees personnelles — F2) existent desormais comme nodes distincts, `body` vide (contenu editorial a saisir). **A rejouer** : les 4 pages `legals` n'ont pas de scenario S1-S24 dedie — verifier a la creation d'un parcours footer (F2) que chacune affiche titre + body + balise meta description | Hors matrice (a rattacher a F2 quand le footer riche sera livre) |
| 2026-08-20 | **Transverse — la video en facade se lance desormais avec le son au clic** (`video-facade.js`) : `video_embed_field` force `mute=1`/`muted=1` des que l'autoplay est actif (necessite technique du rendu serveur statique, sans interaction utilisateur) ; le clic sur la facade **est** cette interaction, le behavior JS leve donc le mute et delegue la permission autoplay a l'iframe a ce moment-la (respecte le cas ou le serveur a deja refuse l'autoplay). ⚠️ Le compte **UID 1** (admin) a automatiquement **toutes** les permissions Drupal, dont celle qui desactive l'autoplay : ne pas rejouer ce scenario en administration, verifier en **anonyme**. A rejouer sur les trois paragraphes video (`video_centered`, `history_element`, `product_video_element`) | S3, S4, S7 (tout paragraphe video) |
| 2026-08-20 | **Transverse — debordement horizontal de 20px corrige sur les pistes qui debordent au bord de la fenetre** (`history`, `jumbo_home`, `news_home`, `product_features`) : le `calc(50% - 50vw)` pose sur un **enfant** du conteneur (pas le conteneur lui-meme) resolvait son `%` contre un bloc de contenu plus etroit de `gutter` que la largeur totale centree — invisible a l'oeil (scrollbars overlay), mesurable via `document.documentElement.scrollWidth`. A rejouer en mesurant, pas a l'oeil | S2, S3, S4 (tout slideshow a piste debordante) |
| 2026-08-19 | **F9 — les 4 pages corporate sont integrees** (Qui sommes-nous, Nos ateliers, Recherches et developpement, Savoir-faire et certifications) : chapo dans `page-intro` via un nouveau `node--corporate.html.twig` (le bundle n'avait aucun template dedie, le chapo tombait dans le rendu generique sans style) ; widget de recadrage retabli sur le mode de formulaire `media.image.media_library` (il portait `image_image`, sans crop, alors que c'est **ce mode** qu'emprunte tout ajout de media en contexte — personne ne l'avait remarque, tous les medias existants avaient ete crees par script). ⚠️ **Chantier plus profond decouvert en corrigeant le crop** : le recadrage Drupal est rattache au **fichier**, pas a l'usage — reutiliser une image en mediatheque a deux ratios differents impose le meme cadrage aux deux. Resolu par [ADR-018](../.claude/decisions/018-images-locales-par-paragraphe.md) : les 9 paragraphes a ratio impose + `news.field_photo` (renomme) passent en **champ image local sans mediatheque**. Migration verifiee 104/104, 0 perte. ⚠️ **A rejouer** : reutiliser la meme photo dans deux paragraphes `image_text_50` **necessite deux imports** (plus de selection media) ; et `field.storage.paragraph.field_image` (bug trouve et corrige le meme jour) ne porte plus que les 3 bundles sans ratio — un nouveau paragraphe image doit etre range dans la bonne liste (CLAUDE.md, section Media/images) | S3, S4 (blocs reutilises), transverse (titre/chapo, pipeline images) |
| 2026-08-19 | **F6 — la page Documentations est integree** (maquette `398-12119`) : chapo dans `page-intro`, sections en **liste zebree** (SDC `documents-list`), chaque fichier en **ligne entierement cliquable**. Les deux champs Fichier ne passent plus par leur formatter — masques dans le view display, rendus par `drive_matic_preprocess_node()` (nouveau helper `_drive_matic_field_downloads()`, cache tags des fichiers reattaches). ⚠️ Rendu distinct du bouton contour+icone de `product_characteristics`, malgre la reutilisation anticipee par [ADR-009](../.claude/decisions/009-telechargements-nommes.md) (addendum 2026-08-19) : la maquette reelle n'avait pas ete revue au moment de cette decision | S5 |
| 2026-08-19 | **F8 — le detail d'une actualite est integre** (maquette `438-10665`) : SDC `news-article`, date centree, legende ferree a droite, corps de texte ajoute (absent de la maquette), et une **seule colonne de 960** pour toute la page ([ADR-016](../.claude/decisions/016-colonne-de-contenu.md)). Nouveaux comportements a rejouer : **le visuel du detail n'est plus recadre** (ratio du fichier source) alors que la vignette de la liste et de la home reste en 16:9 — donc **deux ratios pour une meme image, ce n'est pas un bug** ; et l'ecart entre deux blocs passe de 80 a **64** partout ou un `<figure>` etait racine de SDC (`video_centered`, `image_centered`), le `margin: 1em` du navigateur n'etant pas couvert par le reset | S7, S3 et S4 (blocs video/image centres) |
| 2026-08-18 | **F10 — le formulaire de contact est habille** ([ADR-015](../.claude/decisions/015-habillage-des-formulaires.md)) : carte claire, grille 3 colonnes, deux groupes titres, mention obligatoire en pied, et les infobulles « carte grise » remplacees par une **modale illustree**. Rejouer les **trois variantes** (devis / SAV / question) et le **repli sans JS**. ⚠️ Deux prealables d'environnement decouverts : `file_private_path` (sans lui le champ document **n'existe pas**, en silence) et `upload_max_filesize` du PHP qui sert le site | S8, S9, S10 |
| 2026-08-18 | **F8 — la liste d'actualites est integree** (maquette `438-10209`) : titre de page **centre** (le bloc n'avait aucun CSS, sur **toutes** les pages), colonne de 1130, ligne visuel 325x183 + texte, pagination stylee. Jeu de test porte a **32 actualites**, soit 4 pages : la pagination devient verifiable | S7, transverse (titre de page) |
| 2026-08-18 | **Recadrages remis a plat** : aucune entite `crop_12_5` n'existait, donc les 17 blocs `image_full` du site sortaient au ratio de leur source. Audit sur les 10 emplacements a ratio impose : 45 couples (fichier, ratio) conformes, 31 images verifiees sur les 29 pages publiques, 0 ecart. ⚠️ **A rejouer en mesurant** `width`/`height` sur le `<img>` : un heros 12:5 en 1440 doit faire **600** de haut, jamais 960 | Transverse, S2 a S7 |
| 2026-08-18 | **Pages 12 et 13 creees** (« Recherches et developpement », « Savoir-faire et certifications ») et **pages transform/produit recalees** sur leurs maquettes : photos importees de Figma, accordeons revenus au lorem de la maquette, blocs manquants ajoutes (`text_centered` de la page VOR, telechargements des caracteristiques) | S3, S4 |
| 2026-08-17 | **Rythme visuel** ([ADR-013](../.claude/decisions/013-espacement-et-unites.md)) : ecarts internes uniformises a 24px, blocs separes de 64px, gouttiere de 40px garantie **a toute largeur**. A rejouer : (1) **en responsive** — aucun bloc ne doit toucher le bord, y compris le jumbo, la grille et les actualites, dont la piste deborde a droite par construction ; (2) un bloc dont **seul le titre** est rempli ne doit pas laisser de blanc en dessous ; (3) le degrade du carrousel de marques ne s'affiche **que du cote ou la fleche est active** ; (4) avec une **police de navigateur agrandie**, le texte grossit et la mise en page ne bouge pas | Transverse, S2 en premier lieu |
| 2026-08-17 | **Le site passe en francais** (decision #6) : dates d'actualites (« 17 aout 2026 »), poids de fichiers (« 50 Ko »), barre d'administration, onglets locaux et fil d'Ariane sont desormais en francais. Le fil d'Ariane affiche le **titre affiche** et non le libelle d'administration. ⚠️ **A rejouer apres tout transfert de base** : les alias portent le langcode du contenu, une base venue d'un site en anglais fait retomber **toutes** les pages sur `/node/N` (cf. PRD section 7) | Transverse, S1 a S24 |
| 2026-08-17 | **Administration sur le front** ([ADR-012](../.claude/decisions/012-presentation-admin-front.md)) : `gin_toolbar` desinstalle — la barre du front redevient celle du cœur (noire « Gerer / admin » + menu horizontal d'Admin Toolbar) —, et les onglets locaux passent en **carte grise fixe en haut a droite**, qui suit le defilement. A rejouer **connectee en administration** sur une page publique. Le crayon qui surmonte la carte vient des liens contextuels, il n'apparait qu'au survol | Transverse, toute page publique |
| 2026-08-17 | **F3 — la home est composee d'apres la maquette** (8 blocs, visuels et libelles repris de Figma). S2 devient verifiable pour de vrai : titre generique, 2 bannieres avec fleche et points, grille des solutions 3/2/2 liens, bloc configurateur, actualites, marques, savoir-faire, accordeons SEO. ⚠️ Huit liens restent des placeholders vers `/` | S2 |
| 2026-08-17 | **F7 / F8 — jeux de demonstration alignes sur la maquette** : 12 marques avec leurs logos reels (4 factices supprimees, 3 renommees) et les 6 actualites illustrees par les 3 photos de la maquette | S2, S6, S7 |
| 2026-08-17 | **Transverse — titre affiche distinct du libelle d'administration** (ADR-011) : le champ « Titre » porte ce que voit l'internaute (`h1`, balise `title`, alias) ; le « Titre administratif » ne sert qu'au back-office. **A rejouer en BO** : saisir deux valeurs differentes et verifier que le front n'affiche jamais la valeur d'administration — page, onglet du navigateur, carte d'actualite, ligne de liste. L'alias suit desormais le titre affiche (et une modification cree une **redirection 301** depuis l'ancien) | Toute page publique (S1-S7, S24) + recette editoriale |
| 2026-08-17 | **F4/F5/F6/F7/F8/F9 — les 10 types de contenu restants sont livres**, avec leurs pages : solutions, produit, corporate, mentions legales, liste + detail d'actualites, FAQ, documentations, marques. Les scenarios S3 a S7 deviennent **rejouables pour de vrai** ; **S24 (FAQ)** est ajoute | S3, S4, S5, S6, S7, S24 |
| 2026-08-17 | **F18 — sitemap configure par type** : les 12 nodes publics inclus, les 3 fragments et le bac a sable exclus, l'accueil declaree en lien personnalise sur `/`. A rejouer : `sitemap.xml` ne doit contenir **aucune** URL de fragment | S1 a S7, S24 |
| 2026-08-11 | Creation initiale a partir du PRD (F1-F18) | S1-S23 |
| 2026-08-17 | **F18 / transverse** — **balises meta** : chaque node public sort `titre \| nom du site` en title et un extrait du corps en description ; champ « Balises meta » pour surcharger (vide = automatique). A rejouer en BO **et** dans le `<head>` du rendu, en particulier sur la **page d'accueil** (cas special `front`, cf. ADR-010) | S1 a S7 (toute page publique) |
| 2026-08-17 | **BO / transverse** — formulaire de contenu unifie : **deux onglets horizontaux** (« Informations generales » / « Contenu », paragraphes en dernier), champs `uid`/`created`/`simple_sitemap`/`url_redirects` retires, alias d'URL auto-genere depuis le titre sur Contact / Devenir partenaire / Detail d'une actualite. A rejouer a la creation d'un contenu | Recette editoriale (hors S1-S23) |
| 2026-08-17 | **Transverse** — une **seule apparence de fleche de defilement** pour tous les paragraphes (export des calques « Next » de la Home : Group 4 = suivant, Group 5 = precedent). Changement visible : les fleches du **jumbo** passent de la plaque blanche a la plaque grise. A rejouer sur les 5 slideshows | S2, S3, S4 (tout slideshow) |
| 2026-08-17 | F5 — integration maquette `product_cross` (Figma 396:11619). **Termine la bibliotheque : les 18 blocs sont integres.** Nouveau comportement a rejouer : la ligne cliquable d'une carte porte le **titre** de la carte, pas le libelle du champ lien — dont la **cible d'ouverture** reste respectee | S4 (fiche produit) |
| 2026-08-17 | F5 — integration maquette `product_features` (Figma 396:11618). Nouveaux comportements a rejouer : **fleche centree sur le visuel** et non sur la diapositive, **façade video** (plaque translucide + glyphe, iframe au clic) dans une diapositive de slideshow, bouton gris issu du champ lien | S4 (fiche produit) |
| 2026-08-17 | F3 — integration maquette `brands_home` (Figma 303:6307). **Termine la home.** Nouveaux comportements a rejouer : rangee de tuiles carrees, **fleche a chaque extremite**, tuiles qui **passent a la ligne** sans JS, et **nom de marque masque visuellement** mais lisible au lecteur d'ecran | S2 (blocs home), S6 (marques) |
| 2026-08-17 | F3 — integration maquette `news_home` (Figma 303:6302). Nouveaux comportements a rejouer : titre **centre** + paire de fleches a droite dans le meme en-tete, **apercu coupe** de la carte suivante, lien « voir toutes » en **bouton gris centre** sous la piste, carte reduite au **visuel + titre** | S2 (blocs home) |
| 2026-08-14 | **Transverse** — le libelle de **tout** bouton de telechargement devient editorial : champ « Libellé du bouton de téléchargement » saisi sous le fichier, **obligatoire** des qu'un document est joint (un champ fichier vide n'en demande pas) ; format et poids restent calcules. A rejouer partout ou un fichier est propose (ADR-009) | S3, S4, S5, S9 (tout bouton de telechargement) |
| 2026-08-14 | F5 — integration maquette `product_characteristics` (Figma 390:11137). Nouveaux comportements a rejouer : bandeau sombre pleine largeur, grille de caracteristiques 2 colonnes, et **boutons de telechargement nommes** — libelle **saisi en BO** (description du fichier, repli sur le nom du fichier), la ou le bouton global affiche « Télécharger » (ADR-009) | S4 (fiche produit) |
| 2026-08-14 | F9 — integration maquette `history` (Figma 433:9747). Nouveaux comportements a rejouer : fleches **dans l'en-tete** du bloc (hors piste), filet pointille debordant, **apercu coupe** de l'entree suivante, visuel **sous** le texte, fleche en bout de course **atenuee et inerte**. Corrige au passage l'apercu de `jumbo_home`, qui ne debordait pas (specificite `.swiper`) | S3 (frise history), S2 (jumbo) |
| 2026-08-14 | F3 — integration maquette `jumbo_home` (Figma 303:5967). Nouveaux comportements a rejouer : piste calee a gauche sur le conteneur et debordante a droite (**apercu coupe** de la diapo suivante), **points de pagination** cliquables, **fleche en bout de course masquee** (pas de boucle). Sans JS : points **et** fleches masques | S2 (blocs home) |
| 2026-08-12 | F10/F11 implementes (webforms contact & partenaire, pages /contact & /devenir-partenaire) — comportements inchanges, scenarios toujours valides | S8-S11 |
| 2026-08-13 | F1 — vague V1 paragraphes (text_left_aligned, image_text_50/100, image_centered, image_full, video_centered). Building blocks transverses ; nouvelle interaction a rejouer quand les templates seront construits : **video en facade** (miniature 16:9 → clic → iframe chargee seulement alors, accessible clavier) | S3, S4, S7 (F1) |
| 2026-08-13 | F1 — vague V2 paragraphe **accordion** (+ `accordion_element`). Fournit le building block des **accordeons SEO** (home) et **FAQ** : disclosure ARIA, fermeture du precedent a l'ouverture, ferme par defaut sans flash, accessible clavier, degrade ouvert sans JS. Interaction deja decrite dans les scenarios existants ; a rejouer une fois les templates home/FAQ construits | S3 (SEO home), S4/S9 (FAQ) |
| 2026-08-13 | F1 — vague V3 paragraphes **grid**, **triptych**, **history** (+ elements). Building blocks editoriaux (pages « Drive Matic »/solutions). Nouvelle interaction a rejouer : **slideshow** (`history`, puis carrousels V4) — navigation **fleches + clavier**, **`prefers-reduced-motion`**, **repli en liste** si un seul item, empilement lisible **sans JS** ; la **facade video** (ADR-006) s'applique aussi a `history_element` en mode video | S2 (carrousel marques), S3 (history corporate) |
| 2026-08-13 | F1/F3 — vague V4 home : paragraphes **jumbo_home** (+element), **news_home**, **brands_home** + prerequis content-types **news** (image 16:9) et **brand** (fragment, canonique **403** Rabbit Hole, hors sitemap) + Vues `news_home`/`brands_home`. S2 enrichi : slideshows home (jumbo 2-3 / repli a 1, 5 actus recentes, marques alpha non cliquables). A rejouer une fois le vrai node `homepage` construit | S2 (blocs home) |
| 2026-08-13 | F1/F5 — vague V5 produit : paragraphes **product_arguments** (3 titres), **product_features** (+`product_image_element`/`product_video_element`, slideshow « swipe »), **product_characteristics** (+element, image sans crop + notice/doc), **product_cross** (+element, cartes liees). **Bibliotheque ADR-001 complete (27 paragraphes).** Interactions a rejouer une fois le template **page produit** construit : slideshow features (fleches/clavier, repli a 1), **facade video** sur `product_video_element`, telechargements notice+documentation, cross-selling lie ; **aucun prix** affiche | S5 (page produit, F5) |
| 2026-08-13 | F3 — Home page + shell minimal : type de contenu **`homepage`** (allowlist paragraphes home) + template dedie **`node--homepage`** + SDC **`site-header`/`site-footer`** ; node home servi a **`/`** ; **fil d'Ariane et titre masques sur la home**, presents sur les pages internes. S2 devient **rejouable pour de vrai** (verifie : header/footer, breadcrumb absent home / present interne, titre de node absent home, paragraphes rendus, metatag title). Reste a rejouer avec du contenu media : slideshows `jumbo_home`/`news_home`/`brands_home` (validés en V4, inchangés). Menu multi-niveaux + footer riche = **F2** | S1 (breadcrumb), S2 (home) |
