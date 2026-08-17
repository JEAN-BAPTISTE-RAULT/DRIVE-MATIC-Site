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
1. Depuis la home, ouvrir le menu et deployer les rubriques niveau 2 (Auto-ecole, Vehicule PMR, Drive Matic).
2. Acceder a une page de niveau 2 (ex. « Double-pedalier »).
3. Observer le fil d'Ariane, puis parcourir les liens du footer (solutions, assistance, reseaux sociaux, liens legaux).

**Resultats attendus** :
- Le menu reflete l'arborescence du PRD ; les liens mènent aux bonnes pages.
- Le fil d'Ariane est present sur la page de contenu **et absent sur la home**.
- Le footer expose coordonnees, solutions auto-ecole/PMR, assistance (contact, FAQ), reseaux sociaux et liens legaux.

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
- `news_home` (integration maquette) : titre **centre sur le conteneur** avec la **paire de fleches a droite** (bord droit aligne sur le conteneur) ; piste calee a gauche et **debordante a droite** → carte suivante en **apercu coupe** ; lien « voir toutes » en **bouton gris centre** sous la piste. Carte = visuel 16:9 arrondi + titre + « Lire la suite » (chevron rouge) — **ni date ni chapo**. Fleche en bout de course **atenuee et inerte** (l'en-tete ne se reorganise pas). Sans JS : cartes empilees pleine largeur, fleches masquees.
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

**Mise en oeuvre (a rejouer)** :
- La page porte **deux champs reference ordonnes** ; les **libelles de champ** font les titres de section (« Auto-ecoles », « PMR »). Reordonner en BO doit reordonner la page.
- **Section vide** → ni titre de section, ni espace mort.
- Un document est un **fragment** : `/node/<id>` d'un document doit repondre **403** en anonyme.
- Le libelle du bouton est la **description du fichier** (ADR-009), pas le titre du node — d'ou une saisie du nom a deux endroits, point ouvert du PRD (F6).

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
- Le lien **« Voir toutes les actualites »** de la home mene bien a `/actualites`.
- **0 actualite publiee** → « Aucune actualite n'est publiee pour le moment. », sans bloc casse.
- **Depublier une actualite** doit la retirer de la liste **sans vidage de cache**.
- ⚠️ La date sort aujourd'hui en **anglais** (« 17 August 2026 ») : ecart de langue du site, cf. PRD section 7.

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
- Les champs `*` sont obligatoires ; l'infobulle « carte grise » s'affiche sur les champs chassis.
- La soumission est **enregistree en back-office** (consultable).
- Deux e-mails partent (accuse au demandeur + notification `info@`), conformes au modele « demande de devis ».

**Cas limites** :
- « Vous etes = particulier » → le nom d'entreprise n'est **pas** obligatoire.

---

## S9 — Formulaire de contact : SAV avec piece jointe

**Objectif** : Verifier le cas SAV et l'upload de document.

**Etapes** :
1. Choisir « demande de SAV », renseigner marque/modele/motorisation, n° de chassis, message.
2. Joindre un document valide (PDF/JPG, <= 5 Mo), envoyer.

**Resultats attendus** :
- Soumission stockee + e-mails SAV (demandeur + `info@`) avec la piece jointe referencee.

**Cas limites** :
- Document hors format ou > 5 Mo → **rejet avec message d'erreur** ; tentative d'ajouter un 2e document → bloquee (1 max). `[INFERE]`

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
- Le **tarif remise** applique correspond au taux par defaut du partenaire.
- Les totaux par vehicule, par configuration et general sont exacts (TVA 20 %).
- Les frais de livraison **ne figurent pas** dans le devis.
- Le devis recoit un numero `WAAAAMMJJ-001` et le statut « a finaliser ».

**Cas limites** :
- Tentative d'ajouter une **11e** configuration → bloquee (max 10).
- Quantite retrovision exterieure hors bornes 1-2 → refus. `[INFERE]`

---

## S15 — Adresses de livraison

**Objectif** : Verifier la gestion des adresses a l'etape Livraison.

**Etapes** :
1. A l'etape Livraison, verifier l'adresse de **facturation** (non modifiable en front).
2. Choisir une adresse de livraison existante, puis en ajouter une nouvelle, puis en modifier une.

**Resultats attendus** :
- L'adresse de facturation est en lecture seule.
- Une adresse de livraison ajoutee/modifiee en front est **automatiquement enregistree en back-office**.

---

## S16 — Finaliser & commander un devis

**Objectif** : Verifier le passage a la commande (statut, message, e-mail + PDF).

**Etapes** :
1. Depuis « Mes devis / commandes en cours », ouvrir un devis « a commander » et cliquer « Commander ».

**Resultats attendus** :
- Message « Felicitations, votre commande a bien ete enregistree... ».
- Statut passe a « Commande le jj/mm/aaaa » ; le montant HT s'affiche.
- E-mail de confirmation **avec PDF du devis** (au partenaire + `info@`).

---

## S17 — Remise exceptionnelle

**Objectif** : Verifier l'application d'une remise exceptionnelle sans alterer le taux par defaut.

**Etapes** :
1. Partenaire : disposer d'un devis au statut « a commander » (non commande).
2. Admin (back-office) : attribuer une remise temporaire par ligne sur ce devis.
3. Partenaire : rouvrir le devis.

**Resultats attendus** :
- Le devis reflete la nouvelle remise ; le **taux de remise par defaut du client reste inchange**.
- La remise n'est possible que **tant que le devis n'a pas ete commande**.

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

---

## S19 — Mes informations, mot de passe perdu & suppression de compte

**Objectif** : Verifier la gestion de compte cote partenaire.

**Etapes** :
1. Consulter/modifier « Mes informations personnelles ».
2. Tester « Mot de passe perdu ».
3. Declencher « Supprimer mon compte » et confirmer.

**Resultats attendus** :
- L'adresse de facturation reste **non modifiable** en front.
- « Supprimer mon compte » supprime le compte **et anonymise les devis/commandes associes** (conserves de maniere anonyme).

---

## Securite & cloisonnement

## S20 — Cloisonnement : anonyme bloque sur les ressources partenaire

**Objectif** : Verifier que l'autorisation est re-verifiee cote serveur (decision #5).

**Etapes** :
1. Se deconnecter (ou session anonyme).
2. Acceder **directement** aux URL de l'espace partenaire (tableau de bord, un devis, PDF d'un devis, configurateur) en devinant/collant l'URL.

**Resultats attendus** :
- Chaque acces est **refuse cote serveur** (redirection connexion ou 403).
- **Ne doit PAS observer** : aucune donnee partenaire (devis, montants, adresses, PDF) exposee a l'anonyme, ni dans le HTML, ni dans `drupalSettings`, ni via une URL directe.

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
1. Admin (Gin) : creer un compte partenaire, renseigner conditions commerciales (taux de remise par defaut), adresse de facturation.
2. Verifier l'envoi automatique de l'e-mail d'activation.
3. Modifier puis suspendre le compte.

**Resultats attendus** :
- L'e-mail d'activation (lien 72 h) part automatiquement a la creation.
- Les conditions commerciales sont prises en compte par le configurateur (cf. S14).
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
| S17, S22 | F16 |
| S20, S21 | F12, decision #5 (cloisonnement) |
| S23 | F18 |
| S24 | F9 (volet FAQ) |
| Transverse (S1-S24) | F1 (Paragraphes), decision #8 (RGAA/WCAG AA) |

## Historique des modifications

| Date | Modification | Scenarios impactes |
|------|--------------|---------------------|
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
