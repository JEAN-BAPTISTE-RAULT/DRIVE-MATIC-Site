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

---

## S3 — Page solution (auto-ecole / PMR)

**Objectif** : Verifier le template de page solution et l'acces aux produits/configurateur.

**Etapes** :
1. Ouvrir « Transformer un vehicule en auto-ecole ».
2. Cliquer sur un bloc produit, revenir, cliquer sur le bloc configurateur.
3. Ouvrir un accordeon FAQ.

**Resultats attendus** :
- Image 100 % largeur, bloc informations generales, 3 a 6 blocs produits avec liens, bloc configurateur, FAQ (fermeture du precedent a l'ouverture).

---

## S4 — Fiche produit (sans prix)

**Objectif** : Verifier le template produit et l'absence de prix en public.

**Etapes** :
1. Ouvrir une fiche produit (ex. « Telecommande VOR »).
2. Parcourir : argumentaires, swipe, caracteristiques techniques, cross-selling.
3. Telecharger une notice technique.

**Resultats attendus** :
- Bloc swipe limite a 5 elements ; cross-selling 1 a 5 produits avec liens.
- Notice/documentation affichent **nom + format + poids** (calcul auto).
- **Aucun prix** n'est affiche sur la page produit publique.

---

## S5 — Documentations & telechargement

**Objectif** : Verifier la page documentations et le telechargement.

**Etapes** :
1. Ouvrir la page Documentations.
2. Verifier les listes Auto-ecole et PMR, telecharger un document.

**Resultats attendus** :
- Documents presentes avec nom/format/poids, dans l'ordre defini en back-office.
- Le telechargement fonctionne.

---

## S6 — Marques partenaires

**Objectif** : Verifier l'affichage des marques.

**Etapes** :
1. Ouvrir la page Marques partenaires.

**Resultats attendus** :
- Liste de logos en **ordre alphabetique** + bloc informations generales.

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
| Transverse (S1-S23) | F1 (Paragraphes), decision #8 (RGAA/WCAG AA) |

## Historique des modifications

| Date | Modification | Scenarios impactes |
|------|--------------|---------------------|
| 2026-08-11 | Creation initiale a partir du PRD (F1-F18) | S1-S23 |
