# ADR-019 : `legals` passe de paragraphes a body + metatags, et s'etend a 4 pages

## Statut
Accepte

## Date
2026-08-20

## Contexte
`legals` etait defini par l'[ADR-002](002-types-de-contenu.md) comme l'unique type public
sans `body` ni `field_meta_tags` : un titre obligatoire et des paragraphes
`text_left_aligned` uniquement, indexable mais sans description (ADR-002 point 43,
[ADR-010](010-metatags.md) ligne 14). Un seul node existait (« Mentions legales », renomme
depuis en « Conditions generales de vente », node 55).

`docs/PRD.md` (ligne 87, footer) exige **4 pages legales distinctes** : CGV, CGU, mentions
legales, donnees personnelles. Composer 4 pages de texte juridique long en blocs
`text_left_aligned` empile des paragraphes homogenes sans reel besoin de mise en page —
l'exception `legals` (pas de body, pas de metatags) n'avait plus de justification une fois
le nombre de pages multiplie par 4 : chacune a besoin d'une vraie meta description, comme
tout autre contenu indexable.

## Options considerees

### Option A : garder les paragraphes, dupliquer le node 4 fois
- Avantages : aucun changement de modele, deja en place.
- Inconvenients : 4 pages de texte juridique long composees en sections repetitives
  (`text_left_aligned`) sans benefice editorial ; aucune meta description (l'exception
  ADR-010 devient plus visible avec 4 pages indexees sans description) ; l'exception
  `legals` perd sa justification (elle datait d'un unique node, jamais pense pour 4 pages
  distinctes et indexables).

### Option B : `legals` rejoint la convention standard (`title` + `body` + `field_meta_tags`)
- Avantages : coherent avec tous les autres types publics (ADR-014 conventions, `body` alimente
  la meta description) ; le WYSIWYG `body` convient mieux a du texte juridique continu qu'un
  empilement de blocs ; simplifie le formulaire (un seul champ de texte au lieu d'un
  paragraphe repete N fois).
- Inconvenients : migration du contenu existant (CGV, node 55) hors du champ `field_paragraphs`
  avant sa suppression ; perte de la possibilite de composer visuellement une page legale
  (jugee non necessaire pour ce type de contenu).

## Decision
**Option B**, choisie par l'utilisatrice. `legals` porte desormais `title` + `body`
(obligatoire) + `field_meta_tags` (surcharge, comme tout node public), sans plus aucun
paragraphe. Colonne de contenu alignee sur 960px (comme `news`/`corporate`,
[ADR-016](016-colonne-de-contenu.md)).

Migration : le contenu des 15 sections `text_left_aligned` du node 55 (CGV) a ete transcrit
dans son `body` **avant** la suppression de `field.field.node.legals.field_paragraphs` —
verifie sans perte residuelle en base (aucune ligne `bundle = 'legals'` restante dans
`node__field_paragraphs` / `node_revision__field_paragraphs`). Les 3 pages manquantes du
footer (CGU, mentions legales, donnees personnelles) ont ete creees comme nodes `legals`
distincts, `body` **vide** — le contenu juridique reste a rediger par l'editrice, conforme a
la regle du projet (le contenu editorial ne se fabrique pas par script).

## Consequences
- **Amende [ADR-002](002-types-de-contenu.md)** (point 43 : « `legals` : indexable, sans
  metatags ») et **[ADR-010](010-metatags.md)** (ligne 14 : « sauf `legals` ») : ces deux
  ADR restent valides pour le reste de leur perimetre, seule l'exception `legals` est levee.
  `docs/content-model.md` (lignes 55, 92, 99, 178) est signale perime en tete de fichier
  plutot que reecrit ligne a ligne (convention deja en place sur ce fichier).
- `field.storage.node.field_paragraphs` **reste en config** : la storage est partagee par
  `homepage`/`transform`/`product`/`corporate`/`news`, seule l'instance de bundle `legals`
  disparait.
- `metatag.metatag_defaults.node__legals` gagne `description: '[node:summary]'`, comme les
  autres bundles publics — plus besoin du defaut limite au seul titre.
- Fichiers impactes : `config/sync/core.entity_form_display.node.legals.default.yml`,
  `core.entity_view_display.node.legals.default.yml`, `metatag.metatag_defaults.node__legals.yml`,
  `field.field.node.legals.body.yml` + `field.field.node.legals.field_meta_tags.yml` (nouveaux),
  `field.field.node.legals.field_paragraphs.yml` (supprime), `src/scss/_tokens.scss`.
- Reste hors perimetre : la redaction du contenu juridique des 3 nouvelles pages, et le
  footer riche qui les liera (F2, a venir). Trace de verification :
  `docs/archive/legals-body-verification.md`.

## Addendum du 20/08 : la maquette CGV mesure 1130px, pas les 960px decides ci-dessus

En integrant le rendu du body d'apres la maquette CGV (469-11689), l'ecart de colonne
« alignee sur 960, comme `text_left_aligned` » (§ Decision) ne correspondait pas a la
mesure reelle : les sections de texte y font ~1130px de large (x=153 dans un cadre de
1440), le meme plafond que `news-list`/`brands-grid` et la carte des formulaires
(`calc(50vw - 565px)`), pas 960+2×gouttiere. Confirmation explicite obtenue avant d'agir
(meme reflexe que l'addendum ADR-018 du 20/08 : un classement deja arrete ne se corrige
pas silencieusement sur la seule foi d'une mesure).

**Decision** : le SDC `legal-text` (nouveau, `node--legals.html.twig`) suit la maquette —
plafond 1130 en litteral, comme `news-list`/`brands-grid` — plutot que de consommer
`--dm-content-column`. Le retune `body.page-node-type-legals { --dm-content-column:
960px; }` pose par la decision initiale **reste en config, mais n'est desormais consomme
par rien** : signale en commentaire dans `_tokens.scss` plutot que supprime, la mise a
jour de cette section de l'ADR valant decision explicite de le laisser en l'etat pour
l'instant (aucun autre composant `legals` n'existe qui aurait besoin du token).

**Corrige au meme moment** : le contenu du node 55 (CGV), migre par cette meme ADR,
avait perdu ses balises `<p>` pendant la transcription (texte brut + `<h2>` uniquement,
que le navigateur aplatit en fusionnant les phrases distinctes). Reinsere par script
verifie (round-trip automatique avant enregistrement, aucun mot modifie) — 15 sections,
38 paragraphes, frontieres reprises de `get_design_context` sur la maquette.

**Fichiers impactes** : `web/themes/custom/drive_matic/components/legal-text/` (nouveau),
`web/themes/custom/drive_matic/templates/content/node--legals.html.twig` (nouveau),
`src/scss/_tokens.scss` (commentaire de signalement, pas de suppression), contenu du
node 55 (base, non versionne).
