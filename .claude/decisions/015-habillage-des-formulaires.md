# ADR-015 : Habillage des formulaires et modales d'aide

## Statut

Accepte

## Date

2026-08-18

## Contexte

L'integration des maquettes de la page Contact (`433-7637` devis, `438-9060`
SAV, `438-9465` question, `438-9456` / `438-9457` les modales « carte grise »)
demandait trois choses que les regles du projet ne couvraient pas telles quelles.

1. **Ou poser le CSS d'un formulaire ?** La regle est « tout composant front est
   un SDC, rien hors SDC sauf les fondations globales ». Or le markup d'un
   webform vient de l'API Form de Drupal et de Webform : il n'existe aucun
   dossier de composant ou le co-localiser, et l'habillage sert tous les
   formulaires du site (contact, devenir partenaire, configurateur a venir).

2. **Comment exprimer la grille de la maquette ?** Les champs sont des freres
   plats ; la maquette impose des ruptures de ligne precises (1 / 3 / 2 / 3 / 2
   colonnes) et un champ sur deux colonnes.

3. **Ou branche-t-on la modale d'aide ?** La maquette remplace l'infobulle `?`
   de Webform par une modale montrant un certificat d'immatriculation annote.
   L'icone doit se poser au bout de la ligne du libellé — exactement la ou
   Webform place son aide, c'est-a-dire **dans le `<label>`**.

## Decisions

### 1. `src/scss/_forms.scss` est une fondation, pas un SDC

Meme derogation assumee que `_local-tasks.scss` : le markup n'appartient pas a un
composant. Tout est scope a `.webform-submission-form` pour ne pas deborder sur
les formulaires d'administration.

Les mesures propres a la carte (padding 40/60, gouttiere de 30 entre champs) ne
correspondent a aucun des trois tokens de l'[ADR-013](013-espacement-et-unites.md)
— ce ne sont pas des ecarts de rythme entre blocs. Elles sont **nommees en
custom properties locales** (`--dm-form-card-padding-*`, `--dm-form-gap`) plutot
que semees en dur : la regle « pas de valeur d'espacement en dur » est respectee
dans son intention, sans detourner les tokens de leur role.

### 2. La mise en page est declaree par le formulaire, appliquee par le CSS

Les ruptures de ligne sont portees par `#wrapper_attributes` dans la
configuration du webform (`dm-form-row-start`, `dm-form-span-2`,
`dm-form-span-3`), et le CSS ne fait que traduire ces classes en
`grid-column`. La mise en page reste ainsi decrite **la ou les champs sont
definis**, et le CSS reste generique : aucun selecteur ne nomme un champ.

Corollaire technique : les regles de grille desktop sont **en fin de fichier**.
Elles ont la meme specificite que les regles de base ; c'est leur position qui
les fait gagner. Les remonter remet le formulaire sur une colonne.

### 3. La modale est un SDC, branche par un attribut d'aide

- SDC `help-modal` : un `<button>` ⓘ et un `<dialog>` natif. `showModal()`
  apporte le piege de focus, la fermeture par Échap, le retour du focus au
  declencheur et `::backdrop` — rien a reecrire.
- Le `<dialog>` est livre **dans un `<template>`**, parce que le composant
  s'insere dans un `<label>` ou un `<dialog>` n'a pas le droit de figurer, alors
  qu'un `<template>` si. Le JS le clone dans `<body>` et le cable.
- Amelioration progressive : le declencheur est masque tant que le JS n'a pas
  pose `is-ready` ; c'est alors la phrase d'aide de `#help` qui s'affiche. Le
  garde-fou couvre aussi le cas « JS present mais `<dialog>` non supporte ».
- **Le choix du visuel passe par un nom, pas par un chemin** :
  `#help_attributes: { data-dm-help-visual: chassis-numero }` en configuration,
  et `_drive_matic_help_visuals()` cote theme fait la correspondance nom →
  fichier + alternative textuelle. La configuration nomme un besoin, le theme
  decide de la presentation ; aucun chemin de theme ne se retrouve en config.

Contrainte subie : le hook de theme `webform_element_help` ne declare que
`help`, `help_title` et `attributes`. Il **n'y a pas** de variable `element`, donc
aucun moyen de remonter au champ d'origine — d'ou le passage par un attribut.

### 4. Les visuels d'aide sont des assets de theme, pas des medias

Derogation a « toute image passe par la media-library et un image style ». Ces
deux images sont des **illustrations d'interface** : identiques pour tout
visiteur, jamais editoriales, et indispensables au fonctionnement de l'aide. Les
mettre en media-library exposerait l'aide du formulaire a une suppression en
back-office. L'intention de la regle est respectee sur le fond : sortie **WebP**
(195 Ko chacune contre 1,4 Mo en PNG) et dimensions intrinseques declarees.

## Consequences

- Les formulaires du site heritent de cet habillage sans travail supplementaire.
  Le formulaire « Devenir partenaire » change donc d'apparence sans avoir ete
  touche : a verifier contre sa maquette (`438-9838`).
- L'etoile des champs requis devient un `*` en contenu genere, a la place du
  glyphe SVG du coeur. Le coeur l'avait choisi pour que les lecteurs d'ecran ne
  l'annoncent pas ; la maquette impose l'asterisque. Le caractere reste
  decoratif, `required` / `aria-required` portant l'information.
- La mention « *Champs obligatoires » est deplacee en bas par `order`. L'ordre de
  tabulation n'est pas affecte : ce texte n'est pas focusable.
- `_forms.scss` porte deux `stylelint-disable` cibles : les classes du coeur
  (`.field--type-webform`) et celles d'un SDC (`.help-modal__trigger`) ne passent
  pas le motif kebab-case quand elles ne sont pas ecrites en `&`-imbrique.

## Alternatives rejetees

- **Un SDC enveloppant le formulaire** : imposait de surcharger
  `webform.html.twig` pour l'embarquer, sans regler la question des widgets, qui
  restent hors composant.
- **Selecteurs CSS nommant les champs** (`.form-item-code-postal`) pour les
  ruptures de ligne : la mise en page aurait vecu loin de la definition des
  champs, et le moindre renommage l'aurait cassee en silence.
- **`background-image` sur le `select`** pour le chevron : aurait fige la couleur
  dans l'asset ou dans le CSS. Un `mask` sur un `::after` du conteneur garde le
  token.
- **Garder l'infobulle de Webform** avec l'image dans `#help` : une infobulle au
  survol de 900px de haut, et le contenu est de toute facon passe a `stripTags`.

## Addendum du 25/08 : le meme raisonnement s'etend au formulaire de connexion core

La page `/user/login` (F2, maquettes 472:12636 / 602:33089) a besoin du meme
habillage carte/champ/bouton, mais pour `user_login_form` — un formulaire
core, pas un webform. La regle d'origine (§1) scope explicitement `_forms.scss`
a `.webform-submission-form` / `.field--type-webform` : on ne l'a pas
elargie a `.user-login-form` en silence, ce qui aurait rendu ce scope annonce
faux.

**Decision** : nouvelle fondation dediee `src/scss/_user-login-form.scss`,
meme derogation « tout est SDC » que `forms`, meme reference aux tokens
(couleurs, `--dm-font-body`), mais **pas** la grille multi-colonnes ni la
carte propre de `.webform-submission-form` — la carte (fond, radius,
padding) est portee par le SDC `login-panel` qui enveloppe ce formulaire,
pas par le formulaire lui-meme. Le fichier reprend le meme `stylelint-disable`
cible pour les classes du coeur (`.user-login-form`), et le meme motif pour
une classe de SDC referencee depuis l'exterieur (`.login-panel__password-toggle`,
bouton insere par `#field_suffix` dans `drivematic_forms.module`) que
`.help-modal__trigger` ci-dessus.

Voir [ADR-024](024-mutualisation-formulaire-simple.md) pour la partie
content-model de la meme tache (mutualisation `partner` → `simple_form`).
