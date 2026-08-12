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
- [ ] Le menu reprend l'arborescence des specs (rubriques niveau 1 et 2).
- [ ] Le fil d'Ariane est present sur toutes les pages **sauf** la home page.
- [ ] Le footer contient : coordonnees, solutions auto-ecole/PMR, assistance (contact, FAQ), reseaux sociaux (Instagram, TikTok, LinkedIn, YouTube), et les liens legaux (CGV, CGU, mentions legales, donnees personnelles).
- [ ] Drive Matic peut creer des rubriques de **niveau 2** et des pages en autonomie ; la creation de rubriques de **niveau 1** requiert une intervention CSS de Passerelle.

**Cas limites** :
- Item « Espace partenaire » : affiche le sous-menu authentifie (Tableau de bord, Mes devis, Mes informations personnelles, Me deconnecter, Supprimer mon compte) **uniquement** pour un partenaire connecte.

---

### F3 : Home page

**Trigger** : Quand un visiteur arrive sur la page d'accueil.

**Action** : Il parcourt les blocs specifiques de la home (template dedie, non composable librement).

**Resultat attendu** : Une vitrine synthetique de l'offre, des actualites et des marques.

**Criteres d'acceptation** :
- [ ] Blocs presents : titre generique ; jusqu'a 3 jumbos (visuel + titre + CTA optionnel) ; bloc solutions auto-ecole & PMR (3 blocs produits avec liens) ; bloc configurateur ; bloc actualites (5 plus recentes + lien « voir toutes ») ; carrousel marques partenaires (ordre alpha, navigation fleches) ; bloc image a gauche fond gris ; accordeons SEO.
- [ ] Accordeons SEO : l'ouverture d'un accordeon ferme le precedent.

**Cas limites** :
- Moins de 5 actualites publiees → le bloc affiche celles disponibles. `[INFERE]`

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
- [ ] Template commun a tous les produits : image 100 % largeur ; bloc argumentaires (image/texte) ; bloc swipe (max 5, visuel ou video + titre + texte + CTA) ; bloc caracteristiques techniques (donnees titre/texte + notice technique et documentation telechargeables avec nom/format/poids auto) ; bloc titre + CTA ; bloc configurateur ; bloc cross-selling (1 a 5 produits avec lien vers fiche).
- [ ] **Aucun prix** n'est affiche sur les pages produit publiques.

---

### F6 : Pages Documentations & F7 : Page Marques partenaires

**Criteres d'acceptation** :
- [ ] **Documentations** : template dedie listant les documents Auto-ecole et PMR, ordre gere en back-office, chaque document affiche nom/format/poids (calcul auto).
- [ ] **Marques partenaires** : bloc informations generales + liste des marques sous forme de logos, **ordre alphabetique**.

---

### F8 : Actualites (liste + detail)

**Trigger** : Quand un visiteur consulte les actualites.

**Criteres d'acceptation** :
- [ ] **Liste** : derniere publiee/modifiee en tete ; chaque item = photo principale + titre + date + lien « Lire la suite » ; pagination **10 par page**.
- [ ] **Detail** : titre, date, visuel principal, blocs (titre/texte/lien/document/video optionnels), possibilite d'ajouter des paragraphes texte/image/video.
- [ ] Une actualite dispose d'une fonction publier / ne pas publier en back-office.

---

### F9 : Pages editoriales « Drive Matic » & FAQ

**Criteres d'acceptation** :
- [ ] Pages composables via Paragraphes (F1) : Qui sommes-nous, Nos ateliers, Recherche & developpement, Savoir-faire et certifications.
- [ ] FAQ : accordeons (fermeture du precedent a l'ouverture).

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
- [ ] Les e-mails suivent les modeles des specs (objet, expediteur `no-reply`, contenu) pour chacun des 3 cas.

**Cas limites** :
- « Vous etes = particulier » → le nom d'entreprise n'est pas obligatoire.
- Document SAV hors format/taille → rejet avec message d'erreur. `[INFERE]`

> NB : cette « demande de devis » publique est un **e-mail de demande** (pas de chiffrage) — distincte du devis chiffre du configurateur partenaire (F14/F15).

---

### F11 : Formulaire « Devenir partenaire »

**Trigger** : Quand un prospect demande a devenir partenaire.

**Action** : Il renseigne entreprise, adresse, identite, contact, la question « Etes-vous amenageur qualifie vehicule auto-ecole ? » (Oui/Non), un message ; coche l'autorisation ; resout le captcha ; envoie.

**Resultat attendu** : Message de confirmation a l'ecran + double e-mail (accuse a l'internaute + notification a `info@drivematiclegrand.com`). **Aucun compte n'est cree automatiquement** (cf. decision #4).

**Criteres d'acceptation** :
- [ ] Champs obligatoires marques `*` ; captcha ; case d'autorisation.
- [ ] Message de confirmation affiche : « Votre demande de partenariat a bien ete envoyee !... ».
- [ ] E-mails conformes aux modeles des specs.

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
Le modele de contenu editorial (types de contenu, taxonomie, mapping paragraphes) est acte dans [ADR-002](../.claude/decisions/002-types-de-contenu.md) et detaille dans `docs/active/content-types/model.md` : **12 nodes publics** (`homepage`, `transform`, `product`, `faq`, `documents`, `corporate`, `brands`, `contact`, `partner`, `legals`, `news`, `all_news`), **3 nodes « fragments »** sans page publique (`question`, `document`, `brand` — hors sitemap, URL bloquee), **1 taxonomie** (`categories`). Conventions transverses : champ « lien » interne/externe + cible d'onglet ; « fichier telechargeable » avec nom/format/poids ; metatags (body→description, titre→meta title) ; sitemap = nodes inclus / entites exclues.

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

### Prealables techniques (avant le developpement front)

Livrables a produire et acter (README/ADR) **avant** la production des templates :

1. **Etude prealable de gestion des images** (cf. decision #11), etablissant :
   - les **ratios de crop** (definis par la bibliotheque de paragraphes — ADR-001 : **1:1, 16:9, 12:5**, + cas « sans crop » a largeur fixe) → declinaisons responsive a produire ;
   - la **liste des images cropables en back-office** a l'import ;
   - les **image styles** a appliquer + leurs **declinaisons responsive**, en accord avec la **liste des breakpoints** du site (definie en section 6) ;
   - la regle d'**optimisation WebP** appliquee a chaque image importee ;
   - l'organisation de la **media-library reutilisable** (stockage, nommage, reutilisation).
2. **Mise en place du socle SDC** (cf. decision #10) : structure des composants, conventions de props/slots, fondations globales (reset, tokens, typographie), avant la production des templates.
3. **Etude de rationalisation des Paragraphes** (cf. F1) : les modeles decrits dans les specs ne sont pas tous necessaires et imposent parfois des contraintes techniques non justifiees. Recouper chaque modele avec les **maquettes validees** (verite visuelle) pour ne retenir que l'utile, ecarter le superflu, **puis optimiser** la bibliotheque (mutualisation, variantes via props plutot que duplication, mapping vers SDC). Livrable a acter (ADR/README) avant la production des paragraphes.
