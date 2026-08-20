# ADR-018 : Images locales par paragraphe pour les champs a ratio impose

## Statut

Accepte — remplace, pour les champs concernes, la partie « media reutilisable »
d'ADR-004 et rend obsolete ADR-017.

## Date

2026-08-19

## Contexte

ADR-017 (le jour meme) avait construit un mecanisme de recadrage contextuel pour
resoudre l'absence de widget de recadrage a l'import. En le testant, l'utilisatrice a
souleve un probleme plus profond, propre a l'architecture retenue depuis ADR-004 :
**le recadrage Drupal (Crop API) est rattache au couple (fichier, ratio), pas a
l'usage.** Reutiliser la meme image dans deux paragraphes `image_text_50` (tous deux
1:1) impose donc le **meme** cadrage aux deux, sans moyen de le varier par paragraphe.

Deux corrections deja tentees ce jour-la et explicitement ecartees par l'utilisatrice :

- **Dupliquer le fichier** a chaque nouveau besoin de cadrage : « ridicule de stocker
  autant d'images que de recadrages », et la grille de la mediatheque affiche tout en
  vignettes carrees — impossible de distinguer laquelle est cadree comment.
- **`media_contextual_crop`** (stockage du cadrage par reference) : necessite un
  **patch sur Drupal core** (371 lignes, `ImageStyleDownloadController`, ticket core
  non fusionne #2685905) qui touche le mecanisme servant **toutes** les images du
  site. Risque et charge de maintenance juges excessifs.

L'utilisatrice a alors formule sa propre reference : ses autres sites Passerelle
uploadent l'image **directement dans le paragraphe**, sans mediatheque, et la
recadrent a cet endroit — position deja documentee comme option A d'ADR-004,
initialement ecartee au profit de la reutilisation media.

## Options considerees

### Option A : garder la mediatheque partout, accepter la limite

- Avantages : rien a faire.
- Inconvenients : ne repond pas au besoin exprime ; l'utilisatrice a explicitement
  demande une correction.

### Option B : champ image direct sur **tous** les champs `field_image`, y compris
« sans crop » et `node.brand`/`node.contact`

- Avantages : coherence totale, un seul modele partout.
- Inconvenients : `node.brand`/`node.contact` n'ont jamais eu ce probleme (exemplaire
  unique ou aucun ratio impose) — les convertir est un risque et un travail sans
  bénéfice. Perimetre ecarte.

### Option C : champ image direct **seulement** ou le probleme existe reellement

Convertir les 9 paragraphes a ratio impose (`image_text_50`, `image_full`,
`history_element`, `grid_element`, `jumbo_home_element`, `product_cross_element`,
`product_image_element`, `product_video_element`, `video_centered`) + `node.news`
(16:9 obligatoire a l'import). Garder en mediatheque les champs « sans crop »
(`image_centered`, `image_text_100`, `product_characteristics`) et les node a
exemplaire unique/sans probleme (`node.brand`, `node.contact`).

- Avantages : resout precisement le probleme signale, sans toucher a ce qui
  fonctionnait deja. Aucun patch, aucun module supplementaire — `image_widget_crop`
  sur un champ `image` simple est son usage le plus basique et le mieux eprouve.
  Le fichier n'est jamais duplique par le mecanisme lui-meme : chaque champ porte son
  **propre** fichier (uploade separement si la meme photo doit servir ailleurs avec un
  autre cadrage), donc son propre recadrage, sans aucune notion de contexte a
  transporter.
- Inconvenients : perte de la reutilisation media pour ces champs precis (la meme
  photo utilisee deux fois = deux uploads) ; migration de donnees necessaire (schema
  de champ different).

## Decision

**Option C.**

Le recadrage etant porte par le **fichier** (`Crop::findCrop($uri, $type)`), pas par
le champ qui le referme, la conversion **ne perd aucun recadrage existant** : chaque
nouveau champ image pointe vers le meme fichier qu'avant (aucune duplication), et les
styles `dm_<ratio>_*` continuent de trouver leur entite crop normalement.

Par bundle :

| Bundle | Nouveau widget | Ratio |
|---|---|---|
| `paragraph.image_text_50` | `image_widget_crop` | 1:1 |
| `paragraph.image_full` | `image_widget_crop` | 12:5 |
| `paragraph.history_element` | `image_widget_crop` | 16:9 |
| `paragraph.grid_element` | `image_widget_crop` | 16:9 |
| `paragraph.jumbo_home_element` | `image_widget_crop` | 16:9 |
| `paragraph.product_cross_element` | `image_widget_crop` | 16:9 |
| `paragraph.product_image_element` | `image_widget_crop` | 16:9 |
| `paragraph.product_video_element` | `image_widget_crop` | 16:9 |
| `paragraph.video_centered` | `image_widget_crop` | 16:9 |
| `paragraph.image_centered` | `media_library_widget` (inchange) | sans crop |
| `paragraph.image_text_100` | `media_library_widget` (inchange) | sans crop |
| `paragraph.product_characteristics` | `media_library_widget` (inchange) | sans crop |
| `node.news.field_photo` (nouveau nom, remplace `field_image`) | `image_widget_crop` | 16:9 |

`node.news` change de nom de champ (`field_photo`) car son ancien storage
`field.storage.node.field_image` est **partage** avec `node.brand`/`node.contact`
(non convertis) — un meme storage ne peut porter deux types differents. Les 3
templates concernes (`node--news.html.twig`, `--teaser`, `--card`) sont mis a jour.

Le meme partage existe cote **paragraphe** : `field.storage.paragraph.field_image`
etait une storage unique pour les **12** bundles (les 9 a ratio + les 3 « sans
crop »). Les 9 bundles a ratio recoivent donc, comme `node.news`, un nouveau nom de
champ propre — **`field_photo`** — et non `field_image` ; cela laisse `field_image`
intact (storage entity_reference/media) pour les 3 bundles sans crop, qui ne
changent pas de widget. Voir l'addendum ci-dessous : la premiere execution avait
loupe ce point (meme storage convertie pour les 12 bundles a la fois), corrige avant
publication.

`crop_types_required` vaut desormais **exactement le ratio du bundle** (un seul
element), au lieu de la liste des 3 ratios posee par ADR-004/ADR-017 — puisque chaque
champ ne sert plus qu'un seul usage, il n'y a plus de raison d'exiger les autres.

**ADR-017 est superseded** : sa validation dediee (`drivematic_forms`) et le widget
`contextual_image_widget_crop`/`DrivematicContextualMediaLibraryWidget` n'ont plus
d'utilite (aucun champ concerne ne passe plus par la mediatheque) — code retire, module
`contextual_image_widget_crop` desinstalle. Le champ `field_media_image` du bundle
media `image` revient au widget standard `image_widget_crop` (les usages restants,
`node.brand`/`node.contact`, n'ont jamais eu besoin du recadrage contextuel).

## Migration effectuee

**Aucune perte de donnee** : 72 valeurs de paragraphe (dont 36 imbriquees dans un bloc
via `field_elements`/`field_jumbo_elements`/`field_cross_elements`/
`field_features_elements`) + 32 valeurs `node.news` capturees (fichier, texte
alternatif) avant suppression des anciens champs, restaurees a l'identique sur les
nouveaux — verifie exhaustivement apres coup (104/104, 0 manquant). Les recadrages deja
poses restent actifs (lies au fichier).

⚠️ **Ecart trouve et corrige en verifiant** : le bloc « 65 ans d'expertise » du node 54
portait par erreur le meme fichier que le bloc « Notre engagement » (glissement survenu
pendant les tests interactifs du jour) — remis sur le fichier d'origine
(`home-savoir-faire.png`) apres capture, avant que la migration ne le figrave dans le
nouveau champ.

## Consequences

**Plus facile**

- Une meme photo utilisee dans deux paragraphes differents peut desormais avoir deux
  cadrages independants — il suffit de l'importer deux fois, exactement le modele
  demande.
- Le widget de recadrage montre nativement le **seul** ratio pertinent (verifie :
  `#crop_list` / `#crop_types_required` valent `['crop_1_1']` sur `image_text_50`),
  sans aucune mecanique contextuelle a maintenir.
- Moins de code : tout le mecanisme `drivematic_forms` (validation, table de
  correspondance, widget custom) et le module `contextual_image_widget_crop`
  disparaissent.

**Plus difficile / a surveiller**

- ⚠️ **Plus de mediatheque pour ces 10 champs** : reutiliser visuellement la meme
  photo ailleurs demande un nouvel import, pas une selection. Assume, demande
  explicitement par l'utilisatrice.
- Le crop applique automatiquement lors de la restauration (quand un crop existait
  deja pour ce fichier) reste une decision **anterieure** ; tout nouvel import reste
  soumis a la regle du recadrage manuel ([[crop-obligatoire-manuel]]).
- Un futur nouveau paragraphe portant une image doit etre ajoute a la liste ci-dessus
  s'il impose un ratio — sinon il tombe silencieusement dans le modele « mediatheque »
  par defaut.

**Fichiers impactes**

- `config/sync/core.entity_form_display.{paragraph.<9 bundles>,node.news}.default.yml`,
  `core.entity_view_display.{paragraph.<12 bundles>,node.news.{default,teaser,card}}.yml`,
  `field.storage.{paragraph.field_image,paragraph.field_photo,node.field_photo}.yml` +
  `field.field.*`.
- `web/themes/custom/drive_matic/templates/content/node--news{,--teaser,--card}.html.twig`.
- `web/themes/custom/drive_matic/templates/paragraph/paragraph--{image-text-50,image-full,
  history-element,grid-element,jumbo-home-element,product-cross-element,
  product-image-element,product-video-element,video-centered}.html.twig` (`field_image` ->
  `field_photo`).
- `web/modules/custom/drivematic_forms/` (nettoye : hook, validation, table et widget
  custom retires).
- `composer.json`/`composer.lock` (retrait de `drupal/contextual_image_widget_crop`).

## Addendum du 19/08 : la premiere execution avait reconverti les 3 bundles « sans crop »

En verifiant la migration avant de la declarer terminee (mesure disque vs mesure
active, cf. [[integration-vs-contenu]]), controle de `field.storage.paragraph.field_image`
sur les 3 bundles sans ratio : ils portaient un widget `image_image` sans mediatheque,
alors que la decision ci-dessus dit explicitement de les laisser inchanges.

**Cause** : `field.storage.paragraph.field_image` est UNE storage partagee par les 12
bundles de paragraphe (comme `field.storage.node.field_image` l'est par
`news`/`brand`/`contact`). Le script de migration a supprime puis recree cette storage
en type `image` pour l'ensemble des 12 bundles a la fois — il n'existe pas de type de
champ « mixte » par bundle sur une storage partagee. Les 3 bundles sans crop ont donc
ete convertis eux aussi, silencieusement : aucune erreur, aucune perte de donnee
(le fichier et l'alt textuel restent corrects, la valeur ayant ete resolue depuis le
media vers son fichier par le script de capture/restauration, qui ciblait generiquement
les 12 bundles sans distinguer), mais plus de mediatheque pour ces 3 champs — l'inverse
de ce que dit ce document.

**Correctif** : meme pattern que `node.news` — les 9 bundles a ratio quittent
`field_image` pour une nouvelle storage `field_photo` (donnees copiees directement,
aucune re-derivation necessaire puisque le fichier ne change pas). Une fois ces 9
bundles partis, `field_image` ne porte plus que les 3 bundles sans crop : la storage
est alors supprimee puis recreee en `entity_reference`/media, et leurs valeurs
restaurees en `media` (retrouve par recherche inverse fichier -> media, verifiee
unique avant execution). Verifie de bout en bout apres coup : formulaire (`image_widget_crop`
avec `crop_list` scope sur les 9, `media_library_widget` sur les 3), rendu front (image
200, alt correct) et `drush cst` propre sur tous les objets de config concernes.

**Lecon** : sur un champ partage entre bundles, une conversion de type doit d'abord
verifier la liste complete des bundles utilisateurs de la storage — pas seulement ceux
vises par la tache en cours.
