# Progression — integration des maquettes (stylisation bloc par bloc)

> Point de reprise, pas une source de verite. L'etat des blocs **deja integres**
> se lit dans `git log` (commits `feat(<bloc>): integration maquette`) ; les
> valeurs de reference sont dans Figma. Ce fichier ne contient que ce qui n'est
> derivable de nulle part ailleurs. **A supprimer en fin de chantier**, avec le
> bac a sable.
>
> Methode, capture et pieges : cf. memoire agent (`visual-integration-loop`).
> Derniere mise a jour : 2026-08-17 (chantier termine).

## Etat : termine

Les **18 blocs** de la bibliotheque validee (ADR-001) sont integres, Elements
stylises dans leur Bloc parent. Detail par bloc dans `git log`
(`feat(<bloc>): integration maquette`) ; valeurs de reference dans Figma
(fileKey `ZmmVBSOWSsHVkok6EU2Ays`).

Ce qui reste a faire **n'est plus de l'integration** : ce sont les reserves
ci-dessous, plus la recette visuelle sur des medias reels (logos de marques,
photos produit) et le controle sous Windows. Ce fichier disparait avec le bac a
sable une fois ces reserves arbitrees.

## Reserves ouvertes (non bloquantes)

- **Fleches de navigation dupliquees a l'identique** dans `history`, `news_home`,
  `brands_home` et `product_features` (carre gris clair 44px + chevron masque,
  ~40 lignes chacune), plus une variante blanche superposee dans `jumbo_home`.
  Deux routes possibles, a arbitrer en ADR :
  - **SDC partage** — desormais **eprouve** par `video-play` (la CSS d'un SDC
    inclus est bien attachee, aucun changement de build). Mais la fleche differe
    par son **placement** selon le bloc (superposee au visuel, dans l'en-tete,
    aux extremites de la rangee) : le composant partagerait l'apparence, et
    chaque bloc devrait positionner une classe qui ne lui appartient pas — ce que
    l'isolation SDC proscrit.
  - **Mixin de fondation** — garde chaque CSS scopee, mais demande un
    `--load-path=src/scss` dans `css:components` (decision de build).

- **Full-bleed et barre de defilement** : voir README (« Idiome pleine largeur »).
  A verifier sous Windows en recette ; concerne `image_full`,
  `product_characteristics`, `jumbo_home`, `history`.
- **Format en majuscules** (« PDF ») dans les boutons de telechargement, la ou
  la maquette ecrit « pdf » : ecart assume pour rester coherent sur tout le site
  (ADR-009).
- **Visuel de `product_characteristics` centre verticalement** dans sa colonne,
  la ou la maquette le place ~40px plus haut : choix robuste quand la colonne de
  droite s'allonge.
- **Ratio des visuels** : ne juger un rendu que sur des medias **recadres** pour
  le ratio concerne (cf. PRD, prealables images). Les medias 3 et 4 du bac a
  sable ont un `crop_16_9`.

## Etat du bac a sable (node 33)

Enrichi bloc par bloc, non versionne (base locale) : **19 paragraphes**, tous les
blocs integres a ce jour y sont rejouables. Ajouts recents : `history`
(4 entrees), `product_characteristics` (6 caracteristiques + 2 documents),
`news_home`, `brands_home`, `product_features` (3 diapositives dont une video en
façade), `product_cross` (3 cartes, dont une qui passe a la ligne) ; second PDF de demo
`public://sandbox/dossier-general.pdf` ; recadrages 16:9 sur les medias 3 et 4 ;
descriptions de fichier saisies sur 3 des 9 champs fichier ; **16 marques** de
demo (les 4 de test + 12 nommees) pour que la rangee deborde.

⚠️ Les medias de demo ne sont **pas des logos** (une photo et une capture) : la
tuile de marque est verifiee sur sa geometrie, pas sur son rendu final. Prevoir
de vrais logos avant la recette visuelle des marques.

`news_home` est aussi place sur la **page d'accueil** (node 31), son emplacement
prevu par F3 — le bac a sable sert a le rejouer, la home a le composer. Regle a
garder : **tout bloc integre doit etre ajoute au bac a sable**, meme quand il a
deja ete verifie sur sa page reelle.
