# ADR-020 : Footer riche — menus Drupal plutot que liens en dur

## Statut
Accepte

## Date
2026-08-20

## Contexte

Le footer riche (F2) doit exposer 14 liens vers du contenu existant : 8 liens
produit/configurateur repartis en 2 colonnes (« Nos solutions pour
auto-ecole » / « Nos solutions pour PMR »), 2 liens d'assistance (contact,
FAQ), et 4 liens legaux (CGV, CGU, mentions legales, donnees personnelles).
Tous ciblent des nodes deja publies, avec des alias qui peuvent changer
(regle deja actee au projet : renommer un contenu publie change son alias).

Le SDC `site-footer` existait deja en coquille minimale (shell « F2 a venir »)
avec un seul slot `menu` alimente par la region core `footer`, sur le meme
modele que `site-header` (`page.primary_menu` + `page.secondary_menu` dans un
seul slot `menu`).

## Options considerees

### Option A : hrefs en dur dans le Twig du SDC

- Avantages : le plus simple a ecrire, aucune config/contenu supplementaire.
- Inconvenients : un `href="/telecommande-vor"` casse silencieusement (404)
  si la page est renommee — piege deja identifie au projet pour les alias.
  Aucune trace editoriale du lien (pas visible dans `/admin/structure/menu`).

### Option B : menus Drupal (menu_link_content → entite node)

- Avantages : un `menu_link_content` reference l'entite, pas le chemin — il
  reste correct apres un renommage d'alias. Gerable en back-office. Reutilise
  le menu core `footer` (deja present, vide, destine a exactement cet usage
  — description core : « Liens d'informations sur le site »).
- Inconvenients : demande un nouveau menu custom (`footer-solutions`) pour
  les 3 colonnes de gauche, et un script ponctuel pour creer les
  `menu_link_content` (contenu, pas config — non committe, coherent avec la
  decision projet « le contenu ne se versionne pas »).

## Decision

**Option B.** Un menu custom `footer-solutions` (2 niveaux : 3 items parents
en route `<nolink>` comme titres de colonne non cliquables, portant chacun
leurs liens enfants) + le menu core `footer` peuple pour les 4 liens legaux.
Les deux sont rendus par des `system_menu_block` places dans la region
`footer` existante (pas de nouvelle region declaree dans
`drive_matic.info.yml` — le theme n'a aucune cle `regions:` et en ajouter une
remplacerait le jeu de regions par defaut, risque disproportionne pour cette
tache). `page.html.twig` indexe `page.footer` par ID de bloc
(`page.footer.drive_matic_footer_solutions_menu` /
`...footer_legal_menu`) pour alimenter 2 slots distincts du SDC plutot que le
slot `menu` unique.

Logo, adresse/telephone et icones reseaux sociaux restent codes en dur dans
le twig du SDC (comme le copyright deja present) : donnees de marque fixes,
pas du contenu editorial, pas de configurabilite demandee.

## Consequences

- Les 14 liens survivent a un renommage de page (alias change → le
  `menu_link_content` reste valide, contrairement a un `href` en dur).
- Les items de tete de colonne (« Nos solutions pour auto-ecole », etc.)
  s'appuient sur le rendu core `<nolink>` de `\Drupal\Core\Utility\LinkGenerator`
  (rendu en `<span>`, pas de lien casse) — verifie au navigateur (markup
  anonyme, `curl`), pas seulement suppose.
- Le CSS cible les classes `.menu--footer-solutions` / `.menu--footer`
  posees par `block--system-menu-block.html.twig` (derivative_plugin_id) —
  necessite 2 `stylelint-disable-next-line selector-class-pattern` cibles et
  commentes (classes du coeur, motif deja etabli dans `_forms.scss`/`_pager.scss`).
- Aucun nouveau template Twig de menu : la structure en colonnes est purement
  CSS (le `<ul>` de premier niveau du menu `footer-solutions` devient une
  rangee flex, ses `<li>` sont les colonnes).
- Le contenu des `menu_link_content` (2 menus, 17 liens au total) est cree
  par un script Drush ponctuel, non committe — a rejouer sur tout
  environnement qui ne recoit pas un dump de la base locale.
