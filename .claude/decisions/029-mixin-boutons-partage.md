# ADR-029 : Mixin Sass partage pour les boutons (couleurs + hauteur)

## Statut
Accepte

## Date
2026-08-26

## Contexte

Harmonisation demandee par l'utilisatrice (captures Figma a l'appui, node
243:5551 du fichier `ZmmVBSOWSsHVkok6EU2Ays`) :
1. Boutons fond gris -> fond rouge au survol
2. Boutons fond rouge -> fond bleu acier au survol
3. Boutons fond blanc/contour -> fond bleu acier au survol
4. Tous les boutons a la meme hauteur
5. Checkboxes/radios coches en bleu acier (pas rouge)

Un inventaire exhaustif a montre que le style de bouton (rouge/gris/contour)
etait duplique dans ~15 fichiers SCSS (composants SDC + fondations de
formulaire `src/scss/`), SANS composant ni fondation partagee. Consequence :
couleurs de survol divergentes d'un fichier a l'autre (anthracite vs
`color-mix` vs grey-metal selon le fichier, alors que la seule valeur exacte
est bleu acier), hovers manquants sur plusieurs boutons, et 3 hauteurs
differentes cohabitant. Une note deja presente dans le code
(`text-left-aligned.scss`) signalait ce risque : « Candidat a un style de
bouton partage (ADR) si le motif se repete ».

## Options considerees

### Option A : Fondation globale + classe utilitaire (`.dm-btn--red` en Twig)
- Avantages : moins de CSS ecrit, une seule source de verite explicite dans le
  markup.
- Inconvenients : deroge a la regle « tout composant front est un SDC » (le
  style n'est plus co-localise dans le dossier du composant) ; touche le Twig
  de chaque SDC en plus du SCSS.

### Option B : Mixin Sass partage (`@use` + `@include` sur le selecteur du composant)
- Avantages : le CSS reste co-localise dans chaque SDC/fondation (le
  selecteur `.mon-composant__actions a` reste local, seules les VALEURS sont
  centralisees) ; aucun Twig a toucher ; aucune nouvelle exception a
  documenter dans `style.scss`.
- Inconvenients : necessite un `--load-path` de build supplementaire pour que
  les composants SDC (compiles comme racine independante) puissent `@use` un
  fichier de `src/scss/`.

### Option C : Correctifs locaux, fichier par fichier, sans nouvelle fondation
- Avantages : diff le plus « chirurgical » au sens strict (aucun nouveau
  fichier).
- Inconvenients : la duplication de valeurs reste — le meme risque de derive
  se reproduira au prochain changement de couleur de survol.

## Decision

Option B. Choisie par l'utilisatrice (question posee explicitement en debut
de chantier). Le mixin vit dans `src/scss/_button-mixins.scss` (a cote de
`_tokens.scss`, meme role de primitive de design partagee) et expose :

- `dm-btn-red` / `dm-btn-grey` / `dm-btn-outline` : couleurs de fond/texte au
  repos + au survol, pour les 3 familles Figma (`Btn`, `BtnRouge`,
  `BtnContour`).
- `dm-btn-height($content: text|icon, $bordered: false)` : `padding-block` +
  `line-height: normal` pour une hauteur totale ~46px, quel que soit le
  contenu (texte seul ou icone 24px + texte) et la presence d'une bordure.

Pour rendre le mixin utilisable depuis `web/themes/custom/drive_matic/components/`
(compile comme racine Sass independante par `css:components`, distincte de
`src/scss/`), un `--load-path=src/scss` a ete ajoute aux scripts `css:components`
et `css:watch` de `package.json`.

**Trouvaille empirique en cours de chantier** : un bouton avec bordure
(`border: 1px solid`) mesure 2px de plus qu'un bouton sans bordure a padding
egal, meme sous `box-sizing: border-box` — ce dernier ne change l'effet de la
bordure sur la taille totale QUE lorsqu'une `height`/`width` explicite est
posee ; sur une boite en hauteur automatique (le cas de tous ces boutons), la
bordure s'ajoute toujours au padding + au contenu. D'ou le parametre
`$bordered` du mixin, qui retire 1px de chaque cote au lieu de forcer une
valeur unique fausse pour la moitie des boutons.

Composants restes **hors mixin**, corriges directement (valeur ponctuelle,
pas de restructuration) car leur hauteur ou leur logique etait deja
correcte/documentee et une migration vers le mixin aurait risque une
regression (ex. perte du traitement `:focus-visible` du CTA du header) :
`site-header__cta`, `site-header__account-trigger`, `login-panel__action-button`,
`text-left-aligned__actions`, `configurator-form__add` (icone en
`background-image` a couleur fixe, pas en `mask` — necessite un second asset
`plus-circle-white.svg` plutot qu'un `currentColor`).

Explicitement **hors perimetre** (ne correspond a aucune des 3 regles de
survol demandees) : boutons « pilule » FAQ/accordeon (design distinct,
radius 30px), `product-characteristics__download` (variante fond anthracite,
pas un bouton « fond blanc »), le composant Figma `BtnBlanc` (blanc -> rouge,
ne correspond a aucune des 3 regles demandees — probable incoherence du
fichier Figma).

## Consequences

- Toute future divergence de couleur de survol entre boutons rouge/gris/
  contour necessite de changer UNE valeur dans `_button-mixins.scss`, pas 15
  fichiers.
- Un nouveau composant avec un bouton rouge/gris/contour doit consommer le
  mixin (`@use 'button-mixins' as btn;` + `@include btn.dm-btn-*;`) plutot que
  redupliquer les valeurs.
- Le `--load-path=src/scss` est un changement de build partage par TOUS les
  composants : toute regle Sass ambigue entre `src/scss/` et
  `node_modules/` (collision de nom de partial) romprait silencieusement la
  resolution — aucune collision actuelle, a surveiller si un nouveau partial
  `src/scss/_<nom-existant-dans-node_modules>.scss` est cree.
- Checkbox/radio n'ont qu'UNE implementation dans tout le depot
  (`src/scss/_forms.scss`) : aucun mixin necessaire, correctif direct de la
  couleur `:checked` (rouge -> bleu acier).
