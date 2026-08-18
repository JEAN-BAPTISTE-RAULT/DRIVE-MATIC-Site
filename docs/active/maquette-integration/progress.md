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
| 9 | Contact | 1 | `433-7637`, `438-9060`, `438-9465`, `438-9456`, `438-9457` | à vérifier (5 frames : formulaire + états) |
| 10 | Devenir partenaire | 2 | `438-9838` | à vérifier |
| 11 | **Nos ateliers** | — | `436-2486` | **à créer** (`corporate`) |
| 12 | **Recherches et développement** | — | `436-8300` | **à créer** (`corporate`) — PRD F9 l'appelle « Recherche & développement » |
| 13 | **Savoir-faire et certifications** | — | `436-8578` | **à créer** (`corporate`) |

Déjà conformes (vérifiées le 2026-08-18) : node 52 (`363-9316`), node 76 (`389-10805`), node 75 (`390-11137`).
Les 6 produits sans maquette (53, 70-74) portent un `image_full` seul, conformément à la consigne.

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

⚠️ Conséquence : sur les pages transform (nodes 52 et 76), les questions de FAQ ont été
remplacées par de **vraies** questions du référentiel — c'est allé **au-delà** de la
maquette. Signalé à l'utilisatrice, non tranché.

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
- **Lien en attente** : le bouton de la section « La qualité certifiée » (`436:8978`) doit
  pointer vers la page « Savoir-faire et certifications » (#13), qui n'existe pas encore.
  `field_link` est resté vide — à câbler après création.
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

## Autre constat à traiter

Les libellés d'accessibilité du thème sortent **en anglais** : `Main navigation`, `User account menu`
(`<h2>` masqués). Le site est en français depuis le 2026-08-17. À reprendre, probablement avec F2.
