# ADR-028 : Configurateur de devis — FormBase custom plutot que Webform

## Statut
Accepte

## Date
2026-08-26

## Contexte
F14 (configurateur de devis) demande un ecran « Configuration » avec un bloc
repetable (vehicule + equipements + quantites), dupliable via « Ajouter une
configuration » jusqu'a 10 fois, suppression possible a partir du 2e bloc.
Tous les formulaires existants du site (contact, devenir partenaire, SAV,
demande de compte) sont des **Webforms** — ADR-015 (`_forms.scss`)
anticipait meme explicitement le configurateur comme futur consommateur de la
fondation `.webform-submission-form`. Le bloc repetable a un equivalent
natif cote Webform : un element composite personnalise marque `#multiple`
(min 1, max 10), avec bouton "Ajouter" fourni par le module.

## Options considerees

### Option A : Webform (composite personnalise, `#multiple`)
- Avantages : reutilise directement la convention deja en place pour tous
  les formulaires du site et la fondation `.webform-submission-form` telle
  quelle ; bouton "Ajouter/Supprimer" natif, pas de pattern AJAX a ecrire ;
  moins de code custom.
- Inconvenients : Webform est concu pour des soumissions (email + stockage
  de données de contact), pas pour un objet metier avec cycle de vie (F15 :
  onglets a finaliser / en cours / archives, dupliquer / commander /
  archiver, numerotation `WAAAAMMJJ-001`). Detourner ce mecanisme pour ce
  qui deviendra des entites Devis/Configuration/Equipement (F14-F17)
  aurait impose, tot ou tard, une migration hors Webform.

### Option B : FormBase custom (pattern Drupal « Add more »)
- Avantages : aligne directement sur le modele de donnees cible (entites
  metier a venir), controle complet sur le comportement (cascade JS,
  validation croisee marque/modele/motorisation, cle stable par bloc pour
  ne pas perdre la saisie a la suppression d'un bloc du milieu).
- Inconvenients : plus de code a ecrire (callbacks AJAX add/remove, gestion
  manuelle des cles de configuration dans `$form_state`).

## Decision
Option B, FormBase custom (`Drupal\drivematic_configurator\Form\
ConfigurationForm`), decidee avec l'utilisatrice avant implementation
(question posee explicitement, cf. `docs/plans/configurateur-etape-1.md`).
Le pattern « Add more » stocke une liste de **cles stables** (jamais
reattribuees) dans `$form_state`, distinctes de la **position affichee**
(recalculee a chaque rendu pour la numerotation « Configuration N ») — une
suppression au milieu ne fait donc pas perdre la saisie des blocs suivants.

La fondation `.webform-submission-form` (ADR-015) reste reutilisee, mais
seulement pour ses regles **descendantes** (champ, checkbox, select,
focus...) : ce formulaire porte plusieurs cartes (une par configuration), pas
une seule enveloppant tout le formulaire comme les Webforms existants — les
proprietes de mise en page du bloc racine (`display`, `padding`, fond,
`border-radius`) sont donc neutralisees sur `.configurator-form` et
redeclarees sur `.configurator-form__card` par bloc (`_configurator-form.scss`).

`/configurer` (alias en dur, node `configurator` a exemplaire unique,
CLAUDE.md) est repris par ce formulaire : le node placeholder existant
(body + paragraphes generiques, jamais fonctionnel) et son alias sont
supprimes, une route statique du nouveau module (`drivematic_configurator.
configuration`, `_role: 'partenaire'`) prend le relais sur la meme URL. Ceci
corrige au passage un gap deja note dans le PRD (absence de `_custom_access`
sur cette page).

## Consequences
- Nouveau module `drivematic_configurator` (route, FormBase, JS du stepper
  de quantite).
- `drivematic_forms/js/vehicle-select.js` generalise : ciblage par attribut
  (`data-vehicle-cascade`/`data-vehicle-role`) plutot que par `name` exact +
  `closest('form')`, pour supporter plusieurs cascades marque->modele->
  motorisation independantes sur une meme page (une par bloc de
  configuration). Seul autre consommateur, le webform contact
  (`config/sync/webform.webform.contact.yml`), adapte en consequence
  (attributs ajoutes sur le fieldset `demande` + les 3 selects) et
  re-teste manuellement.
- `_drivematic_forms_vehicle_map()` renommee `drivematic_forms_vehicle_map()`
  (retrait de l'underscore) : devient une API publique du module
  `drivematic_forms`, consommee par `drivematic_configurator`.
- Les 4 equipements (rétrovision ext./int., télécommande VOR, double
  pédalier) sont codes en dur dans le formulaire : aucun catalogue produit
  (F17) n'existe encore. A revoir quand F17 sera implemente.
- Pas encore d'entite Devis/Configuration : `submitForm()` affiche un
  message de confirmation temporaire (l'etape « Devis », F14 2/3, n'existe
  pas encore) — a remplacer quand ce chantier sera pris.

## Addendum (26/08/2026) — liens casses par la suppression du node placeholder

La suppression du node 69 (§ Decision) a casse **13 liens** en dur
(`entity:node/69`) dans des paragraphes `field_link` repartis sur 6 pages
publiques (home + 5 pages produit) — un bloc CTA « Configurez votre véhicule »
recurrent, place en placeholder avant l'existence de F14 (cf. PRD, note sur
le « Bloc configurateur »). Corrige en repointant les 13 occurrences vers
`internal:/configurer` (recherche exhaustive faite sur tous les champs
`link` de toutes les entites fieldable + les champs texte riche, pour
s'assurer qu'aucune autre occurrence ne subsiste).

**Lecon pour la prochaine suppression de contenu** : verifier les
references ENTRANTES (recherche par uid/nid dans les champs `link` et
texte riche de toutes les entites) avant de supprimer un node, pas
seulement son alias et ses liens de menu — un lien `field_link` en dur
(`entity:node/N`) ne remonte dans aucun rapport de menu ni de
`path_alias`, contrairement a un lien de menu.

Consequence supplementaire : ce CTA est visible des anonymes, mais
`/configurer` est reserve aux partenaires (`_role: 'partenaire'`) — un clic
anonyme tombait sur un 403 brut. Decision (validee avec l'utilisatrice) :
generaliser la redirection vers `/user/login?destination=...` a **toutes**
les routes `_role: 'partenaire'`, pas seulement `/configurer`, pour rester
coherent avec `/user/mes-informations-personnelles` (qui rendait le meme
403 brut jusque-la). Implemente en `EventSubscriber` sur
`KernelEvents::EXCEPTION` (`drivematic_partner.access_redirect_subscriber`,
`PartnerAccessRedirectSubscriber`) : intercepte une `AccessDeniedHttpException`
pour un anonyme, verifie que la route en cause porte l'exigence de routing
`_role: partenaire` (pas un `_custom_access`/`_permission` generique — scope
volontairement etroit), redirige vers la connexion avec `destination`.
