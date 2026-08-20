# ADR-017 : Recadrage requis applique par champ, pas par media

⚠️ **Remplace par [ADR-018](018-images-locales-par-paragraphe.md)** (2026-08-19, meme
jour) : le probleme reel etait plus profond que le manque de widget (voir addendum 2
ci-dessous) — le recadrage Drupal est rattache au fichier, pas a l'usage, et aucune
correction de ce fichier ne pouvait faire varier le cadrage d'une meme photo entre
deux paragraphes. Les 9 champs a ratio impose + `node.news` sont passes en champ image
local (upload direct, sans mediatheque) ; le mecanisme decrit ici (widget
`contextual_image_widget_crop`, table `drivematic_forms`, validation dediee) a ete
retire. Cette page reste comme trace du raisonnement et des options ecartees.

## Statut

Remplace par ADR-018

## Date

2026-08-19

## Contexte

L'addendum du 19/08 a l'[ADR-004](004-pipeline-images.md) corrige l'absence de widget
de recadrage dans le modal « Ajouter un media » (mode de formulaire `media_library`
aligne sur `default`). Mais l'utilisatrice a repousse cette correction : elle exige que
**le recadrage soit exige selon le ratio qu'impose le champ de destination**, que le
media soit **tout juste deverse** ou **deja present dans la mediatheque**.

Contrainte structurelle (ADR-004, verrouillee) : un media `image` est **reutilisable**
et ne porte pas de ratio propre — le ratio est choisi au niveau du champ referant, via
le mode d'affichage. Un meme fichier peut donc etre recadre 1:1 pour un usage et 16:9
pour un autre. Consequence directe : Drupal + `image_widget_crop` n'exposent **aucun**
reglage « ratio requis selon le champ appelant » — `crop_types_required` vit sur le
**mode de formulaire du media** (`default`, `media_library`), identique pour **tous**
les champs qui referencent ce bundle, quel que soit le contexte d'appel.

Deux consequences si on s'arrete a l'addendum ADR-004 seul :
1. Exiger les 3 ratios (1:1, 16:9, 12:5) sur **tout** import, meme pour un champ
   « sans crop » (`image_text_100`, `image_centered`, `product_characteristics`,
   `contact`, `brand`) : friction inutile, hors specification.
2. Aucun garde-fou quand un editeur **reutilise un media existant** deja en base mais
   pas recadre pour le ratio de ce nouvel usage — la selection depuis la grille de la
   mediatheque n'ouvre aucun formulaire, donc ne peut declencher aucun widget de crop.

## Options considerees

### Option A : élargir `crop_types_required` a tous les ratios, partout

- Avantages : trivial (deja fait dans l'addendum ADR-004), aucune ligne de code.
- Inconvenients : ne resout pas le cas de la reutilisation d'un media existant (rien
  n'empeche de le selectionner pour un champ dont il n'a pas le crop) ; sur-demande sur
  les champs « sans crop ».

### Option B : un bundle media par ratio (`image_1_1`, `image_16_9`, `image_12_5`)

- Avantages : le ratio requis serait porte par le bundle, donc par le champ qui le
  cible (`target_bundles`).
- Inconvenients : **contredit l'ADR-004** (« le ratio est choisi au niveau du champ
  referant, pas sur le media ») — un meme fichier reutilise a deux ratios obligerait a
  dupliquer l'entite media. Ecarte.

### Option C : validation dediee au champ, independante du mode de formulaire du media

Une table `{entity_type}.{bundle}.{field_name} -> type de crop requis` (etablie a partir
des modes d'affichage reels, cf. `docs/content-model.md` et le PRD §6), verifiee par un
`#element_validate` attache au widget `media_library_widget` de chaque champ concerne.
Le controle porte sur le **fichier selectionne**, pas sur le formulaire d'import : il
s'applique donc identiquement, que le media soit neuf ou deja present en base.

- Avantages : respecte l'ADR-004 (le media reste reutilisable, seul le controle varie
  par champ) ; couvre la reutilisation d'un media existant ; aucun champ « sans crop »
  n'est jamais concerne (absent de la table).
- Inconvenients : necessite du code custom (hook Drupal) ; ne peut bloquer qu'au moment
  ou le **champ** est valide (voir Consequences).

## Decision

**Option C.** Implementee dans `drivematic_forms` :

- `_drivematic_forms_image_crop_map()` : table de correspondance
  `paragraph.image_text_50.field_image => crop_1_1`,
  `paragraph.image_full.field_image => crop_12_5`,
  `paragraph.{grid,history,jumbo_home,product_cross,product_image,product_video}
  _element.field_image => crop_16_9`, `paragraph.video_centered.field_image =>
  crop_16_9`, `node.news.field_image => crop_16_9`. Absent de la table = pas de
  ratio impose (`image_centered`, `image_text_100`, `product_characteristics`,
  `brand`, `contact`).
- `hook_field_widget_single_element_media_library_widget_form_alter()` attache
  `#element_validate` quand le champ figure dans la table.
- `_drivematic_forms_validate_required_crop()` charge chaque media selectionne
  (`$element['selection']`), verifie `Crop::findCrop($uri, $type)` sur son fichier, et
  bloque la soumission avec un message nommant le media et le format attendu, plus un
  lien direct vers son formulaire d'edition, si le recadrage manque. Ignoree quand le
  bouton « Retirer » declenche la soumission (meme garde que
  `MediaLibraryWidget::validateRequired()`) — on doit toujours pouvoir retirer un media
  fautif.
- Gardee en parallele : l'addendum ADR-004 (widget de crop present dans le modal
  d'ajout) reste utile pour l'import d'un fichier neuf — il force les 3 ratios des la
  creation du media, ce qui satisfait la plupart des champs d'un coup et reduit le
  nombre de fois ou cette nouvelle validation doit intervenir.

## Consequences

**Plus facile**

- Un media incorrectement recadre pour l'usage vise ne peut plus etre insere, qu'il
  soit neuf ou repris de la mediatheque — verifie : tenter de sauvegarder le bloc
  « Un savoir-faire technique reconnu » du node 77 avec le media 54 (sans `crop_1_1`)
  bloque avec « L'image « Corporate — réglage manuel d'un pédalier » doit être
  recadrée au format Carré (1:1)… ».
- La table est **la** reference technique du mapping ratio/champ — plus besoin de
  redecouvrir la regle en lisant chaque mode d'affichage.

**Plus difficile / a surveiller**

- ⚠️ **La validation ne s'execute que si le bloc est ouvert (« Modifier ») dans le
  formulaire du node.** Les paragraphes replies (mode resume) ne reconstruisent pas
  leur sous-formulaire (cf. [[sdc-paragraph-pattern]]) : leurs widgets, et donc cette
  validation, ne font pas partie de l'arbre valide tant qu'on ne clique pas
  « Modifier ». Enregistrer une page sans toucher un bloc existant ne re-verifie donc
  pas son crop — comportement standard de Paragraphs (eviter de forcer une correction
  non liee a chaque sauvegarde), pas un defaut de cette implementation, mais a garder
  en tete en recette.
- Ajouter un nouveau champ image necessite de **penser a l'ajouter a la table** —
  sinon aucune erreur ne previent (le champ se comporte juste comme un champ
  « sans crop »). A verifier a chaque nouveau paragraphe portant une image.
- Les medias 53 et 54 (photos reelles importees le 19/08, cf.
  `docs/active/maquette-integration/progress.md`) restent non recadres : cette
  validation les bloquera desormais tant qu'un editeur n'aura pas pose leur crop 1:1
  en back-office — c'est le comportement voulu, pas une regression.

**Fichiers impactes**

- `web/modules/custom/drivematic_forms/drivematic_forms.module`,
  `drivematic_forms.info.yml` (dependances `crop`, `media_library`).

## Addendum (2026-08-19, meme jour) — le widget de recadrage doit apparaitre, pas
seulement bloquer

La validation ci-dessus bloque l'enregistrement, mais l'utilisatrice a repousse cette
reponse : elle veut voir **le widget de recadrage lui-meme** au moment de l'import —
« j'en choisis une nouvelle [image] de la librairie média. LÀ JE VEUX LA ZONE DE
RECADRAGE » — que l'image soit neuve (deversee) ou deja presente en base, et limitee
au ratio qu'impose le paragraphe en cours.

**Recherche** (voir sources dans la conversation) : deux modules contrib repondent a ce
besoin, l'un manquant sans l'autre :

- **`contextual_image_widget_crop`** (`^1.0`) fournit deux widgets : un pour le champ
  `image` (`contextual_image_widget_crop`, remplace `image_widget_crop` sur
  `field_media_image` du media) qui limite `#crop_list` au(x) type(s) de recadrage
  passes en query string (`?crop-context[]=crop_1_1`) ; et un pour le champ de
  reference (`contextual_image_widget_crop_media_library_widget`, remplace
  `media_library_widget`) qui calcule ce ratio et ajoute un lien **« edit / crop »**
  sur **chaque media selectionne** (neuf ou existant), menant a une modale d'edition
  du media portant deja ce contexte.
- **`media_library_edit`**, installe puis **retire** : son mecanisme (bouton
  « Editer ») fait doublon avec celui, plus riche, de
  `contextual_image_widget_crop_media_library_widget` — inutile, source de confusion
  potentielle (deux liens d'edition sur le meme item).

**Piege d'integration** : `ContextualImageCropMediaLibraryWidget::getImageStyles()`
detecte le ratio en inspectant le **style d'image** configure sur le champ hote
(`responsive_image_style` ou `image_style` du formatter). Nos champs `field_image`
rendent via `entity_reference_entity_view` + un **mode d'affichage media**
(`ratio_1_1`, etc.) — pas un style d'image direct sur le champ — donc cette detection
renvoie toujours vide pour nous : aucun lien « edit / crop » n'apparaissait.

**Resolu** par une sous-classe `DrivematicContextualMediaLibraryWidget`
(`web/modules/custom/drivematic_forms/src/Plugin/Field/FieldWidget/`), qui surcharge
`getImageStyles()` pour interroger `_drivematic_forms_image_crop_map()` (deja la
reference du projet) et renvoyer un `ImageStyle` existant portant le bon effet
`crop_crop` pour ce ratio (`dm_1_1_2560`, `dm_16_9_2560` ou `dm_12_5_2560` — un seul
representant par ratio suffit, `getCropType()` du parent n'en lit que l'effet). Chaque
champ concerne (les 9 champs `field_image` de paragraphe + `node.news`) utilise
desormais ce widget ; le champ `field_media_image` du media (`default` et
`media_library`) utilise `contextual_image_widget_crop`.

**Verifie** (formulaire du paragraphe construit directement en PHP, puis en
navigateur) : le lien genere pour le media 53 sur le bloc `image_text_50` vaut
`/media/53/ajax?crop-context[0]=crop_1_1&...` ; l'ouvrir affiche la modale de
recadrage avec le **seul** onglet « Carré (1:1) (required) ».

**La validation de l'ADR (partie 1, ci-dessus) est conservee** comme filet de
securite : elle bloque l'enregistrement si, malgre tout, le recadrage requis manque
encore (media importe par script, modale fermee sans enregistrer, crop supprime apres
coup). Les deux mecanismes sont complementaires, pas redondants : l'un **montre** le
recadrage a faire, l'autre **garantit** qu'il a ete fait.

**Limite assumee** : pour un fichier **tout juste deverse** via « Ajouter un média »
(pas encore selectionne), le widget `contextual_image_widget_crop` du media n'a pas
encore de `crop-context` dans sa requete (le mecanisme du module ne le propage pas a
ce sous-formulaire specifique) — les 3 ratios s'affichent, comme avant cet addendum.
Le lien « edit / crop », lui, apparait desormais sur ce nouveau media une fois
selectionne, et permet de re-ouvrir un recadrage limite au bon ratio si besoin. Non
bloquant : la validation de securite (partie 1) couvre ce cas de toute facon.

## Addendum 2 (2026-08-19, meme jour) — `crop_types_required` bloquait sur les ratios masques

Bug remonte par l'utilisatrice immediatement apres l'addendum 1 : en enregistrant le
media depuis le lien « edit / crop » (narrowe au seul `crop_1_1`), le formulaire
refusait avec « Paysage (16:9), Bannière (12:5) are required. » — alors qu'aucun de
ces deux onglets n'etait meme affiche.

**Cause** : `ContextualImageCropWidget::formElement()` ne restreint que `#crop_list`
(quels onglets sont **rendus**) via le `crop-context` de la requete ; il ne touche
jamais `#crop_types_required` (quels crops sont **obligatoires**). Ce dernier restait
donc a sa valeur configuree sur le widget — `[crop_1_1, crop_16_9, crop_12_5]`, posee
par l'addendum ADR-004 du meme jour (« exiger les 3 ratios sur tout media, quel que
soit le contexte »). Resultat : le formulaire exigeait deux crops que l'utilisateur ne
pouvait plus voir ni renseigner — un blocage sans issue.

**Corrige** : `crop_types_required` vide sur `core.entity_form_display.media.image.
default` et `.media_library` (`crop_list` reste aux 3 ratios, pour l'ecran
d'administration autonome qui n'a pas de contexte). Le media redevient donc
enregistrable sans qu'aucun recadrage soit impose **a ce niveau** — l'obligation « ce
champ-ci exige ce ratio-la » retombe entierement sur la validation de la partie 1
(`_drivematic_forms_validate_required_crop`), qui reste, elle, precise par champ et
inchangee. Verifie : enregistrer le media 53 depuis `/media/53/edit?crop-context[]=
crop_1_1` reussit sans erreur.

**Lecon** : un module qui « limite l'affichage a un sous-ensemble » ne rend pas pour
autant ce sous-ensemble **suffisant** au regard d'une regle de validation posee
ailleurs (ici, sur le widget lui-meme) — les deux reglages (`crop_list` /
`crop_types_required`) sont independants et doivent etre revus ensemble a chaque fois
que l'un des deux change de logique.
