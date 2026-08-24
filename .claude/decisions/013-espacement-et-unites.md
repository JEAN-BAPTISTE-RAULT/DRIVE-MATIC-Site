# ADR-013 : Systeme d'espacement et unites

## Statut

Accepte

## Date

2026-08-17

## Contexte

Trois constats de l'utilisatrice sur la home, tous symptomes d'un meme manque de
systeme :

1. « On ne distingue plus ou un paragraphe commence et ou il s'arrete. » Les
   ecarts internes des blocs (jusqu'a 54px) etaient du meme ordre que la
   separation entre deux blocs (80px) : rapport de 1,5, insuffisant pour marquer
   une frontiere.
2. Le jumbo perdait sa marge gauche en responsive — comme la grille et les
   actualites, sans que ca se voie a 1440px.
3. « Pourquoi passer par des `margin` pour certains paragraphes et des `padding`
   pour d'autres ? »

Etat des lieux mesure avant correction : 90 valeurs d'espacement en dur reparties
sur 20 composants, **73 en px et 35 en rem**, trois idiomes horizontaux
differents, et la typographie entierement en px — y compris les tailles de
police, ce qui est le seul point qui compte pour le redimensionnement du texte.

## Options considerees

### Option A : augmenter la respiration entre blocs

Porter le `padding-block` de 40 a 60px, soit 120px entre deux blocs.

- Avantages : retablit la hierarchie sans toucher aux ecarts internes, qui sont
  conformes a la maquette au pixel pres.
- Inconvenients : **ecarte par l'utilisatrice** — « ce sera trop aere alors que
  visuellement ca l'est deja beaucoup ».

### Option B : resserrer et uniformiser les ecarts internes

Une valeur unique entre elements, nettement inferieure a la separation des blocs.

- Avantages : la hierarchie revient sans rien aerer ; une seule valeur a regler.
- Inconvenients : s'ecarte de la maquette sur quelques ecarts (les actualites y
  passent de 54 a 24px).

### Unites : px partout, rem partout, ou une par role

- **px partout** : colle a la maquette, mais le texte ne suit plus la preference
  de taille de police du navigateur.
- **rem partout** : le texte suit, mais les gouttieres grossissent aussi alors
  que la fenetre, elle, n'a pas bouge.
- **une unite par role** : espacement en px, typographie en rem.

## Decision

**Option B**, plus **une unite par role**.

### Trois tokens, trois roles

| Token | Valeur | Role |
|---|---|---|
| `--dm-space-element` | 24px | ecart entre les elements d'un meme bloc |
| `--dm-space-block` | 32px | respiration verticale d'un bloc (`padding-block`) |
| `--dm-gutter` | 40px | gouttiere laterale (`padding-inline`) |

Deux blocs voisins sont donc separes de 64px, contre 24px en interne : rapport
de 2,7.

### La regle horizontale

`margin-inline: auto` ne sert qu'a **centrer** une largeur plafonnee. Il ne
garantit aucun ecart au bord : sous le plafond, il ne fait plus rien. La
gouttiere est un **`padding-inline`**, et le plafond vaut « contenu + 2
gouttieres ».

Deux exceptions, toutes deux dictees par le design :

- **gouttiere d'un seul cote** quand la piste deborde volontairement (jumbo,
  actualites, histoire, presentation produit) ;
- **bloc full-bleed** quand le fond court d'un bord a l'autre (50/50,
  caracteristiques produit, triptyque, image 100%) : la gouttiere vit alors dans
  le padding du bloc.

### Les unites

- **Espacement en px.** Une gouttiere n'a pas de raison de grossir parce que le
  texte grossit : la largeur de la fenetre n'a pas change.
- **Typographie en rem.** Les tailles suivent la preference de police du
  navigateur — c'est ce que vise WCAG 1.4.4, verrouille par la decision #8 du
  PRD. Mesure a 20px de police racine : le H1 passe de 45 a 56,25px, le corps de
  16 a 20px, gouttiere et padding inchanges.
- **`em`** reste reserve a ce qui est intrinsequement lie au texte (marge sous un
  paragraphe, taille d'une icone en ligne). Une gouttiere en `em` serait une
  faute : elle dependrait de la taille de police du bloc voisin.

## Consequences

**Plus facile**

- Une valeur a changer pour regler tout le rythme du site.
- La frontiere entre paragraphes se lit sans effort.
- Le texte est redimensionnable sans casser la mise en page.

**Plus difficile / a surveiller**

- ⚠️ **Un ecart entre elements appartient au bloc, pas au contenu saisi.** Le
  `<p>` d'un champ texte riche porte 16px de marge haut et bas, qui s'ajoutaient
  a l'ecart du bloc — titre → texte faisait 26px au lieu de 10 sur le bloc
  configurateur. Neutralise dans les fondations
  (`.text-formatted > :first-child / :last-child`). Toute nouvelle enveloppe de
  texte riche doit passer par cette classe.
- ⚠️ **Une marge ne se pose que s'il y a un element en dessous** : les champs
  etant optionnels, un titre peut etre le dernier element affiche. D'ou
  `:not(:last-child)`. Piege associe : le reset ne neutralise que les marges du
  `body`, pas celles des titres — sans `margin: 0` explicite, un titre seul
  reprend celle du navigateur et la correction reste invisible.
- Les ecarts internes s'ecartent de la maquette la ou elle etait plus genereuse
  (actualites : 24px au lieu de 54). Choix assume au profit de la lisibilite.
- `product_characteristics` garde 70px de padding vertical : bandeau sombre
  pleine largeur, volontairement plus genereux. Seule valeur hors rythme commun.

**Fichiers impactes**

- `src/scss/_tokens.scss` (les trois tokens, typographie en rem),
  `src/scss/_typography.scss` (marges du texte riche).
- Les 20 composants SDC.

## Addendum du 2026-08-24 — bascule mobile-first

`--dm-gutter` et `--dm-space-page` (le « quatrieme token » du gabarit de
page, cf. README) ainsi que l'echelle de titres (h1/h2/h3, taille +
interligne) ne portaient qu'une valeur, mesuree sur les maquettes
**desktop** (`--dm-gutter: 40px`) — aucune des maquettes **mobiles**
n'etait alors reprise dans les tokens. Corrige : chacun porte desormais une
valeur mobile de base (mesuree sur les maquettes mobiles revues : home
526-20394, FAQ 527-24855, documentations 527-25612, contact 600-29489,
actualites 601-31379, marques 600-28845, corporate 527-26410 — toutes
donnent `--dm-space-page: 13px` a 1px pres), et un bloc `@media (width >=
992px)` restaure la valeur desktop d'origine. Repercute sur ~25 composants
qui consommaient ces tokens sans distinction d'ecran.

**`--dm-space-element` (24px) n'est PAS concerne : il reste une valeur
unique**, y compris entre mobile et desktop — c'est une regle distincte
(« ne pas le desaccorder bloc par bloc », ci-dessus), pas un oubli.
Consequence directe verifiee le meme jour sur `news-article` (detail d'une
actualite, maquette mobile 601-32027) : cette maquette mesure 16px sous le
titre et 30px sous la date, deux valeurs differentes, alors que le code
n'a qu'un seul token a leur offrir — impossible de satisfaire les deux a la
fois sans casser l'unicite. Arbitrage reconduit (24px des deux cotes),
decision de l'utilisatrice.
