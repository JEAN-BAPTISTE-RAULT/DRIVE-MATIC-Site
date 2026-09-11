# ADR-056 : Vérification du catalogue à la reprise et à la commande d'un devis

## Statut
Accepte

## Date
2026-09-11

## Contexte

Le catalogue de tarifs peut évoluer au fil des imports DM (ADR-055), non maîtrisés par ce
projet (fréquence, ampleur des changements inconnues à l'avance). Un devis « à finaliser »
n'est pas figé (ADR-052) : reprendre un vieux devis (Modifier/Dupliquer) ou finaliser une
commande peut donc se faire à des tarifs/disponibilités différents de ceux vus au moment de
la configuration initiale, sans qu'aucun mécanisme n'avertisse le partenaire.

Une première tentative (session du 2026-09-10) avait ajouté un message par configuration
directement dans les écrans « Devis »/« Configuration » (« cette configuration n'est plus
proposée au catalogue »). Remplacée ici par une confirmation explicite à deux moments précis
du parcours, décidée avec l'utilisatrice le 2026-09-11 :

1. Au clic sur « Modifier » un devis « à finaliser » (menu 3 points, « Mes devis ») —
   avertissement systématique, pas conditionnel.
2. Au clic sur « Commander » (étape 3) — vérification et avertissement UNIQUEMENT si un
   changement est réellement détecté.

## Decision

### 1. Vehicule depublie = equipement indisponible

`QuoteCalculator::loadPrice()` traite désormais un `vehicle_model` dépublié (Statut
« Ne pas publier », ADR-055) exactement comme un tarif absent du catalogue : aucun prix
résolu, ligne `unavailable`. Un seul mécanisme d'indisponibilité pour « produit/prix retiré
du catalogue » ET « véhicule retiré de la vente » — décision explicite (l'alternative aurait
laissé un devis existant sur un véhicule dépublié continuer de se vendre normalement).

### 2. « Modifier » : modale systematique, puis retour a l'etape 1

Nouveau `QuoteModifyConfirmForm` (`ConfirmFormBase`, même mécanisme de modale que
`QuoteConfigurationDeleteForm`/`DeliveryAddressDeleteForm`, ADR-034) remplace l'ancien
`QuoteModifyController` (supprimé). Le lien « Modifier » de `quote-row-actions.twig` devient
`use-ajax`/`data-dialog-type: modal`. Confirmer (« Poursuivre ») reprend l'ancienne logique
(reconstruction du brouillon via `QuoteDraftBuilder`) mais redirige désormais vers **l'étape 1**
(Configuration), pas l'étape 3 (Livraison) comme avant — le partenaire doit pouvoir revoir sa
configuration avant de commander. « Annuler » ferme la modale sans rien faire (lien natif,
aucune soumission).

Corollaire dans `ConfigurationForm::buildConfigurationElement()` (conservé de la tentative
précédente, sans le message qui l'accompagnait) : un `<select>` ne peut pas afficher une
valeur absente de ses propres `#options` sans que le navigateur ne bascule silencieusement
sur une autre — le modèle/la motorisation d'un véhicule désormais dépublié sont donc vidés
explicitement (la marque, elle, reste valide).

### 3. « Commander » : verification finale, modale seulement si changement reel

- `QuoteForm::deliverySubmit()` (seul point de passage vers Livraison, y compris depuis
  Modifier qui redirige maintenant vers l'étape 1) stocke un instantané comparable
  (`QuoteCalculator::buildComparableSnapshot()` — prix/référence/disponibilité par ligne,
  jamais la remise partenaire) dans la `PrivateTempStore`.
- `DeliveryForm::orderSubmit()` recalcule via `QuoteCalculator` au moment du clic et compare
  à cet instantané. Identique : la commande se finalise normalement (comportement inchangé).
  Différent : **rien n'est persisté** ; le bouton « Commander » passe en `#ajax`
  (`orderAjaxCallback()`) qui ouvre une modale construite depuis un nouveau
  `OrderCatalogChangeConfirmForm` (`ConfirmFormBase`, routé sur
  `/configurer/livraison/confirmer-catalogue` — nécessaire même si jamais navigué
  directement : un formulaire construit via `form_builder` sans route dédiée hérite de
  l'URL de la requête courante comme `#action`, ce qui l'aurait fait soumettre à
  `DeliveryForm` par erreur — piège rencontré et corrigé en verifiant). Ce formulaire relit
  tout depuis la `PrivateTempStore` (brouillon, adresse par ID, devis en cours d'édition —
  aucun accès au `$form_state` de `DeliveryForm`, formulaire distinct) : « Poursuivre la
  commande » finalise avec le tarif recalculé à l'instant présent (jamais l'instantané
  périmé) ; « Annuler » ferme la modale et retourne à l'étape 1.
- Logique de finalisation (persistance + PDF + e-mails) extraite dans un nouveau service
  `QuoteFinalizer::finalize()`, appelé identiquement par `DeliveryForm::persistQuote()` (cas
  sans changement) et `OrderCatalogChangeConfirmForm::submitForm()` (cas confirmé) — évite de
  dupliquer la génération PDF/l'envoi des 2 e-mails entre les deux points d'entrée.
- Adresse de livraison également dupliquée en `PrivateTempStore` par ID
  (`DeliveryForm::validateForm()`) : `$form_state->get('selected_delivery_address')` ne
  survit pas d'un formulaire à l'autre, contrairement à la tempstore.

## Consequences

- Fichiers modifiés/créés : `QuoteCalculator.php`, `QuoteForm.php`, `DeliveryForm.php`,
  `QuoteModifyConfirmForm.php` (nouveau), `OrderCatalogChangeConfirmForm.php` (nouveau),
  `QuoteFinalizer.php` (nouveau service), `drivematic_configurator.routing.yml`,
  `drivematic_configurator.services.yml`, `quote-row-actions.twig`. `QuoteModifyController`
  supprimé (remplacé par `QuoteModifyConfirmForm`).
- Le message par configuration de la tentative précédente est retiré (`QuoteForm`/
  `ConfigurationForm`, SCSS `&__catalog-warning`/`&__vehicle-notice`) : plus jamais de
  détection de changement affichée par configuration, uniquement aux deux points de
  confirmation ci-dessus.
- Vérifié de bout en bout (curl authentifié, cf. mémoire browser-mcp-form-submit-curl-fallback
  — le login Browser MCP échouait silencieusement sur cette session) : reprise d'un devis
  avec véhicule dépublié (modèle/motorisation vidés, marque conservée), commande sans
  changement (chemin direct inchangé), commande avec changement détecté (modale, prix
  recalculé au moment de « Poursuivre la commande », pas l'instantané périmé).

## Addendum (2026-09-11) : `$form_state->getRedirect()` est désactivé sous AJAX réel

Bug rapporté (« Reprendre » puis « Poursuivre » ne faisait rien) : `QuoteModifyConfirmForm::
ajaxSubmit()` lisait `$form_state->getRedirect()` pour construire son `RedirectCommand` — cet
appel renvoie **toujours `FALSE`** sous une vraie soumission AJAX (`FormBuilder::buildForm()`
appelle `$form_state->disableRedirect()` dès qu'il détecte `?ajax_form=1`, **avant** que les
handlers de soumission ne s'exécutent), quel que soit ce que `submitForm()` a posé via
`setRedirect()`. Le code retombait donc systématiquement sur son fallback (`getCancelUrl()`,
« Mes devis ») au lieu de l'étape 1.

Resté invisible sur `QuoteConfigurationDeleteForm`/`DeliveryAddressDeleteForm` (même
mécanisme de modale, ADR-034) uniquement parce que leur cible de succès **coïncide** avec
`getCancelUrl()` — jamais testé en conditions réelles (AJAX vrai, pas une soumission POST
classique qui contourne entièrement le problème). Voir mémoire
`form-state-get-redirect-disabled-under-ajax` pour le détail complet.

**Corrigé** dans les 3 endroits concernés (`QuoteModifyConfirmForm`,
`OrderCatalogChangeConfirmForm`, `DeliveryForm::orderAjaxCallback()`) : plus aucun callback
`#ajax` ne lit `$form_state->getRedirect()` — la destination est soit posée explicitement dans
une clé `$form_state` dédiée (`ajax_redirect_url`) pendant `submitForm()`, soit codée en dur
quand une seule destination est possible dans la branche concernée.
