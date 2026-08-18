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
| 3 | Mentions légales | 55 | `469-11689` | à vérifier — 1 lien cassé, même bloc hors sujet |
| 4 | Questions fréquentes | 62 | `396-11620` | à vérifier (contenu par Vue, 0 paragraphe) |
| 5 | Documentations | 67 | `398-12119` | à vérifier (contenu par Vue) |
| 6 | Marques partenaires | 68 | `433-7148` | à vérifier (contenu par Vue) |
| 7 | Actualités (liste) | 46 | `438-10209` | à vérifier (contenu par Vue) |
| 8 | Une actualité | 17 | `438-10665` | à vérifier — **écart demandé** : ajouter le `body` sous l'image principale et **avant** le `text_left_aligned` |
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

- **`corporate` a rejoint les bundles à titre-héros** : sa maquette groupe titre + chapô
  en un `text_centered` centré, et `text_centered.field_title` est obligatoire — un chapô
  seul est donc impossible. `_drive_matic_hero_title_bundles()` et la visibilité du bloc
  titre listent désormais 4 bundles. Les 3 autres pages `corporate` (#11-13) devraient
  suivre le même schéma — à confirmer sur leur maquette.
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

## Autre constat à traiter

Les libellés d'accessibilité du thème sortent **en anglais** : `Main navigation`, `User account menu`
(`<h2>` masqués). Le site est en français depuis le 2026-08-17. À reprendre, probablement avec F2.
