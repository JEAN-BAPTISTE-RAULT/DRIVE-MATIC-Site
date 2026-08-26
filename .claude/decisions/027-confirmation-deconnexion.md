# ADR-027 : Confirmation de deconnexion (`/user/logout/confirm`)

## Statut

Accepte

## Date

2026-08-26

## Contexte

Le lien « Me deconnecter » du dropdown « Espace partenaire » pointait vers
`/user/logout` : deconnexion immediate, sans etape de confirmation. Demande :
passer par la page de confirmation core (`UserLogoutConfirm`,
`/user/logout/confirm`), stylisee a minima comme les autres pages du parcours
compte (`/user/password`, `/user/{uid}/edit`).

En verifiant le rendu, la page ne montrait **aucun texte** : ni `<h1>`, ni
`<title>` de navigateur. Cause a deux etages :

1. Le bloc `drive_matic_page_title` a une condition de visibilite
   `entity_bundle:node` niee qui exige un contexte `node` — absent sur toute
   route non-node. Deja le cas sur `/user/login` et `/user/password` :
   comportement existant, pas un bug introduit.
2. `BlockPageVariant` construit le `<title>` HTML a partir du bloc titre de
   page **rendu**, pas directement de `$form['#title']`. Quand ce bloc ne
   rend rien, Drupal retombe sur `_title`/`_title_callback` **statique de la
   route**. `user.pass` (`/user/password`) en a un
   (`_title: 'Reset your password'`) — d'ou un `<title>` correct malgre
   l'absence de `<h1>`. `user.logout.confirm` n'en a **aucun** dans
   `user.routing.yml` — d'ou un titre entierement vide, pas seulement le
   `<h1>` manquant.

## Options considerees

### Rendre la question visible : re-activer le bloc titre vs l'injecter dans le formulaire

- **Re-activer/adapter la condition du bloc titre pour cette route** :
  coherent avec le mecanisme standard, mais touche une condition de
  visibilite partagee par **toutes** les routes non-node du site
  (`/user/login`, `/user/password`, `/user/{uid}/edit`) — risque de faire
  apparaitre un `<h1>` sur des pages ou ce n'est ni demande ni verifie
  visuellement, pour un gain limite a une seule route.
- **Injecter la question dans le formulaire via `hook_form_FORM_ID_alter()`**
  (retenue) : `ConfirmFormBase::buildForm()` peuple deja `$form['#title']`
  avec `getQuestion()` avant que les hooks d'alter ne s'executent — reutiliser
  cette valeur (`html_tag` `<p>`) evite de dupliquer la chaine traduite et
  ne touche qu'a la route concernee.

### Alignement horizontal du texte/boutons

Meme demande et meme mecanisme que le fil d'Ariane ([ADR-023](023-fil-ariane-style.md)) :
`.user-logout-confirm` reprend a l'identique la boite du bandeau du header
(plafond 1440px centre, gouttiere 24px / 40px a 992px), dupliquee a la main
faute d'heritage possible de `--site-header-gutter` (scopee a `.site-header`).

## Decision

- Le lien de menu « Me deconnecter » pointe vers `/user/logout/confirm`
  (contenu, pas config — mis a jour directement en base, `menu_link_content`
  id 48).
- `drivematic_partner_form_user_logout_confirm_alter()` (module
  `drivematic_partner`) injecte la question du core en `<p>` visible, sans
  reactiver le bloc titre de page ni dupliquer la chaine traduite.
- Habillage minimal dans `src/scss/_user-logout-confirm-form.scss`, sur le
  modele de `_user-pass-form.scss` (pas de carte, pas de SDC) : pas de
  `padding-block-start` (le fil d'Ariane porte deja cet ecart, deux paddings
  ne s'additionnent pas), alignement horizontal sur la boite du header.
- Le `<title>` du navigateur reste vide sur cette route : juge hors
  perimetre (n'affecte que l'onglet du navigateur, pas le contenu visible
  demande).

## Consequences

- Toute future route core sans `_title` statique **et** sans contexte de
  node montrera le meme symptome (titre de navigateur vide) — verifier
  `*.routing.yml` de la route avant de chercher ailleurs si le cas se
  represente.
- Le mecanisme « bloc titre absent sur route non-node » reste inchange et
  continue de s'appliquer identiquement a `/user/login`, `/user/password`,
  `/user/{uid}/edit` et desormais `/user/logout/confirm`.
- Si une future page core sans maquette a besoin d'un texte visible en
  l'absence de bloc titre, le pattern `hook_form_FORM_ID_alter()` +
  reutilisation de `$form['#title']` est reproductible tel quel.

## Alternatives rejetees

Voir les options ci-dessus.
