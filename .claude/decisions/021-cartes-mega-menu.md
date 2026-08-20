# ADR-021 : Cartes du méga-menu — champ image sur le lien de menu

## Statut
Accepte

## Date
2026-08-20

## Contexte

Le header (F2) doit afficher, dans les dropdowns « Auto-école » et
« Véhicule PMR », des liens de niveau 2 sous deux formes distinctes : 7
d'entre eux (3 + 4) sont des cartes avec un visuel produit, les autres
(« Configurez votre véhicule », « Documentations »…) sont de simples liens
texte. Les dropdowns « Drive Matic » et « Assistance » n'ont aucune carte.

Le menu Drupal `main` porte déjà l'arbre à 2 niveaux (titres, ordre,
destinations) via `menu_link_content`. Rien ne permet aujourd'hui d'attacher
une image à un lien de menu : ni le core, ni un champ existant.

Le PRD (F2) demande que Drive Matic gère les rubriques de **niveau 2** en
autonomie, sans intervention de Passerelle — un critère qui doit couvrir les
7 liens à carte autant que les liens texte.

## Options considerees

### Option A : champ image local sur `menu_link_content`

- Avantages : un seul menu à gérer (arbre + visuel), autonomie totale pour
  Drive Matic sur les 2 niveaux, y compris l'image. Présence du champ = rendu
  en carte, absence = rendu en lien texte (pas de champ booléen séparé à
  garder cohérent). Suit le pattern « champ image local, recadrage manuel »
  déjà établi (ADR-018) : widget `image_widget_crop`, `crop_types_required:
  [crop_16_9]`, réutilise le crop type et le style responsive `dm_16_9`
  existants — aucune nouvelle config image.
- Inconvenients : `menu_link_content` n'a qu'un seul bundle (`menu_link_content`)
  partagé par **tous** les menus du site. Le champ apparaît donc, optionnel,
  sur le formulaire de **tout** lien de menu (footer, compte…), pas
  seulement ceux du header — limitation du cœur, pas contournable sans un
  module contrib dédié.

### Option B : cartes codées en dur dans le thème

- Avantages : le plus simple, aucun nouveau champ.
- Inconvenients : Drive Matic ne peut plus ajouter/retirer une carte sans
  intervention développeur — contredit le critère d'autonomie niveau 2 du
  PRD pour ces 2 rubriques précises.

## Decision

**Option A.** Nouveau champ `field_nav_card_image` (image, cardinalité 1, non
requis) sur `menu_link_content`, widget `image_widget_crop` scopé au crop
`crop_16_9` (comme les 9 champs « ratio imposé » d'ADR-018), rendu via le
formatter `responsive_image` / style `dm_16_9`. Le preprocess PHP
(`_drive_matic_main_navigation()` dans `drive_matic.theme`) charge l'arbre du
menu `main` directement (pas de `system_menu_block`, qui ne permettrait pas
la distinction carte/lien) et bascule chaque enfant de niveau 2 en carte ou
en lien selon que ce champ est rempli.

Le menu `account` (dropdown « Espace partenaire ») reste rendu par son bloc
standard (`page.secondary_menu`) : liste plate, la distinction ne s'y
applique pas.

## Consequences

- 3 nouveaux fichiers de config : `field.storage.menu_link_content.field_nav_card_image`,
  `field.field.menu_link_content.menu_link_content.field_nav_card_image`,
  `core.entity_form_display.menu_link_content.menu_link_content.default`
  (le premier form display jamais posé sur cette entité dans ce projet).
- Le bloc `drive_matic_main_menu` (region `primary_menu`, `system_menu_block:main`)
  devient redondant — désactivé (`status: false`) plutôt que supprimé, la
  nav principale étant désormais construite par le preprocess et rendue
  directement par le SDC `site-header`.
- Le champ apparaît, vide et sans effet, sur le formulaire d'édition de tout
  lien de menu hors header (compte, footer…) — limitation acceptée, cf.
  Option A ci-dessus.
- Recadrage 16:9 obligatoire et **manuel** (ADR-004) : les 7 champs sont
  laissés vides à la livraison, à compléter en back-office (upload + crop
  via `image_widget_crop`) — jamais par script, qui poserait un crop centré
  usurpant la décision éditoriale.
- Le contenu des menus `main` (24 liens) et `account` (5 liens) est créé par
  un script Drush ponctuel, non committé — même decision que le footer
  (ADR-020), à rejouer sur tout environnement sans dump de la base locale.
