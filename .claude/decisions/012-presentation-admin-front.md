# ADR-012 : Presentation de l'administration sur le front

## Statut

Accepte

## Date

2026-08-17

## Contexte

Connectee en administration, la navigation sur le **front** ne ressemblait pas
aux autres projets Passerelle. Deux ecarts :

1. La barre d'administration sortait **claire, a icones** (rendu de Gin), la ou
   les autres projets affichent la barre **noire du cœur** — « Gerer /
   Raccourcis / admin » — surmontant le menu horizontal d'Admin Toolbar.
2. Les onglets locaux (Voir / Modifier / Supprimer / Revisions) sortaient en
   **liste a puces brute**, en pleine largeur entre le fil d'Ariane et le
   contenu : le theme front n'a aucune regle pour `.tabs`.

Diagnostic pose en comparant avec le projet **CFPA** (`~/Documents/WWW/CFPA-Site`),
qui a la presentation attendue.

## Options considerees

### Option A : reproduire le rendu par du CSS de theme

- Avantages : aucun changement de modules.
- Inconvenients : on habillerait la barre de Gin pour la faire ressembler a
  celle du cœur — beaucoup de CSS a maintenir contre un composant qui evolue.
  **Approche tentee puis abandonnee** : elle traitait le symptome.

### Option B : aligner les modules sur le projet de reference

- Avantages : la cause. La comparaison est sans ambiguite — **la seule
  difference est le module `gin_toolbar`**, actif ici, absent la-bas. Tout le
  reste etait identique : meme version de Gin (5.0.15), meme theme
  d'administration, meme `gin.settings` y compris `classic_toolbar: horizontal`.
- Inconvenients : la barre change aussi **sur les pages d'administration**, qui
  repassent au style du cœur au lieu du style Gin.

## Decision

**Option B pour la barre, plus un portage minimal pour les onglets.**

- **`gin_toolbar` desinstalle.** Le front (et l'administration) servent la barre
  du cœur avec le menu d'Admin Toolbar.
- **Les onglets locaux** reprennent la regle du theme CFPA
  (`libraries/navigation/menu-local-tasks/menu-local-tasks.css`) : carte `fixed`
  en haut a droite, fond gris, qui suit le defilement. Adaptee aux tokens du
  projet, avec deux ecarts : le decalage haut suit
  `--drupal-displace-offset-top` (variable posee par `Drupal.displace`) plutot
  qu'une variable maison, et une declaration `li { display: block }` est
  necessaire car le cœur pose `display: inline-block` sur les items.
- **Le crayon n'est pas dessine** : c'est celui des liens contextuels du cœur
  (module `contextual`), qui apparait au survol de la region. Il ne figure pas
  davantage dans le CSS de CFPA.

## Consequences

**Plus facile**

- La presentation d'administration est celle des autres projets de l'agence :
  rien a reapprendre en passant d'un site a l'autre.
- Le diagnostic est trace : la prochaine divergence de barre se cherche d'abord
  du cote des modules, pas du CSS.

**Plus difficile / a surveiller**

- ⚠️ **Le paquet `drupal/gin_toolbar` reste sur disque** : `drupal/gin` 5.0.15
  l'exige en dependance ferme, `composer remove` echoue. Seule la dependance
  racine redondante a ete retiree de `composer.json`. **Rien n'empeche donc de
  reactiver le module** — un `drush pm:enable` ou une recette suffirait, et la
  barre du front changerait sans autre signe. CFPA vit avec la meme contrainte.
- La barre des **pages d'administration** perd le style Gin. C'est le
  fonctionnement des autres projets ; le theme d'administration Gin, lui, reste
  en place pour tout le reste (formulaires, listes, mise en page).
- **Deroge a la decision #10 du PRD** (« aucun CSS hors SDC, hormis les
  fondations globales ») : la regle des onglets vit dans
  `src/scss/_local-tasks.scss`. Elle habille les onglets d'administration du
  cœur, qui ne sont pas un composant du site ; en faire un SDC imposerait une
  surcharge de template rien que pour l'embarquer. L'exception est commentee
  dans `style.scss`.

**Fichiers impactes**

- `config/sync/core.extension.yml`, `composer.json`, `composer.lock`.
- `src/scss/_local-tasks.scss` + `src/scss/style.scss` (+ `css/style.css` genere).
