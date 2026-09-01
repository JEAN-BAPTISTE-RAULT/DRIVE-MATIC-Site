# ADR-034 : Modales d'adresse de livraison — dialogue Drupal core, pas `help-modal`

## Statut
Accepte

## Date
2026-09-01

## Contexte
F14 etape 3/3 (Livraison) demande 2 modales avec formulaire valide serveur :
ajout/edition d'une adresse de livraison (`DeliveryAddressForm`) et
confirmation de suppression (`DeliveryAddressDeleteForm`, `ConfirmFormBase`).
Le seul pattern de modale existant dans ce projet est le SDC `help-modal`
(ADR-015) : `<dialog>` HTML natif + JS vanilla (`Drupal.behaviors`), pensé
pour du contenu **statique** (une image d'aide) — le contenu vit dans un
`<template>` clone vers `document.body`, sans validation ni soumission.

## Options considerees

### Option A ecartee : etendre `help-modal` a un contenu de formulaire
- Avantages : reutilise un pattern deja en place, coherent visuellement.
- Inconvenients : `help-modal` n'a jamais ete concu pour un `<form>` valide
  serveur — il faudrait re-implementer a la main la soumission AJAX, la
  gestion des erreurs de validation, le focus trap, et la fermeture
  post-soumission. Duplique une bonne partie du systeme de dialogue de
  Drupal core.

### Option B (retenue) : systeme de modale Drupal core
`use-ajax` + `data-dialog-type="modal"` sur les liens Modifier/Supprimer/
Ajouter une adresse, routes `_form` standard (aucun controleur
`OpenModalDialogCommand` custom necessaire : le `MainContentRenderer` de
Drupal core convertit automatiquement toute route en modale des que la
requete AJAX porte `_wrapper_format=drupal_modal`). Boutons de soumission
avec `#ajax['callback']` retournant soit le formulaire (erreurs — Drupal
re-affiche dans la modale, mecanisme standard) soit un `AjaxResponse`
(`CloseModalDialogCommand` + `RedirectCommand` vers `/configurer/livraison`,
succes).
- Avantages : **zero JS custom** ecrit pour ce projet (`core/drupal.dialog.
  ajax`, requis en `#attached` par chaque formulaire ouvrant une modale —
  `drive_matic/dialog` n'est que le CSS brut de jQuery UI, pas ce mecanisme
  JS, voir addendum du 01/09 (2e partie)) ; validation serveur,
  focus trap et fermeture Echap/clic-fond gratuits (jQuery UI dialog,
  eprouve) ; degradation sans JS native (le lien `use-ajax` redevient un
  `<a href>` normal, chaque route fonctionne aussi en page complete — verifie
  a l'implementation, cf. `docs/plans/configurateur-etape-3-livraison.md`).
- Inconvenients : premiere utilisation de ce pattern dans le projet (aucun
  precedent a suivre) ; le degrade sans JS (page complete) necessite son
  propre `<h1>` — le bloc titre de page du coeur ne s'affiche que sur les
  routes de node (meme piege que ConfigurationForm/QuoteForm/DeliveryForm),
  omis quand la reponse est destinee a la modale pour ne pas dupliquer le
  titre deja porte par le dialogue (`ModalRequestTrait::isModalRequest()`,
  verifie via `\Drupal::request()->get('_wrapper_format') === 'drupal_modal'`
  — accessible en GET **et** en POST, le cycle de soumission AJAX passant
  par une sous-requete POST qui porte toujours ce parametre).

## Decision
Option B. `help-modal` reste inchange, reserve a son cas d'usage (contenu
statique). Les 2 nouveaux formulaires de ce chantier sont les premiers du
projet a utiliser le systeme de modale Drupal core.

## Consequences
- `web/modules/custom/drivematic_configurator/src/Form/ModalRequestTrait.php`
  (nouveau) : partage `isModalRequest()` entre `DeliveryAddressForm` et
  `DeliveryAddressDeleteForm` — reutilisable pour toute future modale
  Drupal core du projet.
- Prochaine modale de formulaire (hors `help-modal`) : suivre ce meme
  pattern plutot que d'introduire un 3e mecanisme.
- Verifie en conditions reelles (curl, requete AJAX authentifiee) : ouverture
  de la modale (`openDialog`), re-affichage avec erreurs
  (`insert` + message), fermeture+redirection au succes (`closeDialog` +
  `redirect`) — voir §9 du plan pour le detail de la boucle de verification.

## Addendum du 01/09 : fidelite pixel a la maquette 521:17375

Premier passage de stylisation (`_dialog.scss`) approximatif — verifie
seulement a l'oeil, pas mesure. `getBoundingClientRect()` face aux
coordonnees exactes de `get_metadata`/`get_design_context` a revele 5 bugs,
tous causes par du CSS brut jQuery UI (`ui-dialog.css`, non tokenise) plus
specifique que nos selecteurs (voir CLAUDE.md, section SCSS/SDC) :

1. `.ui-widget.ui-widget-content { border: 1px solid #c5c5c5 }` (2 classes)
   battait `.ui-dialog { border: 0 }` (1 classe) — bordure fantome.
2. `.ui-dialog .ui-dialog-title { width: 90% }` (meme specificite que notre
   regle, mais propriete jamais redeclaree par nous) — largeur figee.
3. Le texte brut "Close" du coeur (masque a l'ecran, jamais retire du DOM)
   faussait le calcul d'espace du `flex-grow` du titre — la croix "volait"
   ~48px de trop des deux cotes. Corrige en abandonnant le flex pour la
   croix : `position: absolute`, ancree sur les memes valeurs `top`/`right`
   que le padding du conteneur (mecanisme d'origine de jQuery UI, simplement
   re-theme).
4. Le titre et le contenu n'avaient aucun ecart vertical entre eux (padding
   top absent sur `.ui-dialog-content`) — 41px manquants mesures sur la
   maquette.
5. `.ui-dialog .ui-dialog-buttonpane .ui-dialog-buttonset { float: right }`
   (3 classes) battait `.ui-dialog .form-actions { justify-content:
   flex-start }` (2 classes) — le bouton d'un `FormBase` simple (pas
   seulement `ConfirmFormBase`) est deplace par jQuery UI hors de la grille
   du formulaire des que son wrapper porte la classe `form-actions` :
   `grid-column`/`grid-row` pose sur `.form-actions` ne visait qu'un
   doublon masque (`display: none`), jamais le bouton reellement affiche.

Grille de champs (`_delivery-address-form.scss`) corrigee de la meme
maniere : « Raison sociale » etait etiree en pleine largeur
(`grid-column: 1 / -1`, hypothese jamais mesuree) au lieu de rester en
colonne 1 seule (315px, colonne 2 vide sur cette ligne, verifie
521:17375) ; placement de chaque champ desormais explicite
(`grid-column`/`grid-row`) plutot que delegue a l'auto-placement, dont le
resultat n'etait pas celui attendu des que 6 elements (5 champs + actions)
se disputent une grille a 2 colonnes avec une case vide.

Resultat final mesure (`getBoundingClientRect`, coordonnees relatives au
coin superieur gauche de `.ui-dialog`) : modale 760×530 (identique au
frame), titre (50,30), croix (686,30,24×24), 3 lignes de champs a
(50/395,103) (50/395,204) (50/395,315) chacune 315×81, bouton (50,436,
185×46) — correspond exactement aux coordonnees de `get_metadata` sur
521:17375 (ecarts de 2-3px negligeables, imputables au rendu de police).

## Addendum du 01/09 (2e partie) : croix surdimensionnee, bouton « Oui »
difforme, `Drupal.ajax` absent sur `QuoteForm`

Retour utilisatrice apres le premier addendum : 3 problemes supplementaires,
tous du meme registre (CSS brut du coeur plus specifique, ou mecanisme non
charge) — jamais visibles sur une simple capture, seulement a la mesure ou
au `getComputedStyle`.

1. **Croix surdimensionnee** : `mask-size: contain` etirait le SVG
   `close.svg` (deja recadre au plus pres du trait, 14×14, sans marge
   interne) jusqu'a remplir tout le cadre (24×24 desktop/20×20 mobile) — la
   maquette (521:17375 ET 671:22383, verifie aux deux tailles) cadre en
   realite le glyphe a **50% seulement** de son cadre (12px dans 24px,
   10px dans 20px, meme ratio). Corrige : `mask-size: 50%` au lieu de
   `contain`.
2. **Bouton « Oui » etire a une hauteur ~2x trop grande** (62px au lieu de
   46px) : `align-items` par defaut (`normal` → `stretch`) sur
   `.ui-dialog-buttonset`. Cause exacte non isolee malgre forcage en
   `!important` de `flex-shrink`, `white-space`, `line-height` (aucun effet
   — seul un `height` explicite en dur repondait, signe d'une resolution
   flex interne plutot que d'une propriete CSS classique) : `getBoundingClient
   Rect()` sur le texte lui-meme (`Range`) confirmait pourtant une seule
   ligne, 18px de haut, correctement centree. Fix qui MARCHE sans
   comprendre la cause exacte : `align-items: flex-start` sur `.ui-dialog
   .form-actions` (n'affecte pas « Non », qui n'etait pas etire).
3. **Bouton « Non » decale de 8px vers le bas par rapport a « Oui »** : le
   coeur pose `margin: 0.5em 0.4em 0.5em 0` via
   `.ui-dialog .ui-dialog-buttonpane button` (2 classes + 1 element =
   plus specifique que notre `.ui-dialog .dialog-cancel`, 2 classes
   seules) — `.form-submit` (« Oui ») en etait deja protege par sa propre
   regle `margin: 0`, mais `.dialog-cancel` n'en avait pas. Corrige avec
   la meme structure de selecteur (`.ui-dialog .ui-dialog-buttonpane
   .dialog-cancel { margin: 0 }`) pour l'emporter a coup sur.
4. **`QuoteConfigurationDeleteForm` (nouvelle modale, suppression d'une
   configuration a l'etape 2/3 « Devis », remplace l'ancien
   `QuoteForm::removeConfigurationSubmit()` sans confirmation) n'ouvrait
   pas la modale du tout : `Drupal.ajax` etait absent de la page.**
   `core/drupal.dialog.ajax` n'est **pas** charge sitewide (contrairement a
   ce que disait ce document jusqu'ici) — chaque formulaire qui ouvre une
   modale Drupal core doit l'attacher explicitement
   (`DeliveryForm::buildForm()` le fait deja depuis le debut ;
   `QuoteForm::buildForm()` ne le faisait pas, n'ayant jamais eu besoin de
   modale avant ce chantier). Symptome : le lien `use-ajax` degradait
   silencieusement en navigation de page complete (fonctionnellement
   correct, juste pas la modale attendue) — verifie via
   `!!window.Drupal?.ajax` dans la console. Reflexe pour toute future
   modale : verifier que le formulaire qui la declenche attache bien
   `core/drupal.dialog.ajax`, pas seulement que le lien porte `use-ajax`.
