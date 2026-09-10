# ADR-054 : Suppression du fil d'Ariane (remplace ADR-023)

## Statut

Accepte

## Date

2026-09-10

## Contexte

Demande explicite de l'utilisatrice : retirer le fil d'Ariane de toutes les
pages du site, en mobile comme en desktop. Le fil (bloc `system_breadcrumb_block`,
markup Easy Breadcrumb, stylise par ADR-023 le 2026-08-21) n'avait de toute
facon jamais ete montre par aucune maquette Figma — ADR-023 le notait deja
lui-meme comme un ecart assume, pas une conformite a un design.

Retirer uniquement le bloc ne suffit pas : `.block-page-title-block` (bloc
titre de page du coeur) ne pose plus son propre `padding-block-start` depuis
ADR-023, precisement parce que le fil d'Ariane portait cet ecart a sa place
(son propre `padding-block`, moitie haute). Sans compensation, toute page
affichant le bloc titre se serait retrouvee avec un `<h1>` colle au filet du
header. Meme probleme sur les gabarits « hero » (`homepage`/`transform`/
`product`, cf. `_drive_matic_hero_title_bundles()`) : masquant le bloc titre,
ils reposaient entierement sur l'ecart du fil d'Ariane pour separer le header
de leur premier paragraphe.

## Options considerees (trois essais successifs, retour utilisatrice a chaque fois)

### Ecart de remplacement au-dessus du titre de page (`.block-page-title-block`)

1. **Reposer `--dm-space-page`, essai initial** (49px desktop) : coherent
   avec la mesure documentee dans `_page-title.scss` d'apres les maquettes
   d'origine (438-10209, 438-10665, 436-8300, 436-8578, 433-7637 — « 49px
   sous le filet du header »), qui datent d'avant le fil d'Ariane. Rejete :
   l'utilisatrice a lu ce report comme si le fil d'Ariane etait toujours la,
   juste invisible — « il faut supprimer l'espace dans lequel se trouvait le
   fil d'ariane ».
2. **N'en reposer aucun, deuxieme essai** : le titre suit directement le
   filet du header, ecart nul. Rejete a son tour : sur les templates
   `corporate` (titre texte plein, ex. `/qui-sommes-nous`), le titre se
   retrouvait colle au menu du header, sans respiration — « il faut ...
   retablir la variable de rythme vertical choisie pour le projet ».
3. **Reposer `--dm-space-page` (retenue, definitif)** : c'est exactement
   l'option 1, mais comprise cette fois comme la restauration du **rythme de
   page standard du projet** (CLAUDE.md, section « Gabarit de page — un seul
   ecart, `--dm-space-page` », qui liste explicitement « au-dessus du titre
   de page » parmi ses usages), pas comme un heritage du fil d'Ariane. La
   distinction est venue de l'utilisatrice elle-meme au 2e retour : ce n'est
   pas la meme valeur reintroduite pour la meme raison, c'est LE token de
   rythme vertical du projet, qui doit s'appliquer ici comme partout
   ailleurs sur le gabarit de page.

### Gabarits « hero » (`transform`/`product`, premier paragraphe sans bloc titre)

Le 2e essai ci-dessus a aussi retire l'ecart ajoute au premier tour sur ces
bundles. **Ecart laisse a zero, definitivement, pour ces bundles
uniquement** : leur premier paragraphe (`image-full`) est une banniere
plein-cadre, deja documentee comme volontairement sans `padding-block` avant
meme l'existence du fil d'Ariane (ADR-023, contexte) — visuellement, une
image pleine largeur qui touche le header n'a pas le meme besoin de
respiration qu'un titre texte. L'utilisatrice n'a signale que les templates
`corporate` (titre texte) comme trop serres, jamais `transform`/`product`.

## Decision

- Bloc `drive_matic_breadcrumbs` (`system_breadcrumb_block`) supprime de la
  configuration (`drush php:eval` + `drush cex`), sur toutes les pages, tous
  paliers.
- `src/scss/_breadcrumb.scss` supprime, `@use 'breadcrumb';` retire de
  `style.scss`. Template `templates/navigation/breadcrumb.html.twig` (override
  du coeur) supprime — devenu mort avec le bloc.
- `.block-page-title-block` porte `padding-block-start: var(--dm-space-page)`
  (`_page-title.scss`) : meme token que partout ailleurs sur le gabarit de
  page, pas une valeur dediee a cet emplacement.
- Aucune regle de remplacement pour les gabarits hero (`transform`/`product`) :
  leur premier paragraphe suit directement le header, sans ecart — design
  d'origine du composant, inchange.
- Module `easy_breadcrumb` et sa configuration (`easy_breadcrumb.settings.yml`
  + surcharge FR) **non desinstalles** : ils ne construisent qu'un service de
  breadcrumb (donnee, pas de rendu), inertes sans bloc pour les afficher.
  Les desinstaller n'aurait rien change visuellement et aurait touche a des
  fichiers de configuration hors perimetre de la demande.

## Consequences

- Le fil d'Ariane a disparu de toutes les pages, mobile et desktop.
- Le titre de page (bloc du coeur, `corporate`/actualite/FAQ/etc.) retrouve
  un ecart de 49px desktop / 13px mobile sous le header — verifie via
  `getBoundingClientRect()` sur `/qui-sommes-nous` aux deux paliers.
- Les gabarits hero (`transform`/`product`) restent a ecart nul sous le
  header — verifie sur `/transformer-un-vehicule-en-auto-ecole` (0px).
- `/user/login`, `/user/password` et `/configurer*` ne sont pas concernes :
  ils portent deja leur propre ecart independant
  (`.login-panel`/`.configurator-page { padding-block: var(--dm-space-page) }`,
  ADR-024/028), inchange par cette decision.
- ADR-023 est remplace par cette decision : son detail (alignement horizontal
  sur la boite du header, typographie) devient obsolete dans son ensemble,
  pas seulement l'ecart vertical qu'il documentait.

## Alternatives rejetees

Voir les options ci-dessus (essais 1 et 2, tous deux corriges par retour
utilisatrice explicite).
