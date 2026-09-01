# ADR-035 : Recapitulatif en lecture seule des adresses de livraison sur `/user/{uid}/edit`

## Statut
Accepte

## Date
2026-09-01

## Contexte
`delivery_address` (ADR-033) est une entite a part, liee au partenaire par
son champ `uid`, geree exclusivement depuis l'espace partenaire
(`/configurer/livraison`). Question posee par l'utilisatrice : pourquoi ne
pas avoir stocke les adresses directement comme champs sur le compte
`user`, pour que son client (back-office) retrouve toutes les infos d'un
partenaire au meme endroit ?

Le choix d'une entite separee (ADR-033) reste justifie techniquement :
identite stable par adresse (routes de modale par ID, pas par delta de champ
multivalue), et evite de reexposer un champ editable sur `user_form`, deja a
l'origine d'un bug reel (voir CLAUDE.md, `user_form` partage entre
auto-edition et edition admin). Mais la preoccupation de fond — consolider
la vue back-office — etait legitime et non couverte : aucune UI admin
n'existait pour consulter les adresses de livraison d'un partenaire.

## Options considerees

### Option A : migrer les adresses vers des champs sur `user`
- Avantages : un seul formulaire, cote stockage.
- Inconvenients : perte de l'identite stable par adresse (CRUD par
  modale), reouvre le risque de fuite d'editabilite deja rencontre sur
  `user_form` (CLAUDE.md), refonte du modele de donnees deja livre et valide
  (ADR-033) pour un besoin qui est en realite un besoin d'AFFICHAGE, pas de
  stockage.

### Option B (retenue) : garder l'entite separee, ajouter un recapitulatif en lecture seule sur `/user/{uid}/edit`
- Avantages : repond au besoin reel (consolidation de la vue admin) sans
  toucher au modele de donnees ni aux formulaires partenaire existants ;
  chirurgical (un hook, une regle d'acces).
- Inconvenients : deux emplacements distincts continuent d'exister
  (compte + entite), mais desormais consultables au meme endroit.

## Decision
Option B. `DeliveryAddressAccessControlHandler::checkAccess()` ouvre
l'operation `view` a tout compte ayant la permission `administer users`
(`update`/`delete` restent strictement proprietaire — aucun besoin metier
de les ouvrir a l'admin). `drivematic_partner_form_user_form_alter()`
ajoute, uniquement quand un admin edite le compte d'un AUTRE utilisateur
(`!$editing_own_account && $is_admin`), un bloc `details` "Adresses de
livraison" liste les adresses de ce partenaire (raison sociale, adresse,
CP/ville, mention "adresse par defaut" le cas echeant), ou un etat vide.
Necessite une dependance `drivematic_configurator` ajoutee a
`drivematic_partner.info.yml` (chargement de l'entite `delivery_address`).

## Consequences
- Un admin retrouve desormais les adresses de livraison d'un partenaire
  directement sur `/admin/people/{uid}/edit`, sans avoir a se connecter a sa
  place.
- Bloc strictement lecture seule : la gestion (ajout/modification/
  suppression) reste exclusivement du ressort du partenaire, depuis
  `/configurer/livraison`.
- `drivematic_partner` depend desormais de `drivematic_configurator`
  (nouvelle dependance de module).
- Fichiers impactes : `DeliveryAddressAccessControlHandler.php`,
  `drivematic_partner.module`, `drivematic_partner.info.yml`.
