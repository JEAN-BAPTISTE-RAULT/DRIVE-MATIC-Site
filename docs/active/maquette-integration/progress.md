# Intégration des maquettes — progression

> Chantier ouvert le 2026-08-18. Fichier Figma : `ZmmVBSOWSsHVkok6EU2Ays`.
> Reprise après `/clear` : lire ce fichier, puis `CLAUDE.md` et la mémoire auto.

## Décision prise le 2026-08-18 : suppression de `field_title` (remplace l'ADR-011)

`field_title` **côté node** disparaît des 12 types de contenu ; le `title` (ex-« titre
administratif ») devient la source unique du titre affiché, du breadcrumb et du motif d'URL.
⚠️ `field.storage.paragraph.field_title` (21 bundles, 21 templates SDC) **reste intact** :
c'est le titre des blocs, ne pas confondre.

Titres réalignés pour préserver les alias existants (validé) :
node 46 → « Actualités », node 62 → « Questions fréquentes »,
node 69 → « Configurez votre véhicule et obtenez votre tarif ».

Où s'affiche le `<h1>` :
- `homepage`, `transform`, `product` → le **premier paragraphe** le porte (prop `heading_level`),
  et le bloc titre de page est masqué sur ces bundles. `image-full` et `text-centered` affichaient
  déjà leur titre à la taille H1 : le changement est purement sémantique.
- tous les autres types → le bloc titre de page rend le `title` en `<h1>`, au-dessus du contenu.

Corrigé au passage : le bloc titre était en région `sidebar_first`, rendue **après** le contenu
par `page.html.twig` → d'où un `<h1>` en bas de page. Déplacé en région `content`, poids -10.

**LIVRÉ le 2026-08-18** — acté dans [ADR-014](../../../.claude/decisions/014-titre-unique-porte-par-le-title.md),
CLAUDE.md, README, PRD (écarts #2 et #3 passés en résolus). 91 fichiers de config,
`npm run lint` et `format:check` à 0, `config:status` en phase.
Vérifié : **exactement un `<h1>`** sur les 10 types rendus, alias tous préservés,
`<title>` et fil d'Ariane alimentés par le `title`.

Piège rencontré : `news-card` et `news-teaser` lisaient `node.field_title.value` →
**500 sur la home** après suppression du champ. Corrigés en `node.title.value`. Le premier
`grep` les avait manqués : sa sortie était tronquée par un `head -20`. Vérifier
`grep -c` avant de conclure qu'une référence est isolée.

## Pages à vérifier / intégrer

| # | Page | Node | Node-id Figma | État |
|---|------|------|---------------|------|
| 1 | Accueil | 31 | `303-5967` | ✅ **conforme, rien à changer** — voir « Le lorem est celui des maquettes » |
| 2 | Qui sommes-nous | 54 | `433-9747` | ✅ **mesurée le 2026-08-19** (le « 6 blocs conformes » du 18/08 portait sur le contenu, pas la mise en page — voir « 11bis » plus bas). 2 vraies photos de la maquette réintégrées, logo UTAC sur le bloc « qualité certifiée » |
| 3 | **CGV** (type `legals`) | 55 | `469-11689` | ✅ **intégrée** — 15 sections `text_left_aligned`. Le frame s'appelle « Conditions générales de vente » : le type `legals` (« Page :: Mentions légales ») porte les CGV. Titre et alias changés, 301 auto depuis /mentions-legales |
| 4 | **FAQ** | 62 | `396-11620` | ✅ **intégrée le 2026-08-19** (le « intégrée » précédent portait sur le contenu : filtre en liste verticale à puces collée au bord gauche, pas de 95px au lieu de 87 — voir « 4bis » plus bas). Titre « FAQ : Nous répondons à vos questions », motif Pathauto **supprimé**, alias en dur `/faq`, 301 depuis /questions-frequentes |
| 5 | Documentations | 67 | `398-12119` | ✅ **restructurée** — type de node `document` supprimé, les 2 champs passés en **Fichier illimité**, titres de section en dur dans le Twig |
| 6 | Les marques partenaires | 68 | `433-7148` | ✅ **intégrée le 2026-08-18** (le « conforme » précédent portait sur le contenu : la grille sortait en **une colonne de tuiles de 1440px**, page de 17 910px de haut — voir « 6bis » plus bas). Titre repris de la maquette, motif Pathauto supprimé, alias `/marques-partenaires` en dur. **27** logos alphabétiques dans `brands-grid` |
| 7 | Actualités | 46 | `438-10209` | ✅ **intégrée le 2026-08-18** (le « conforme » précédent portait sur le contenu, pas sur la mise en page — voir plus bas). Alias `/actualites` en dur, `body` masqué |
| 8 | Une actualité | 17 | `438-10665` | ✅ **intégrée le 2026-08-19** (le « conforme » précédent portait sur l'ordre des champs : la page rendait la date, la légende et le corps **collés au bord gauche**, et le visuel sur 1440 recadré en 16:9 — voir « 8bis » plus bas). SDC `news-article`, visuel sans recadrage, colonne unique de 960 |
| 9 | Contact | 1 | `433-7637`, `438-9060`, `438-9465`, `438-9456`, `438-9457` ✅ | **formulaire stylé et modales faites** (cf. « 9bis » plus bas). Les 4 frames restants n'étaient pas des « états du formulaire » : deux sont les **variantes SAV et question**, deux les **modales « carte grise »**. Reste hors formulaire : la ligne adresse + carte au-dessus de la carte grise du formulaire |
| 10 | Devenir partenaire | 2 | `438-9838` | ✅ **conforme** — titre puis formulaire, `body` masqué (aucun chapô dans la maquette), mention « *Champs obligatoires » activée |
| 11 | Nos ateliers | **77** | `436-2486` | ✅ **mesurée le 2026-08-19** — titre, chapô (`body`), 2 `image_text_50` alternées. 1 vraie photo de la maquette réintégrée (bloc 1), le bloc 2 reste un placeholder bibliothèque (grey rectangle réel dans la maquette). Alias `/nos-ateliers` |
| 12 | Recherches et développement | **78** | `436-8300` | ✅ **mesurée le 2026-08-19** — titre, chapô (`body`), 2 `image_text_50` alternées (placeholders bibliothèque confirmés — les deux blocs sont de vrais rectangles gris dans la maquette), puis un `text_centered` (« Innover aujourd’hui… »). Alias `/recherches-et-developpement`. Le titre retenu est celui de la maquette, pas le « Recherche & développement » du PRD F9 |
| 13 | Savoir-faire et certifications | **79** | `436-8578` | ✅ **mesurée le 2026-08-19** — titre, chapô, 2 `image_text_50` **toutes deux image à gauche** (pas d'alternance ici). Logos UTAC et ISO 9001 exportés de la maquette (médias 27 et 28). Alias `/savoir-faire-et-certifications`. Le lien en attente du node 54 est câblé |

Déjà conformes (vérifiées le 2026-08-18) : node 52 (`363-9316`), node 76 (`389-10805`), node 75 (`390-11137`).
Les 6 produits sans maquette (53, 70-74) portent un `image_full` seul, conformément à la consigne.

## « 8bis » — Détail d'une actualité, intégration réelle (mesuré le 2026-08-19)

**État trouvé.** La page était le **seul rendu `news` sans SDC** : `node--news.html.twig`
déversait `{{ content }}` sans enveloppe, et aucune règle n'existait pour `.node--type-news`
ni `.node__date`. La date, la légende et le corps sortaient donc à **x = 0**, sans gouttière ;
le visuel occupait les **1440px de la fenêtre, recadré en 16:9**. Seuls les paragraphes
portaient une colonne — et pas la même : 960 pour `text_left_aligned`, 900 pour
`video_centered`. Le suivi disait « conforme » parce qu'il n'avait vérifié que l'ordre des
champs.

**Mesures de la maquette** (frame `438:10665`, cadre 1440) : titre `x=270 w=900` centré en
Exo 2 Bold **45/58** `#2F3A45`, à **49px** sous le filet de l'en-tête ; date centrée en Inter
**16/28** `#666666` ; visuel `x=270..1170` **rayon 16** (nœud `438:10992`) ; légende ferrée à
droite en **14/28** `#666666`. Écarts de boîte à boîte : titre → date **16**,
date → visuel **35**, visuel → légende **5**, légende → bloc **11**.

**Déjà conforme, rien à changer** : `.page-title` était exactement aux valeurs de la maquette
(les tokens `--dm-h1-size`/`--dm-h1-line` valent 45/58, `--dm-color-steel` vaut #2F3A45,
`--dm-space-page` vaut 49). Idem pour la date et la légende : `--dm-body-size`/`--dm-body-line`
valent 16/28 et `--dm-color-grey-text` vaut #666666. Aucune valeur typographique redéclarée.

**Trois décisions de l'utilisatrice.**

1. **Le visuel tient la colonne**, pas la fenêtre — « pleine largeur » = largeur du contenu.
2. **La colonne de cette page vaut 960, pas 900** : `text_left_aligned` reste sur son état
   consigné (960, partagé avec les CGV, « Qui sommes-nous » et les pages produit) et **tout le
   reste de la page s'aligne sur lui**, plutôt que faire cohabiter deux colonnes.
   D'où un token `--dm-content-column` (900 par défaut) **retuné à 960 sur
   `body.page-node-type-news`** : le titre de page est un **frère** du contenu, la valeur ne
   pouvait pas vivre sur le SDC.
3. **Les écarts internes reprennent `--dm-space-element` (24)**, pas les 16 et 35 de la
   maquette.

**Le visuel sans recadrage n'a demandé aucune config nouvelle** : le view mode média `free`
(→ responsive image style `dm_free` : `image_scale` sur la largeur, `height: null`,
`upscale: false`, WebP, **aucun `crop_crop`**) existait déjà et servait 6 autres displays.
Une ligne : `ratio_16_9` → `free`. ⚠️ Les vignettes des listes et du carrousel home restent
en `ratio_16_9` : **l'obligation de recadrage à l'import ne bouge pas**, seule la description
du champ a été reformulée pour dire à l'éditeur que la page de détail affiche l'original.

**Rendu obtenu** (navigateur, viewport 1440, anonyme) — maquette entre parenthèses :
colonne **240..1200 (960)** pour les **sept** éléments — titre, date, visuel, légende, corps,
titre du bloc texte, cadre vidéo ; **49** entre le haut de `main` et le titre (49) ;
titre → date **24** (16), date → visuel **24** (35), visuel → légende **8** (5),
légende → corps **24**, corps → titre du bloc **32**, bloc texte → cadre vidéo **64**
(2 × `--dm-space-block`, la maquette dit 50). Légende ferrée à **1200**, comme celle de la
vidéo. Visuel servi au ratio **2.107** du fichier source (2048×972) et non en 16:9 (1.778) :
le recadrage a bien disparu. Un seul `<h1>`, aucun débordement horizontal.

**Deux accidents corrigés au passage.**

- `video-centered` et `image-centered` sont des `<figure>`, et le reset global ne couvre pas
  `figure` : le navigateur y posait **`margin: 1em`**, soit 16px parasites au-dessus et
  au-dessous. L'écart bloc texte → bloc vidéo faisait donc **80** au lieu des 64 du rythme
  documenté. `margin-block: 0` dans les deux. Vérifié sur le bac à sable (node 33) : leur
  colonne reste à **270..1170 (900)**, seul l'écart vertical change.
- Le test `{% if block('x') is not empty %}` **ne suffit pas** dans un SDC : un champ vide
  rend un slot fait d'espaces, que `empty` laisse passer — d'où une enveloppe vide et sa
  marge. Il faut **`|trim`**. Constaté sur `news-article` en rendant le node 18 sans visuel ni
  corps *en mémoire* (`renderInIsolation`, sans `save()` — sauvegarder aurait **redaté** le
  node et l'aurait fait remonter dans les Vues). `text_left_aligned` n'a **pas** le problème :
  un champ sans valeur n'y produit aucun `content.field_*`, le slot est vraiment vide.

**Cas limites rejoués** : sans légende → pas de `<figcaption>` orphelin ; sans bloc (node 18)
→ c'est `.news-article__lede:last-child` qui pose l'écart au pied de page, mesuré à **49** ;
sans visuel ni corps → aucune enveloppe vide. Non-régression vérifiée sur `/qui-sommes-nous`
et le node 33 (colonne toujours 900), `/actualites` et la home (toujours `dm_16_9`).

**Écarts assumés, écrits.** Le **fil d'Ariane** est rendu alors que la maquette n'en montre
aucun (élément de shell de page, à trancher avec F2, comme l'en-tête et le pied de page).
Les écarts internes sont à 24 au lieu de 16 et 35 (décision 3). `dm_free` est dimensionné
pour la pleine fenêtre : servi dans une colonne de 960 ses dérivés sont surdimensionnés
(dette `sizes`, ADR-004). `links` reste visible dans le view display `default` (poids 100)
alors qu'il est masqué sur `teaser` — sans effet en anonyme. Et le contenu du 1er bloc du
node 17 dit « En savoir plus » / « Dossier de presse » là où la maquette dit « Lien vers
site » / « Télécharger » : saisie éditoriale sur un node de test, hors intégration.

## « 11bis » — Les 4 pages « corporate », mesure et vraies photos (2026-08-19)

**Constat de départ.** Les 4 pages (54, 77, 78, 79) n'avaient jamais reçu la passe
« mesurer, comparer au rendu » — seul leur contenu avait été vérifié à leur création.
Contrairement aux autres pages reprises ce jour-là, le bloc titre (`_page-title.scss`)
et le chapô (`page-intro.scss`) étaient **déjà mesurés directement sur 3 de ces 4
maquettes** (`436-2486`, `436-8300`, `436-8578` cités en commentaire de leurs
fondations) : la charpente haute de page n'était donc pas un risque réel, contrairement
à ce qu'un premier balayage aurait pu laisser croire.

**Découverte non documentée : deux blocs des pages 54 et 77 ont une vraie photo dans
la maquette, pas un rectangle gris.** La note « les visuels corporate sont des
rectangles gris » ([[home-content]]) était vraie pour `78` et pour un bloc sur deux de
`77`/`54`, mais pas générale — elle avait été tirée des pages `78`/`79` et appliquée
par erreur à l'ensemble du type. Vérifié bloc par bloc via `get_screenshot` puis
`download_assets` :

- Node 54, bloc « Notre engagement » : la maquette montre un technicien DRIVE MATIC
  LEGRAND consultant une tablette devant un véhicule (photo réelle, pas de placeholder).
  Le node pointait vers `home-jumbo-auto-ecole.jpg` (média 5, un visuel générique de la
  home) → **remplacé par la vraie photo** exportée de Figma (média 53,
  `public://corporate/qui-sommes-nous-engagement.png`).
- Node 54, bloc « La qualité certifiée » : la maquette montre les logos UTAC **et** ISO
  9001 empilés dans une seule zone image. `image_text_50` n'a qu'un seul `field_image` —
  **décision de l'utilisatrice (option A)** : n'afficher que le logo UTAC (média 27, déjà
  importé pour le node 79, déjà recadré 1:1). Le node pointait vers `home-jumbo-pmr.jpg`
  (média 6, sans rapport) → corrigé.
- Node 77, bloc « Un savoir-faire technique reconnu » : la maquette montre des mains
  réglant un pédalier dans un habitacle (photo réelle). Le node pointait vers
  `home-savoir-faire.png` (média 11, réutilisé depuis la home) → **remplacé** par la vraie
  photo (média 54, `public://corporate/nos-ateliers-savoir-faire.png`).
- Les placeholders bibliothèque restants (node 54 bloc « 65 ans », node 77 bloc
  « réseau de partenaires », les 2 blocs du node 78) sont **confirmés comme de vrais
  rectangles gris dans la maquette** (screenshot à l'appui) : le remplacement par une
  image de la bibliothèque déjà en place est correct, rien à changer. La note rouge sur
  le bloc « 65 ans » (« Mettre les différents logo Drive Matic avec leur date ») reste une
  instruction de designer sans asset réel fourni — non actionnable.

⚠️ **Les 2 nouvelles photos sont importées sans crop fabriqué** (média 53 et 54) —
conformément à [[crop-obligatoire-manuel]], aucun recadrage n'a été posé par script.
Elles s'affichent donc actuellement **non recadrées** dans leur slot 1:1 (510×340 au
lieu de 510×510, ratio natif de la photo conservé). **Reste à faire en back-office** :
poser un crop 1:1 manuel sur ces deux médias avant publication définitive.

**Mesures reprises sur le rendu (navigateur, 1440, anonyme)** — les 4 pages :

- Un seul `<h1>`, centré, colonne **270..1170 (900)**, comme les 3 maquettes déjà citées
  en fondation.
- `--dm-space-page` (49px) au-dessus du titre, écart titre → chapô conforme à l'arbitrage
  déjà pris (49 au lieu des 40 de la maquette, cf. `page-intro.scss`).
- Écart dernier bloc → footer mesuré à **0** sur les 4 pages : pas une régression, chaque
  bloc pose déjà son `padding-block` de 32px (`--dm-space-block`) en interne, à l'identique
  du reste du site.
- Aucun débordement horizontal réel sur `77`, `78`, `79`. Sur `54`, un écart de 20px avait
  été attribué au carrousel « Notre histoire » qui déborde volontairement d'un côté (piste
  Swiper, exception documentée par ADR-013) — **conclusion erronée, corrigée le
  2026-08-20** : c'était un vrai bug de calcul (`calc(50% - 50vw)` posé sur un enfant du
  conteneur au lieu du conteneur lui-même, résolvant son `%` contre la mauvaise base — 20px
  de constante, indépendante de la largeur de fenêtre). Présent aussi sur `jumbo_home`,
  `news_home`, `product_features`. Corrigé, cf. [ADR-008](../../../.claude/decisions/008-slideshow-swiper.md)
  addendum du 20/08 et [CLAUDE.md](../../../CLAUDE.md) section SCSS/SDC.
- Node 79 : les deux `<p>` du bloc UTAC (héritage d'une saisie en 2 paragraphes alors que
  la maquette est un texte continu) ont été fusionnés en un seul, pour ne pas introduire
  un écart vertical absent de la maquette.

## « 11ter » — Deux vrais bugs remontés par l'utilisatrice après « 11bis »

La passe « 11bis » avait conclu trop vite sur deux points : une vérification de
`.page-intro` qui n'a pas remarqué son absence, et l'hypothèse (jamais vérifiée) que
le pipeline crop restait sans faille hors script. Les deux se sont révélés faux.

**1. Le chapô n'était mis en page sur AUCUNE des 4 pages.** Cause : il n'existe pas de
`node--corporate.html.twig` — le type tombait donc sur le `node.html.twig` générique,
qui rend `{{ content }}` brut (le champ `body` sans l'enveloppe `page-intro`, donc sans
colonne 900, sans centrage, sans le `padding-block-start` de 49px). Le gabarit
`text-align/width` de `page-intro.scss` n'était simplement jamais atteint. Créé
`node--corporate.html.twig` sur le modèle de `node--documents.html.twig` (chapô dans
`{% embed 'drive_matic:page-intro' %}`, puis `content.field_paragraphs`). Vérifié après
coup sur les 4 pages : chapô à x=270/w=900, centré, `padding-block-start: 49px`.

⚠️ **Piège méthodologique à ne pas reproduire** : la mesure « 11bis » avait bien
interrogé `document.querySelector('.page-intro')`, mais le script ignorait le cas où le
sélecteur ne matchait rien (la clé `introBox` disparaissait simplement du JSON au lieu
d'afficher une erreur) — d'où une« page-intro déjà mesurée sur ces maquettes » présumée
à tort. **Toujours vérifier explicitement qu'un `querySelector` a retourné un élément
avant d'utiliser son absence comme preuve d'absence de problème.**

**2. Le recadrage n'était pas proposé du tout en ajoutant une image en contexte**
(depuis le widget media library d'un paragraphe — le chemin qu'utilise réellement un
éditeur). Cause détaillée dans l'addendum du 2026-08-19 à
[ADR-004](../../../.claude/decisions/004-pipeline-images.md) : le mode de formulaire
`media_library` du bundle `image` portait le widget `image_image` (sans crop) et non
`image_widget_crop`, contrairement au mode `default` (utilisé seulement par la page
d'admin autonome, jamais par un éditeur en pratique). Corrigé par config
(`core.entity_form_display.media.image.media_library`), vérifié en simulant un upload
dans le modal : les 3 onglets de recadrage requis apparaissent.

Les médias 53 et 54 (photos réelles importées en 11bis, toujours sans crop) devront
être recadrés par cette voie corrigée — désormais possible, ce qui ne l'était pas avant
ce correctif.

**Suite (même jour)** : ce correctif restait insuffisant selon l'utilisatrice — il
exigeait les 3 ratios sur tout import, sans lien avec le champ de destination, et ne
couvrait pas la réutilisation d'un media déjà en base. Voir [ADR-017](../../../.claude/decisions/017-recadrage-requis-par-champ.md) :
une validation dédiée par champ (`drivematic_forms`) bloque désormais la sauvegarde du
node tant que le media sélectionné — neuf ou repris de la médiathèque — n'a pas le
recadrage exigé par CE champ précis. Vérifié : sauvegarder le node 77 avec le média 54
(sans `crop_1_1`, requis par `image_text_50`) est bloqué avec un message nommant le
media et le format attendu ; retirer le média reste possible. ⚠️ Ne s'exécute que si le
bloc est ouvert (« Modifier ») dans le formulaire — un bloc replié non touché n'est pas
revalidé à l'enregistrement (comportement standard de Paragraphs).

**Suite (même jour, 3e passe)** : l'utilisatrice a précisé qu'elle voulait voir
apparaître **le widget de recadrage lui-même** à l'import (neuf ou depuis la
médiathèque), pas seulement un blocage à l'enregistrement. Ajout des modules
`contextual_image_widget_crop` (widgets contextuels) et retrait de `media_library_edit`
(redondant). Un widget custom `DrivematicContextualMediaLibraryWidget` fournit le ratio
exigé (déjà connu via `_drivematic_forms_image_crop_map()`) là où le module ne peut pas
le déduire seul. Détail complet, limite assumée (fichier tout juste déversé, avant
sélection) et vérification dans l'addendum du 19/08 à [ADR-017](../../../.claude/decisions/017-recadrage-requis-par-champ.md).
Vérifié en navigateur : sélectionner le média 53 (non recadré) sur le bloc « 65 ans »
du node 54 affiche un lien « edit / crop » qui ouvre une modale limitée au seul onglet
« Carré (1:1) ».

**Bug immédiat, corrigé (4e passe)** : enregistrer le média depuis cette modale
refusait avec « Paysage (16:9), Bannière (12:5) are required » — les deux onglets
masqués restaient exigés par le widget lui-même (`crop_types_required`, posé à « les 3
toujours » lors du 1er correctif de ce fil). Vidé sur les deux modes de formulaire du
média ; l'obligation retombe entièrement sur la validation par champ (déjà en place).
Vérifié : l'enregistrement du média 53 depuis le lien scopé réussit sans erreur.
Détail dans l'addendum 2 de [ADR-017](../../../.claude/decisions/017-recadrage-requis-par-champ.md).

**Rebondissement final (même jour, 4e passe)** : tout ce qui précède répondait à la
mauvaise question. Le vrai problème : le recadrage Drupal est rattaché au **fichier**,
pas à l'usage — réutiliser la même image dans deux blocs `image_text_50` impose donc
le même cadrage aux deux, sans moyen de le varier. Deux corrections écartées par
l'utilisatrice : dupliquer le fichier (« ridicule », et la médiathèque affiche tout en
vignettes carrées indistinguables) ; `media_contextual_crop` (stocke le recadrage par
référence) exige un **patch sur Drupal core** touchant le contrôleur qui sert toutes
les images du site — écarté après l'avoir concrètement testé.

**Solution retenue** ([ADR-018](../../../.claude/decisions/018-images-locales-par-paragraphe.md)) :
les 9 paragraphes à ratio imposé + `node.news` (champ renommé `field_photo`, son
ancien storage étant partagé avec `node.brand`/`node.contact`, non concernés) passent
en **champ image local** — upload direct dans le paragraphe, sans médiathèque, exactement
le modèle des autres sites Passerelle. Le recadrage étant lié au fichier et non au
champ, **aucun recadrage existant n'a été perdu** : 72 valeurs de paragraphe (dont 36
imbriquées dans un bloc via `field_elements`/`field_jumbo_elements`/
`field_cross_elements`/`field_features_elements`) + 32 `node.news` capturées puis
restaurées à l'identique sur les nouveaux champs — vérifié exhaustivement, 104/104,
aucun fichier manquant. Le mécanisme de l'ADR-017 (widget custom, validation,
`contextual_image_widget_crop`) est retiré, devenu sans objet.

⚠️ **Écart trouvé et corrigé en vérifiant** : le bloc « 65 ans d'expertise » du node 54
portait par erreur le même fichier que « Notre engagement » (glissement survenu
pendant les tests interactifs de ce fil) — remis sur `home-savoir-faire.png` avant
migration.

⚠️ **Point non tranché, à signaler** : en testant le widget de recadrage scopé
(addendum 1 de l'ADR-017), un enregistrement sans interaction a validé un crop
**par défaut** (`show_default_crop`) sur le média « Corporate — technicien et
diagnostic véhicule » — une position plausible mais **non choisie par un éditeur**.
Ce fichier est désormais utilisé directement (media abandonnée) ; son cadrage actuel
mérite une relecture en back-office, comme les cas relevés dans l'audit du 18/08.

⚠️ **Bug trouvé en vérifiant avant de livrer, corrigé le même jour** : `field.storage.
paragraph.field_image` est une storage partagée par les **12** bundles de paragraphe
(les 9 à ratio + les 3 « sans crop »), pas seulement les 9 visés. La convertir en
`image` a donc aussi converti silencieusement `image_centered`/`image_text_100`/
`product_characteristics` — perte de la médiathèque là où ADR-018 disait explicitement
de la garder (aucune perte de donnée : fichier/alt restent corrects). Corrigé en
appliquant aux 9 bundles à ratio le même renommage que `node.news` (nouveau champ
`field_photo`), ce qui libère `field_image` pour les 3 bundles sans crop, dont la
storage a été recréée en `entity_reference`/media et les valeurs restaurées par
recherche inverse fichier → media (unique, vérifiée). Vérifié de bout en bout après
coup : `entity.form_builder` sur chaque bundle des deux groupes, rendu front (200,
alt correct), `drush cst` propre sur tous les objets de config concernés. Détail dans
l'addendum du 19/08 à l'[ADR-018](../../../.claude/decisions/018-images-locales-par-paragraphe.md)
et [[shared-storage-check-all-bundles]] (mémoire).

## « 4bis » — FAQ, intégration réelle (mesuré le 2026-08-19)

**Découplage d'abord** (consigne de l'utilisatrice). La page empruntait les SDC
`accordion` / `accordion-element`, ceux des **paragraphes** du même nom utilisés par la home
et les pages transform. Or la FAQ est une **liste de contenus `question` filtrable**, pas un
bloc éditorial : elle a désormais ses propres composants, `faq-list` (enveloppe + behavior
`driveMaticFaqList`) et `faq-question` (la ligne), sur le modèle `news-list` / `news-teaser`.
`accordion*` n'a pas été touché — la home reste à 95px de pas, glyphe `#1A1A1A`, mesuré après
coup. Contrepartie assumée : la logique de dépliage et la pilule de CTA sont dupliquées.

**État trouvé.** Le filtre sortait en **liste verticale à puces, collée au bord gauche**
(204px de haut, x=0), libellé « Catégorie » visible, 4 entrées (« - Tout - » en tête, puis
l'ordre alphabétique) et un bouton « Toutes les catégories » dès qu'une catégorie était
choisie. Le chapô du node s'affichait alors que la maquette n'en a pas. L'accordéon était au
pas de 95px au lieu de 87, gouttière 24 au lieu de 61, réponse sur 900 au lieu de 785,
glyphe redessiné en deux barres de 22×2 en `#1A1A1A` au lieu des assets `+`/`×` en `#B5B5B5`.

**Décidé avec l'utilisatrice** : 3 onglets comme la maquette, **aucun actif à l'arrivée** ;
le retour à « toutes les catégories » se fait en recliquant l'onglet actif (lien natif BEF,
il pointe déjà sur `/faq`). L'entrée « tout » est retirée dans
`drive_matic_preprocess_bef_links()` — côté **variables du template**, jamais `#options`,
sinon la validation « choix illégal » du Form API rejette la valeur `All` par défaut.

**Rendu obtenu** (navigateur, viewport 1440, anonyme) — maquette entre parenthèses :
barre 525×54 centrée sur 720 (526×54), onglets 169×42 à x = 463,5 / 635,5 / 807,5
(463 / 635 / 808), colonne 900 à x=270,
**pas entre filets 87** (87), cercle 54, glyphe fermé `plus.svg` 16 en `#B5B5B5` et ouvert
`close.svg` 14 (14×14 et 12×12 d'arête dans la maquette), ligne → réponse 12 (12), largeur
de réponse 785 (785), réponse → CTA 16 (16), dernier élément → filet 16 (16).
Un seul `<h1>`, questions en `<h2>` (elles étaient en `<h3>` sous le `<h1>`), aucun
débordement horizontal.

**Deux valeurs viennent du symbole `317:6612`, pas de la page** : celle-ci pose 83px sur
certains pas et 87 sur d'autres (décalages de calque). Le symbole, qui définit le composant,
dit 87 — et 16px sous le dernier élément là où la page dit 18 à 20.

**Le gabarit vertical ne suit PAS la maquette, par décision de l'utilisatrice** (2026-08-19) :
la charpente de toutes les pages est cadencée par le seul `--dm-space-page` (49px) — au-dessus
du titre, au-dessus **et** au-dessous du filtre, au-dessous de la liste. La maquette posait
63 sous le titre et 84 avant la première question ; les actualités avaient déjà tranché de la
même façon (113/103 ramenés à 49). ⚠️ Corollaire : `faq-list` n'a **pas** de
`padding-block-start` — le filtre a déjà posé l'écart, deux paddings ne s'additionnent pas.
Règle consignée dans CLAUDE.md et le README.

**Trou comblé au passage** : quand aucune question ne correspond, Views rend `view-empty`
**à la place** des lignes, donc **sans** passer par le template qui pose `faq-list` — le
message sortait pleine largeur, collé au bord gauche. Habillé dans `src/scss/_views-faq.scss`
avec la même colonne que la liste qu'il remplace.

⚠️ **La Vue trie sur `changed` décroissant** : re-sauvegarder une question la fait remonter
en tête de page. Vécu en testant l'état vide (dépublication/republication des 2 questions
PMR) ; ordre d'affichage rétabli à la main. Si l'ordre doit être éditorial, il faudra autre
chose qu'un tri sur la date de modification.

## « 6bis » — Marques partenaires, intégration réelle (mesuré le 2026-08-18)

**État trouvé.** `.brands-grid` était en `flex-wrap` avec `gap: 16px` et sans colonne,
face à un `.brand-logo` en `width: 100%` + `aspect-ratio: 1` : chaque tuile prenait les
1440px du conteneur et passait à la ligne. **12 tuiles de 1440×1440, page de 17 910px.**
Le chapô sortait pleine largeur, aligné à gauche, en `#1A1A1A`. Le SCSS le disait
(« L'integration fine reste a faire ») pendant que le suivi disait « conforme ».

**Mesures de la maquette.** 7 tuiles carrées de 144,15 par rangée, colonne x=156→1283
dans un cadre de 1440, écarts 19,8 (colonne) et 21,9 (rangée — la 1re rangée de la
maquette est à 26,9, décalage du calque, les deux autres à 21,9). Chapô à x=270, l=900,
Inter 16/28 en `#666`, centré — **identique sur `436-2486` (Nos ateliers)**, d'où un SDC
et non une règle propre à cette page. Tuile : rayon 10,98, bordure 0,686, fond blanc.

**Rendu obtenu** (navigateur, viewport 1440) : colonnes à x = 155 / 319,3 / 483,6 / 647,9
/ 812,1 / 976,4 / 1140,7 (maquette 156 / 319,8 / 483,7 / 647,5 / 811,3 / 975,2 / 1139),
tuile 144,28 (maquette 144,15), `gap: 22px 20px`, rangées à y = 496 / 663 / 829 / 995
(pas de 166, celui de la maquette), chapô à x=270 l=900 h=112 centré `#666`,
un seul `<h1>`, zéro lien vers un 403, page de 1 400px, aucun débordement horizontal.

**Le nombre de colonnes n'est pas écrit en dur** : `minmax(min(132px, 100%), 1fr)` donne
exactement 7 colonnes dans la colonne de 1130 (huit en exigeraient 1196) et en retire une
à chaque resserrement — vérifié à 4 colonnes à 768 et 2 à 375, sans media query.

**Bug corrigé au passage dans `brand-logo` (partagé avec la home).** Drupal empile quatre
conteneurs entre `.brand-logo__image` et l'image (champ > media > champ > `<picture>`) :
le `max-height: 100%` de l'image se résolvait donc contre une hauteur `auto` et ne
contraignait **rien**. Seule la largeur plafonnait — elle se propage d'elle-même, un bloc
de largeur `auto` remplissant son parent — ce qui masquait le trou tant que **tous** les
logos étaient en paysage. Le premier logo en portrait (Škoda, 210×240) débordait sa tuile
de 9,7px. Corrigé en `display: contents` sur les conteneurs intermédiaires. Débordement
maximal désormais **0** sur les 27 tuiles, page Marques **et** carrousel de la home.

**Contenu.** 15 marques ajoutées (Audi, BMW, Maxus, Mazda, Mercedes-Benz, Opel, Peugeot,
Renault, Seat, Škoda, Smart, Toyota, Volkswagen, Volvo, XPeng) — logos exportés de la
maquette, médias 38→52, fragments 106→120, tous en 403 anonyme. Rendus en mode `free`
(style `dm_free`) : **aucun ratio imposé, donc aucune entité `crop` en jeu** — la règle du
recadrage manuel n'est pas contournée. Le chapô a été repris de la maquette, lorem
compris, à la place de la phrase rédigée qui s'y trouvait.

### Écarts assumés, à ne pas redécouvrir comme des bugs

- **Rythme vertical à 49 partout** (`--dm-space-page`) là où la maquette pose 40 sous le
  titre, 65 au-dessus de la grille et 188 en dessous. Même arbitrage que sur la liste
  d'actualités, reconduit explicitement par l'utilisatrice.
- **Bordure de tuile à 1px** là où la maquette dit 0,686 : le groupe de la maquette est un
  calque mis à l'échelle (210px × 0,686). Un filet sous le pixel n'est pas rendu de façon
  fiable, et la home est déjà à 1px.
- **Taille des logos dans la tuile** : la maquette place chaque logo à sa taille propre
  (de 44 % à 90 % de la tuile) ; le composant ne peut que plafonner. Le `padding: 16px`
  existant donne 76,4 %, soit la densité moyenne relevée sur les 27 logos.
- **Hors périmètre, mais fausse la page** : le **header** fait 143,4px de haut au lieu de
  90 (le menu s'écrase sur 116px de large — le shell de page F2 n'est pas intégré), le
  **footer** 140 au lieu de 324, et le **fil d'Ariane** ajoute 36px entre le header et le
  titre (contenu réel, absent des maquettes). L'écart header→titre mesure donc 85 au lieu
  de 49 : les 49 sont bien posés, les 36 viennent du fil d'Ariane.

### Auto-revue

1. **Décision la plus difficile** — où loger le CSS du chapô. C'est du markup de champ du
   cœur, ce qui plaidait pour une fondation type `_page-title.scss` ; mais une règle
   globale sur `.field--name-body` aurait aussi attrapé le corps de texte des actualités,
   qui n'est pas un chapô. Le SDC `page-intro`, embarqué depuis le template de node, donne
   la même portée sans effet de bord.
2. **Alternatives rejetées** — (a) `repeat(7, 1fr)` avec quatre media queries : exact mais
   quatre valeurs à resynchroniser ; (b) reproduire les 40/65/188 de la maquette :
   désaccorde cette page des Actualités ; (c) styler `.node--type-brands .field--name-body`
   dans une fondation : une règle de page dans un fichier global.
3. **Le moins sûr** — le seuil de 132px. Il tient parce que la colonne vaut 1130 ; si cette
   colonne change, le nombre de colonnes bascule sans prévenir. La fourchette qui donne 7
   est écrite dans le commentaire du SCSS (124 → 144).

## « 9bis » — les 4 frames restants du Contact (mesuré le 2026-08-18)

Les 4 frames en attente ne sont pas ce que cette page annonçait : `438-9060` est la
variante **SAV**, `438-9465` la variante **question**, `438-9456` / `438-9457` les deux
**modales « carte grise »**. Aucun n'est un état de validation ou de confirmation.

**Variantes SAV et question : conformes.** Vérifié en pilotant le `select` « Votre demande
concerne » dans le navigateur et en relevant la visibilité réelle de chaque élément.
SAV → marque, modèle, motorisation, n° de châssis, document, message ; type de châssis
masqué. Question → message seul. C'est exactement ce que décrivent les maquettes.

**Bug trouvé et corrigé : le champ « Ajouter un document » n'existait pas dans le rendu.**
`#uri_scheme: private` sans `file_private_path` configuré → Drupal **retire l'élément
silencieusement**, sans log ni message. Le dossier `private/` existait déjà (gitignoré),
seul le réglage manquait. Ajouté dans `web/sites/default/settings.php` (gitignoré) :
`$settings['file_private_path'] = dirname(DRUPAL_ROOT) . '/private';`.
⚠️ **À reporter sur chaque environnement** — préprod et prod n'ont pas ce fichier.

**Le plafond de 5 Mo était déjà bon là où ça compte.** Le « Limité à 2 Mo » observé venait
du PHP **CLI** (Homebrew, `upload_max_filesize = 2M`) utilisé par `drush runserver` — le
vhost MAMP qui sert réellement le site est à 48M et affichait bien 5 Mo. Le piège est le
banc de test, pas le site : **une vérification faite via `runserver` ne dit pas ce que
voit un visiteur** dès qu'un réglage `php.ini` est en jeu. Le CLI a quand même été passé
à 5M (sauvegarde `php.ini.bak-20260818`) pour que les deux chemins concordent.

**Formulaire stylé et modales faites le 2026-08-18** — acté dans
[ADR-015](../../../.claude/decisions/015-habillage-des-formulaires.md).

- Fondation `src/scss/_forms.scss` : carte `#F5F5F5` radius 24 padding 40/60, grille
  3 colonnes gouttière 30, libellés Inter 16/28 acier, champs blancs bordure
  `grey-metal` radius 8 hauteur 50, chevron des selects en `mask`, message sur
  2 colonnes, case à cocher 20px, bouton rouge, mention déplacée en bas par `order`.
- SDC `help-modal` : déclencheur ⓘ au bout de la ligne du libellé, `<dialog>` natif,
  visuels annotés en WebP dans `images/help/`. Repli sans JS = la phrase d'aide.
- Config du webform réalignée : intro H3, deux groupes (`identite` et `demande`) dont
  les selects portent le libellé « Sélectionner », « Type de châssis » et « case D.2.1 »
  corrigés, libellé du champ document repris de la maquette.

Mesuré sur le rendu : colonnes à x = 215 / 562 / 908 sur 317 de large (maquette
216/561/906 sur 315), libellé 28px puis champ de 50px à +31, icône ⓘ collée au bord de
la colonne, modale 596×1019 avec le visuel à (48, 69) en 500×902 — la maquette dit
596×1021 et (48, 69) en 500×902.

**Reste ouvert :**

1. **La pièce jointe du SAV ne part pas dans l'e-mail interne.** Le handler
   `sav_interne` a `attachments: false`, alors que le plan F10 §4 dit « joint le
   document si présent ». Jamais visible avant, le champ ne s'affichant pas. Une ligne
   de config à basculer — pas fait, ça change un envoi sortant.
2. **Le bouton d'envoi épouse son texte** (~95px) là où la maquette dessine un cadre de
   171px. Choix de cohérence avec les autres boutons du site, déjà validés ainsi.
3. **La ligne « adresse + carte » au-dessus du formulaire n'est pas mise en page** : la
   maquette la veut sur deux colonnes (texte à gauche, carte à droite), le rendu
   l'empile pleine largeur avec une photo en guise de carte. Hors périmètre du
   formulaire.

## Reprise de la page « Transformer un véhicule en auto-école » (2026-08-18)

Trois écarts relevés par l'utilisatrice sur le node 52, tous corrigés.

**1. Le recadrage 12:5 n'existait nulle part.** Le héros sortait en 1440×**960** (le 3:2 de
la source) au lieu de 1440×600. Cause : `crop_crop` **n'est pas un recadrage automatique**,
c'est l'application d'une entité `crop` posée sur le couple (fichier, type de recadrage).
Sans cette entité il ne fait **rien** — l'image traverse le style et ne subit que le
`scale`. Or la base ne contenait **aucune** entité `crop_12_5` : les **17 blocs `image_full`
du site** étaient donc tous au ratio de leur source. Neuf entités `crop_16_9` et une
`crop_1_1` existaient, ce qui rendait le trou invisible ailleurs.

Corrigé en créant le recadrage manquant, **centré**, sur les 7 fichiers concernés. Le
centrage n'est pas un choix par défaut : il a été **mesuré**. En glissant la bande du héros
de la maquette (1440×614) sur la photo source (4096×2725) et en minimisant l'écart
quadratique en niveaux de gris, le centre de bande tombe à 1362 px — le centre exact de
l'image. ⚠️ La maquette dessine 1440×614, soit un ratio de 2,345 : le 12:5 (2,4) est bien
l'intention, le 614 est un arrondi de maquette.

**2. La photo du héros n'était pas celle de la maquette** : le node reprenait la bannière
de la home (véhicule ECF jaune) là où la maquette montre un habitacle vu de la place du
conducteur. Photo importée depuis Figma (média 29).

**3. Le bloc configurateur portait des reliquats du bac à sable** : image `demo-1-1.png`,
légende « BMW Série 1 équipée — démonstration » et notice PDF, aucun des trois n'étant dans
la maquette. Les textes et le bouton, eux, étaient bons. Photo de la maquette importée
(média 30), légende et fichier retirés.

**4. Les accordéons des deux pages transform reviennent au lorem de la maquette.** L'écart
signalé plus haut (« de vraies questions… non tranché ») est **tranché : on intègre à
l'identique de la maquette**. Les nodes 52 et 76 portent désormais les 4 questions lorem et
le bouton « Lien vers site » du premier panneau, comme la home et « Qui sommes-nous » qui
avaient été jugés conformes pour cette raison. Le bouton pointe la FAQ (node 62).

À surveiller, non traité : le bouton du premier panneau de l'accordéon du node 54 pointe
encore `https://example.com`.

## Reprise de la page « Télécommande VOR auto-école » (2026-08-18)

Quatre écarts relevés par l'utilisatrice sur le node 75 (maquette `390-11137`).

**1. Filets de l'argumentaire produit.** Le bloc `product_arguments` (que la maquette
dessine comme un triptyque) faisait 960 de large avec des textes collés aux filets.
Mesuré sur `392:11481` : bloc de **858**, filets à **280** et **577** du bord, et **16px**
de dégagement de chaque côté du filet — les colonnes extrêmes, elles, **affleurent** les
bords du bloc. Corrigé, et le rendu retombe à 280/577 au pixel près.

**2. Le recadrage 1:1 manquait aussi.** Même cause que le 12:5 de la veille : une seule
entité `crop_1_1` existait en base (`home-savoir-faire.png`). L'image de l'`image_text_50`
sortait donc en 3:2. Recadrage centré semé sur les 9 fichiers servis en 1:1 ; l'image fait
désormais 1440×1440.

**3. Les deux boutons de téléchargement** des caractéristiques n'étaient pas alimentés.
`field_file_notice` et `field_file_doc` existaient et le SDC savait déjà les rendre :
seuls les fichiers manquaient. Les PDF de démonstration ont été **copiés hors du bac à
sable** vers `public://documents/`, celui-ci ayant vocation à disparaître en fin de
chantier. Libellés « Notice technique » et « Dossier général », le format et le poids
étant calculés par le thème.

**4. Le `text_centered` manquant** (« Obtenir la télécommande VOR auto-école » + bouton
rouge) est créé et placé juste après les caractéristiques. Son titre s'était retrouvé sur
le bloc configurateur, qui reprend donc les siens (« Configurez votre véhicule et obtenez
votre tarif »), sans quoi la page aurait affiché deux fois le même titre.

Corrigé au passage : le média 30 créé la veille pour le bloc configurateur du node 52
faisait **doublon** avec le média 10 (même fichier, même empreinte MD5). Node 52 repointé
sur le 10, doublon supprimé.

**Les trois photos de la maquette ont été importées** (les trois emplacements affichaient
le même média 7, « Home — solutions : commandes »).

- La bannière (`390:11148`) et l'`image_text_50` (`392:11485`) sont **deux cadrages de la
  même photo** : un seul média (31, la télécommande sur la console centrale) et deux
  entités `crop`. C'est exactement ce que le modèle « un crop par couple (fichier, type) »
  permet — inutile de dupliquer le fichier.
- Centres verticaux **mesurés** par corrélation avec les bandes de la maquette : 714 px
  pour le 12:5, 783 px pour le 1:1, sur une source de 1571 de haut. Le 1:1 tombe donc
  quasiment au centre (786) mais **pas** le 12:5, qui cadre 72 px plus haut.
- Le visuel des caractéristiques (`393:11517`) est un **détouré** : ses marges
  transparentes ont été rognées (`imagecropauto`) puis rétablies aux proportions de la
  maquette (381×418) en transparence pure, le bloc l'affichant « sans crop ». Source
  ramenée à 1100 px de large : l'original faisait 3072×4096 pour un affichage à ~380.

⚠️ La source de la bannière ne fait que **1179 px de large** : les styles ne sur-échelonnant
pas (`upscale: false`), la plus grande dérivée s'arrête là au lieu des 2560 habituels.

**Les cinq visuels restants ont suivi** : les 3 diapos de `product_features` et les
2 cartes de `product_cross`, toutes en `crop_16_9` centré. Les deux cartes ont été
mesurées par corrélation (centre pile dans les deux cas) ; la 1re diapo n'avait pas de
question de cadrage, sa source étant déjà en 16:9 ; les 2e et 3e diapos sont **hors cadre
dans la maquette** — le carrousel n'en laisse dépasser qu'une lisière de 364 px, leur
cadrage n'y est donc pas observable et le centrage est un choix par défaut assumé.

Les cartes de renvoi montrent désormais le bon produit (double pédalier, rétroviseur)
là où les deux affichaient la même photo de la home.

Ne reste que `home-configurateur.png` sur la page — et c'est normal : le bloc
configurateur est le même composant dans toutes les maquettes.

## Audit des recadrages sur tout le site (2026-08-18)

Vérification demandée après les trois trous trouvés au fil des pages. Méthode : partir
des **modes d'affichage** pour déduire le ratio imposé à chaque emplacement, puis
contrôler l'existence du crop pour chaque fichier référencé — l'inverse (partir des
médias) ne dit pas à quels ratios ils sont rendus.

**Périmètre couvert** : 10 emplacements à ratio imposé (`image_full` 12:5 ;
`grid_element`, `history_element`, `jumbo_home_element`, `news`, `product_cross_element`,
`product_image_element`, `product_video_element`, `video_centered` en 16:9 ;
`image_text_50` en 1:1) et 6 emplacements « sans crop » assumés (`brand`, `contact`,
`image_centered`, `image_text_100`, `product_characteristics`). Vérifié qu'**aucun champ
n'est rendu à deux ratios différents** selon le mode d'affichage — ce qui aurait créé un
angle mort dans l'audit.

**Résultat** : 45 couples (fichier, ratio), **1 seul recadrage manquant** —
`home-savoir-faire.png`, recadré en 1:1 pour la home mais pas en 16:9 alors que la frise
« Notre histoire » le rend à ce ratio. Créé, centré (la maquette `corporate` ne pose que
des rectangles gris).

**Contrôle de bout en bout** : les 29 pages publiques ont été récupérées en anonyme et
les dimensions de chaque `<img>` comparées au ratio attendu de son style — **31 images à
ratio imposé, 0 écart**. Les 1,776 relevés au lieu de 1,778 sont l'arrondi entier sur des
sources à largeur impaire, pas un défaut de cadrage.

**Conformité au PRD (§7, décision #11)** : ratios 1:1 / 16:9 / 12:5 + sans-crop ✅ ;
44 styles dimensionnés convertissant en WebP ✅ ; 4 responsive styles mappés sur les
**6 breakpoints × 2 multiplicateurs** ✅. Les 4 styles `*_fallback` restent au format
d'origine — c'est **voulu et documenté** ([ADR-004](../../../.claude/decisions/004-pipeline-images.md)) :
le rendu produit un `<picture>` dont les `<source>` sont en WebP et l'`<img src>` sert de
repli aux navigateurs qui ne le gèrent pas.

### Le point ouvert est tranché : recadrage obligatoire et manuel

L'utilisatrice a tranché le 2026-08-18 : « quand j'ai demandé un crop avec un ratio
précis, il est obligatoire et doit être effectué manuellement par l'utilisateur à l'import
de l'image avant d'enregistrer son contenu ». La piste d'un recadrage **automatique** est
donc écartée — le cadrage est une décision éditoriale.

**C'était déjà implémenté** : le formulaire média liste les trois types dans
`crop_types_required`, et `ImageCrop::cropRequired()` bloque l'enregistrement tant qu'un
recadrage requis n'est pas appliqué. Aucun éditeur ne pouvait contourner la règle.

⚠️ **Le seul contournement est la création programmatique** : l'API entité ne valide pas
les formulaires. **Tous** les trous constatés viennent de là — scripts de seed et imports
depuis Figma, les miens compris.

État au regard de la règle, mesuré :

- **Aucun** recadrage manquant parmi ceux qu'un emplacement consomme réellement → rien de
  visible n'est en défaut aujourd'hui.
- **67** recadrages que le formulaire exigerait à l'import mais qu'aucun emplacement ne
  consomme : les 12 logos de marques (36), le visuel du configurateur, les deux logos de
  certification, et les ratios inutilisés des photos produit. Sans impact visuel.
- **35 recadrages existants** (11 en 1:1, 16 en 16:9, 8 en 12:5), dont la plupart **posés
  par script, centrés** — une valeur machine, pas la décision éditoriale que la règle
  demande. Seules exceptions mesurées sur la maquette : la console VOR (12:5 à y=714 et
  1:1 à y=783), l'habitacle transform, et les deux cartes de renvoi (mesurées centrées).
  **Ces cadrages méritent une relecture en back-office.**

Consigné dans [ADR-004](../../../.claude/decisions/004-pipeline-images.md) et dans les
conventions (CLAUDE.md) : ne plus fabriquer de recadrage par script, signaler pour un
passage éditorial.

## ⚠️ « Conforme » ne voulait pas dire « intégrée » — reprise de la liste d'actualités

Constat de l'utilisatrice le 2026-08-18 : la page `/actualites` était « n'importe quoi »,
et la plupart des 13 pages « intégrées n'importe comment ». C'est fondé, et la cause est
identifiable : les vérifications précédentes portaient sur le **contenu** (les bons blocs,
les bons textes, un seul `<h1>`, les bons alias) et **jamais sur la mise en page**. Le SCSS
de `news-teaser` le disait lui-même : « Structure seulement. L'intégration fine (mesures
de maquette) reste à faire. » La ligne du suivi disait pourtant « conforme ».

**Deux manques de fondation, qui touchent bien plus que cette page :**

1. **Le bloc titre de page n'avait aucun CSS.** Les maquettes centrent le titre — vérifié
   sur `438-10209`, `438-10665`, `436-8300`, `436-8578`, `433-7637` : il est **toujours**
   centré (centre à 720 dans un cadre de 1440), à 49 sous le filet du header, dans une
   colonne de 900. Le site le rendait collé à gauche, sans gouttière. Corrigé en fondation
   (`src/scss/_page-title.scss`) : **toutes** les pages à titre de bloc en bénéficient.
2. **La Vue `all_news` n'avait pas d'enveloppe SDC.** D'où des lignes à `x=0`, sans
   colonne de contenu ni rythme vertical. Corrigé avec le SDC `news-list` embarqué par
   `views-view-unformatted--all-news.html.twig`, comme `brands` et `faq`.

**Mesures reprises de la maquette et vérifiées sur le rendu** (relevé au navigateur, pas à
l'œil) : colonne 1130, ligne 1130×183, visuel **325×183** (16:9, rayon 16), écart visuel →
texte **60**, colonne de texte 745, pas entre lignes **213** (= 183 + 30), lien **aligné en
bas** de la ligne. Tout tombe juste au pixel.

**Respiration verticale — arbitrage de l'utilisatrice** : plutôt que les 113 / 103 / 99 de
la maquette (titre → liste, liste → pagination, pagination → footer), un **rythme unique**
égal à l'écart header → titre. D'où le token **`--dm-space-page` (49px)**, consommé par
`.block-page-title-block` (padding-top), `.news-list` (padding-block) et `.pager`
(padding-bottom) : les trois bougent ensemble, il n'y a plus de valeur à resynchroniser à
la main.

**Pagination** : ses libellés portaient les chevrons en caractères (« Suivant › ») là où
la maquette les veut en icône. Corrigé, et stylé en fondation (`_pager.scss`) : page
courante en blanc sur pastille acier, numéros en gras acier, « Précédent »/« Suivant » en
gris encadrés de chevrons.

⚠️ **Erreur commise et corrigée** : j'avais fait passer la Vue de 10 à **6 par page** parce
que la maquette dessine 6 lignes. C'est une confusion entre **vérité visuelle** (la
maquette) et **besoin fonctionnel** (les specs) : le PRD F8 écrit noir sur blanc
« pagination **10 par page** ». Remis à 10. Le nombre de lignes dessinées dans un cadre
Figma ne fait pas spécification.

**Jeu de test** : 32 actualités publiées (26 créées le 2026-08-18), soit 4 pages à 10.
Dates échelonnées d'une actualité tous les 3 jours en remontant — `node--news.html.twig`
affichant `node.changed`, sans cela la liste n'aurait aucun ordre lisible. Les visuels
réutilisent les 3 médias d'actualité existants, **déjà recadrés** : aucun média créé, donc
aucun recadrage fabriqué par script (cf. règle du recadrage manuel).

⚠️ **À retenir pour les 12 autres pages** : ne pas conclure « conforme » sur la seule
vérification du contenu. Une page n'est intégrée que si ses **mesures** ont été relevées
sur la maquette et **comparées au rendu**.

## Divergence titre (à arbitrer)

Constat mesuré sur le rendu anonyme :

- **La home n'a aucun `<h1>`** — PRD écart #2, qui renvoie explicitement l'arbitrage à « l'intégration des maquettes ».
- **Les pages transform et produit ont deux titres** : un `<h1>` issu de `field_title` (ADR-011) **plus** un `<h2>` issu du bloc héros. Sur la VOR c'est deux fois le même texte (« Télécommande VOR Auto-école » / « Télécommande VOR auto-école »). Les maquettes n'affichent **qu'un** titre.
- PRD écart #3 : ce `<h1>` est rendu **après** le contenu, dans un `<aside>` (région `sidebar_first` du starterkit), à reprendre avec le shell de page en F2.

Options soumises à l'utilisatrice : masquer le bloc titre de page / retirer le titre des blocs héros / faire rendre le titre du héros en `<h1>` et masquer le bloc titre.

## Le lorem est celui des maquettes — ne pas le « corriger »

Vérifié sur le symbole `317:6612` (l'accordéon FAQ) : la maquette contient **les 4 mêmes
questions en lorem, mot pour mot**, que le site. Idem pour la description de
l'`image_text_50` de la home (`303:6045`). Ces zones sont donc **conformes** : le lorem
est un placeholder de la maquette, pas une dette d'intégration.

~~⚠️ Conséquence : sur les pages transform (nodes 52 et 76), les questions de FAQ ont été
remplacées par de vraies questions du référentiel.~~ **Corrigé le 2026-08-18** : on intègre
à l'identique de la maquette, lorem compris. La règle ne souffre pas d'exception — ne pas
reproposer d'« améliorer » un placeholder de maquette.

## Ce que la reconstruction du node 54 a appris

- ⚠️ **Ne pas porter titre et chapô par un paragraphe.** J'avais créé un `text_centered`
  pour les deux et ajouté `corporate` aux bundles à titre-héros : **erreur, corrigée**.
  La règle du projet : tout type indexable **porte** `title` + `body` pour le SEO, mais ce
  qu'il en **affiche** lui est propre. `transform` et `product` n'affichent ni l'un ni
  l'autre (tout vient des paragraphes, contenu libre) ; les autres affichent `title` seul
  ou `title` + `body`. `corporate` affiche les deux — son `body` est le chapô. Les bundles
  à titre-héros restent **3** : homepage, transform, product.
- Le titre de la maquette est « Qui sommes-nous **?** » : le point d'interrogation ne
  change pas l'alias (`/qui-sommes-nous`), Pathauto le retire.
- Mapping utile : `triptych_element` = `field_text_top` / `field_title` (le grand chiffre)
  / `field_text_bottom` → colle exactement aux cartes de chiffres (« Plus de » / « 2500 »
  / « véhicules équipés par an »).
- Les visuels des maquettes `corporate` sont des **rectangles gris** : aucun média n'est
  spécifié, ceux posés sont des placeholders.
- ~~**Lien en attente**~~ : le bouton de la section « La qualité certifiée » (`436:8978`)
  pointe désormais vers la page #13 (node 79). ⚠️ Son libellé est **« En savoir plus »** :
  le nom de calque Figma disait « Demander un devis », mais c'est le nom **du composant**,
  pas le texte de l'instance. Sur une `<instance>`, lire le texte, jamais le nom de calque.
- La maquette porte une consigne éditoriale à ne pas prendre pour du contenu :
  « Mettre les différents logo Drive Matic avec leur date. » (`436:8975`).

## Règle de titre et d'URL (validée le 2026-08-18)

Le `title` prend le **libellé exact de la maquette**, accroche comprise. Sur un type à
**exemplaire unique**, on **supprime son motif Pathauto** et on pose l'alias **en dur** sur le
node, pour garder une URL courte. Fait : `configurator` → `/configurer`, `faq` → `/faq`,
`brands` → `/marques-partenaires`. Restent à traiter sur ce schéma : `documents`, `all_news`,
`contact`, `partner`. Pas d'alias manuel sur un type multi-instances (`corporate`, `product`,
`transform`, `news`, `legals`) : leur motif reste.

Les coquilles de maquette sont corrigées, pas reproduites : le calque disait « Les marques
partenaire » au singulier, le frame « Les marques partenaires ». Retenu : le pluriel.

⚠️ **Piège** : poser un alias à la main **ne supprime pas l'ancien**. Les deux répondent en 200
et le node vit à deux URL. Supprimer l'entrée `path_alias` périmée et créer le 301 à la main.

## Fragments : toujours prévoir leur template de rendu

`node.html.twig` rend `{{ label }}` dès que `view_mode != "full"`, **et en lien vers la page
canonique**. Sur un fragment (`rabbit_hole` en `access_denied`) cela donne un titre parasite
pointant vers un 403 — vu sur `document`, où le titre est un libellé d'administration jamais
public (PRD F6). Corrigé par `node--document.html.twig`, qui ne rend que `{{ content }}`.
Vérifié : `question` et `brand` ne fuitaient pas. **Tout nouveau fragment rendu dans une Vue
ou un champ référence a besoin de son propre template**, sans quoi son libellé fuite.

Contrôle utile : `href="/node/<id>"` dans `<main>` doit toujours renvoyer zéro résultat.

## Documentations : restructuration du 2026-08-18

Le type de node `document` et ses 4 nodes sont **supprimés**. Un document n'est plus une
entité : c'est une **valeur d'un champ Fichier à itération illimitée**, saisie dans l'ordre
d'affichage. Les deux champs `field_documents_school` / `field_documents_pmr` ont donc été
**supprimés et recréés** (le type d'un champ ne se change pas), et les titres de section
« Auto-écoles » / « PMR » sont **en dur** dans `node--documents.html.twig`.

⚠️ Deux homonymes à ne pas confondre : le **media type `document`** est conservé (bundle de
la bibliothèque de médias), et `field.storage.node.field_file` aussi (encore utilisé par
`question`). Seul le **type de node** a disparu.

Après suppression d'un champ, `field_purge_batch()` est nécessaire avant de recréer un champ
du même nom — sinon la création échoue sur le nom encore réservé.

## À surveiller : la date d'une actualité vient de `changed`

`node--news.html.twig` affiche `node.changed` — décision du modèle éditorial, pas de champ
date dédié. Conséquence : **modifier une vieille actualité la redate**. Vu en direct, le node 17
est passé au 18/08/2026 en le sauvegardant. À rediscuter si l'ordre chronologique compte.

## Piège : les traductions de config surchargent la config de base

`config/sync/language/fr/*.yml` (193 fichiers) surcharge la valeur de base. Modifier
`webform.settings` n'a **rien changé** au rendu : la surcharge `fr` gagnait.
`\Drupal::config()` renvoie la valeur résolue, `getEditable()` la valeur brute — comparer les
deux pour diagnostiquer. Corriger via
`\Drupal::languageManager()->getLanguageConfigOverride('fr', <nom>)`.

Le site étant monolingue français, **c'est toujours la surcharge `fr` qui est servie**.

## Autre constat à traiter

Les libellés d'accessibilité du thème sortent **en anglais** : `Main navigation`, `User account menu`
(`<h2>` masqués). Le site est en français depuis le 2026-08-17. À reprendre, probablement avec F2.

## Self-review — détail d'une actualité (2026-08-19)

1. **Décision la plus difficile** : où poser la largeur de colonne. La maquette dit 900, mais
   `text_left_aligned` tient 960 et il est partagé avec quatre autres pages. Trois issues
   possibles (aligner le bloc sur 900 et rejouer les autres pages / laisser deux colonnes
   cohabiter sur la page / aligner la page sur le bloc) ; l'utilisatrice a tranché pour la
   troisième. Reste la question technique : le titre de page est un **frère** du contenu, la
   valeur ne pouvait donc pas vivre sur le SDC — d'où un token retuné sur le `<body>`.
2. **Alternatives rejetées** : (a) un modificateur `--news` sur les SDC de blocs — deux
   largeurs pour un même bloc selon le contexte, plus dur à suivre qu'un token de gabarit ;
   (b) appliquer la colonne à la racine du SDC plutôt qu'à un `__lede` — les paragraphes
   auraient subi une **seconde** gouttière et leur texte serait tombé à 880 ; (c) créer un
   style d'image « sans crop » — inutile, `dm_free` / view mode `free` existaient déjà.
3. **Point de moindre confiance** : le `<figcaption>` reçoit une chaîne (prop) alors que le
   `body` reste un render array — deux régimes dans le même composant. C'est la convention du
   dépôt (identique à `video_centered`) et les métadonnées de cache du champ légende sont
   perdues, mais un champ `string` n'en porte pas d'utile. Second point : `dm_free` sert des
   dérivés surdimensionnés dans une colonne de 960 (dette `sizes`, ADR-004) — non traité.

## Page Contact intégrée (2026-08-20) — maquettes 433-7637/438-9060/438-9465 + modales 438-9456/438-9457

Les 3 frames sont les 3 états conditionnels d'un même formulaire (devis/SAV/question),
déjà couverts par les `#states` de `webform.webform.contact.yml` ; les 2 modales
(numéro/type de châssis) étaient déjà livrées (`help-modal`, ADR-015). Le seul écart
réel était la ligne « adresse + visuel » au-dessus du formulaire (écart #3 de
`docs/plans/webform-contact.md`), jamais mise en page. Fait : SDC `contact-intro`
(2 colonnes 510/510, gap 110px repris d'`image-text-50`, même plafond 1130px que la
carte du formulaire) + `node--contact.html.twig`.

À la demande de l'utilisatrice, le visuel impose désormais un crop 16:9 — `contact`
sort du circuit « médiathèque sans ratio » (ADR-018 addendum). Le fichier semé
(capture de carte reprise de la maquette Figma) n'a pas de crop posé par script
([[crop-obligatoire-manuel]]) : **reste à recadrer manuellement en back-office**
(`/node/1/edit`, onglet Contenu, champ Visuel) avant publication.

### Self-review

1. **Décision la plus difficile** : confirmer avec l'utilisatrice avant de convertir
   le champ, puisque `contact` était explicitement listé comme cas où ADR-018 ne
   s'appliquait pas. Une fois confirmé, réutiliser la storage `field_photo` déjà
   créée pour `news` plutôt que d'en ouvrir une troisième — aucun nouveau mécanisme.
2. **Alternative rejetée** : réutiliser le SDC `image-text-50` (même silhouette
   visuelle) plutôt qu'un nouveau composant. Écarté pour rester cohérent avec la
   règle FAQ (composants dédiés par cycle de vie) : `image-text-50` porte un titre,
   un fond, un CTA et une bascule gauche/droite dont ce bloc n'a besoin d'aucun —
   le réutiliser aurait tiré des props mortes dans le node.
3. **Point de moindre confiance** : le gap titre → bloc et bloc → formulaire (32px,
   `--dm-space-block`) ne reproduit pas le pixel exact de la maquette (97px mesuré) —
   choix délibéré de suivre le rythme unique déjà en place ailleurs sur le site
   plutôt que la valeur de cette maquette précise, mais pas mesuré contre une
   maquette qui l'exigerait au pixel.
