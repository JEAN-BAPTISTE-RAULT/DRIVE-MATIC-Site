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
| 2 | Qui sommes-nous | 54 | `433-9747` | ✅ **reconstruite le 2026-08-18** — 6 blocs conformes |
| 3 | **CGV** (type `legals`) | 55 | `469-11689` | ✅ **intégrée** — 15 sections `text_left_aligned`. Le frame s'appelle « Conditions générales de vente » : le type `legals` (« Page :: Mentions légales ») porte les CGV. Titre et alias changés, 301 auto depuis /mentions-legales |
| 4 | **FAQ** | 62 | `396-11620` | ✅ **intégrée** — titre « FAQ : Nous répondons à vos questions », motif Pathauto **supprimé**, alias en dur `/faq`, 301 depuis /questions-frequentes. Filtres Général/Auto-école/PMR rendus |
| 5 | Documentations | 67 | `398-12119` | ✅ **restructurée** — type de node `document` supprimé, les 2 champs passés en **Fichier illimité**, titres de section en dur dans le Twig |
| 6 | Les marques partenaires | 68 | `433-7148` | ✅ **conforme** — titre repris de la maquette, motif Pathauto supprimé, alias `/marques-partenaires` en dur. 12 logos alphabétiques dans `brands-grid` |
| 7 | Actualités | 46 | `438-10209` | ✅ **conforme** — `body` masqué (aucun chapô dans la maquette), alias `/actualites` en dur. Pager configuré, ne s'affiche pas avec 6 items : normal |
| 8 | Une actualité | 17 | `438-10665` | ✅ **conforme** — l'écart demandé (`body` sous l'image, avant les blocs) était **déjà** la config : field_image(0), field_caption(1), body(2), field_paragraphs(3). Ajouté les 2 blocs manquants |
| 9 | Contact | 1 | `433-7637`, `438-9060`, `438-9465`, `438-9456`, `438-9457` ✅ | **formulaire stylé et modales faites** (cf. « 9bis » plus bas). Les 4 frames restants n'étaient pas des « états du formulaire » : deux sont les **variantes SAV et question**, deux les **modales « carte grise »**. Reste hors formulaire : la ligne adresse + carte au-dessus de la carte grise du formulaire |
| 10 | Devenir partenaire | 2 | `438-9838` | ✅ **conforme** — titre puis formulaire, `body` masqué (aucun chapô dans la maquette), mention « *Champs obligatoires » activée |
| 11 | Nos ateliers | **77** | `436-2486` | ✅ **créée** — titre, chapô (`body`), 2 `image_text_50` alternées. Alias `/nos-ateliers` |
| 12 | Recherches et développement | **78** | `436-8300` | ✅ **créée** — titre, chapô (`body`), 2 `image_text_50` alternées, puis un `text_centered` (« Innover aujourd’hui… »). Alias `/recherches-et-developpement`. Visuels = placeholders (la maquette ne pose que des rectangles gris). Le titre retenu est celui de la maquette, pas le « Recherche & développement » du PRD F9 |
| 13 | Savoir-faire et certifications | **79** | `436-8578` | ✅ **créée** — titre, chapô, 2 `image_text_50` **toutes deux image à gauche** (pas d'alternance ici). Logos UTAC et ISO 9001 exportés de la maquette (médias 27 et 28). Alias `/savoir-faire-et-certifications`. Le lien en attente du node 54 est câblé |

Déjà conformes (vérifiées le 2026-08-18) : node 52 (`363-9316`), node 76 (`389-10805`), node 75 (`390-11137`).
Les 6 produits sans maquette (53, 70-74) portent un `image_full` seul, conformément à la consigne.

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

**Reste à faire sur cette page — trois photos ne sont pas celles de la maquette**, toutes
remplacées par le média 7 (« Home — solutions : commandes ») : la bannière (`390:11148`
montre la télécommande VOR dans la console centrale), le visuel de l'`image_text_50`
(`392:11485`) et le visuel des caractéristiques (`393:11517`, un détouré du produit). Non
traité : l'utilisatrice n'a signalé que le **ratio** de l'image, pas la photo elle-même.

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
