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
  ajax`, deja charge sitewide via `drive_matic/dialog`) ; validation serveur,
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
