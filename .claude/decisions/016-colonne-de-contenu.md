# ADR-016 : Colonne de contenu — un token retunable par gabarit

## Statut

Accepte

## Date

2026-08-19

## Contexte

L'[ADR-013](013-espacement-et-unites.md) a pose la regle horizontale — la
gouttiere est un `padding-inline`, le plafond vaut « contenu + 2 gouttieres » —
mais n'a jamais defini **ce que vaut « contenu »**. Chaque composant a donc
inscrit sa propre largeur en dur : 900 pour le bloc titre de page, le chapo,
`image_centered`, `video_centered` et `faq-list` ; **960** pour
`text_left_aligned` ; 1130 pour `news-list` et `brands-grid`.

Ces valeurs n'entraient pas en conflit tant qu'un gabarit n'en melangeait pas
deux. L'integration de la page de detail d'une actualite (maquette `438-10665`)
a fait sortir le probleme : la maquette pose **tout** sur 900 — titre, date,
visuel, legende, et les deux blocs qui composent la page — alors que
`text_left_aligned` en tenait 960 depuis son integration. Le texte du premier
bloc sortait donc **30px plus large de chaque cote** que le visuel juste
au-dessus.

Contrainte structurelle : le **bloc titre de page** est un bloc du coeur pose en
region `content`, donc un **frere** du contenu du node, pas un descendant. Une
valeur declaree sur le composant qui enveloppe le node ne peut pas l'atteindre.

## Options considerees

### Option A : aligner `text_left_aligned` sur 900

- Avantages : une seule largeur sur tout le site, aucune notion nouvelle.
- Inconvenients : **ecarte par l'utilisatrice**. Le bloc est partage avec les
  CGV (15 sections), « Qui sommes-nous » et les pages produit ; le passer a 900
  impose de rejouer et revalider quatre pages deja recettees, pour une page.

### Option B : laisser les deux largeurs cohabiter sur la page

- Avantages : aucun risque de regression ailleurs.
- Inconvenients : le visuel et le texte qui le suit ne sont pas alignes. C'est
  precisement l'ecart que l'integration devait corriger.

### Option C : un modificateur `--news` sur les SDC de blocs

- Avantages : scope limite au gabarit concerne.
- Inconvenients : deux largeurs pour un meme composant selon le contexte, a
  declarer bloc par bloc — et le bloc titre de page, qui n'est pas un SDC,
  resterait a traiter a part.

### Option D : un token de colonne, retune par gabarit

Une custom property `--dm-content-column`, declaree une fois avec la valeur
commune et redeclaree sur le `<body>` du gabarit qui s'en ecarte.

- Avantages : une seule valeur par gabarit, atteinte par **tous** les
  descendants du `<body>` — bloc du coeur inclus. La valeur par defaut ne change
  pour personne.
- Inconvenients : un composant ne suit le gabarit que s'il **consomme** le token ;
  ceux qui gardent leur largeur en dur ne bougeront pas, silencieusement.

## Decision

**Option D**, avec la valeur de la page alignee sur celle du bloc (decision de
l'utilisatrice, pas l'inverse) :

```scss
:root { --dm-content-column: 900px; }          // valeur commune aux maquettes
body.page-node-type-news { --dm-content-column: 960px; }
```

Le retune vit sur le `<body>` — et non sur le composant — parce que le bloc
titre de page est un frere du contenu. La classe `page-node-type-<bundle>` est
posee par le coeur, elle est donc deja disponible sur tous les bundles.

**Consommateurs convertis** : `.block-page-title-block` (fondation
`_page-title.scss`), `video-centered`, `image-centered`, et le nouveau
`news-article`. Ce sont exactement les composants qui apparaissent sur un
gabarit dont la colonne est retunee.

**Non convertis, volontairement** : `text_left_aligned` garde son `960px`
litteral — c'est **lui** qui fixe la reference sur ce gabarit, la resoudre par
le token en ferait 900 sur les CGV et les pages produit, soit la regression que
l'option A voulait eviter. `page-intro` (450), `faq-list` (900),
`news-list` et `brands-grid` (565) gardent aussi leurs valeurs : aucun d'eux
n'apparait sur un gabarit retune.

## Consequences

**Plus facile**

- Une seule valeur a poser pour aligner toute une page, y compris les blocs du
  coeur qu'aucun SDC n'enveloppe.
- Ajouter un gabarit a colonne particuliere ne demande plus de toucher aux
  composants : une ligne sur `body.page-node-type-<bundle>`.

**Plus difficile / a surveiller**

- ⚠️ **Le token n'est pas universel.** Un composant qui garde sa largeur en dur
  ne suivra pas le gabarit, **sans erreur ni signe visible**. Avant de retuner la
  colonne d'un nouveau gabarit, lister les composants qui y apparaissent et
  verifier qu'ils consomment bien `--dm-content-column` — sinon les convertir.
- ⚠️ **Deux sources de verite sur la valeur 960** : le litteral de
  `text_left_aligned` et le retune du `<body>`. Si le bloc est un jour ramene a
  900, le retune doit disparaitre dans le meme mouvement. Les deux se citent
  mutuellement en commentaire.
- La regle « la colonne du site vaut 900 » cesse d'etre vraie partout : c'est
  desormais « 900 par defaut, retunable par gabarit ».

**Fichiers impactes**

- `src/scss/_tokens.scss` (declaration + retune), `src/scss/_page-title.scss`.
- `components/news-article/`, `components/video-centered/`,
  `components/image-centered/`.
