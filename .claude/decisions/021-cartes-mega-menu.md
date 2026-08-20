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

## Addendum du 2026-08-20 — colonnes de liens à 3 niveaux

Signalé le jour même : le dropdown « Drive Matic » (node Figma 433:9459)
n'a aucune carte mais **3 colonnes de liens** séparées par des filets
(3+2+1), alors que le modèle initial ne portait qu'un seul bucket `links`
à plat par rubrique — issue directe de la portée fixée ci-dessus (Option A
ne traitait que cartes/liens, pas le regroupement en colonnes).

**Extension, pas remise en cause** : le menu passe de 2 à **3 niveaux**
pour cette seule rubrique. Un enfant de niveau 2 qui a lui-même des
enfants (`menu_link_content` supplémentaires, `<nolink>`, ex. « Colonne
1/2/3 ») n'est jamais rendu comme lien — seuls ses enfants (niveau 3)
forment une colonne dédiée. `_drive_matic_main_navigation()` charge
désormais l'arbre sur 3 niveaux (`setMaxDepth(3)`) et produit
`item.link_groups` (tableau de colonnes) au lieu d'un `item.links` plat.
Les rubriques sans regroupement (Auto-école, Véhicule PMR, Assistance)
retombent naturellement sur un unique groupe — comportement inchangé.

Alternative écartée : un champ « rupture de colonne » sur
`menu_link_content` (booléen/liste), plus proche du pattern
`field_nav_card_image` de la décision initiale. Écartée parce que la
scission mesurée sur la maquette n'est pas binaire (2 filets de largeurs
différentes, 40px puis 81px) — un champ à 2 états n'aurait couvert que la
moitié du besoin sans complexité supplémentaire, alors que la structure de
menu à 3 niveaux couvre nativement un nombre de colonnes arbitraire.
