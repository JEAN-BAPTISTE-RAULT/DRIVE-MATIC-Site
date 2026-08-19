# ADR-009 : Telechargements nommes (plusieurs documents dans un meme bloc)

## Statut

Accepte

## Date

2026-08-14

## Contexte

Le theme rend tout champ fichier via un override global de `file-link.html.twig` :
un bouton « Télécharger (FORMAT POIDS) » (le poids etant fourni par
`drive_matic_preprocess_file_link()`). Cela convient tant qu'un bloc ne porte
**qu'un** document.

`product_characteristics` en porte **deux** (`field_file_notice`,
`field_file_doc`) : le rendu global produirait deux boutons rigoureusement
identiques, impossibles a distinguer. La maquette (Figma 390:11137) les nomme :
« Notice technique (pdf 1 Mo) » et « Dossier général (pdf 1Mo) ».

Les libelles de champ ne sont pas utilisables : ils sont administratifs
(« Notice technique (à télécharger) », « Documentation (à télécharger) »).
`F6 Documentations` aura le meme besoin.

## Options considerees

### Option A : etendre le theme `file_link` avec une variable de libelle

- Avantages : un seul point de rendu pour tous les telechargements du site.
- Inconvenients : le hook `file_link` ne connait pas le champ parent (il ne
  recoit que le fichier) ; il faudrait declarer une variable supplementaire via
  `hook_theme_registry_alter()` **et** trouver un moyen de la poser depuis le
  contexte du champ. Beaucoup de machinerie pour un libelle.

### Option B : afficher le libelle de champ

- Avantages : aucun code, le libelle est deja saisi en back-office.
- Inconvenients : ces libelles sont rediges pour l'admin et porteraient la
  mention « (à télécharger) » en front. Les rendre presentables reviendrait a
  faire porter du texte public par de la config de champ.

### Option C : rendre le champ hors formatter, dans le SDC

- Avantages : le composant maitrise entierement son markup, aucune contrainte
  venue du rendu global ; libelle front porte par le theme donc previsible et
  traduisible ; reutilisable par tout bloc multi-documents.
- Inconvenients : le fichier n'etant plus rendu par son formatter, ses **cache
  tags ne remontent plus** (a reattacher explicitement) ; le SDC gagne une prop
  au lieu d'un slot, donc une API un peu moins « Drupal ».

### Option D : libelle saisi en back-office (champ « description » du fichier)

- Avantages : correspond a la lettre de la spec F5 (« nom/format/poids ») ; la
  personne qui depose le document le nomme, donc le libelle suit toujours le
  document reellement en ligne (un libelle fixe mentirait si l'editeur change
  de piece).
- Inconvenients : impose un changement de config (`description_field`) **et**
  une saisie supplementaire a chaque depot ; un libelle vide doit avoir un repli.

## Decision

**Option C pour le mecanisme, option D pour le libelle** (arbitrage rendu par
l'utilisatrice le 2026-08-14).

Un helper `_drive_matic_document_downloads(paragraph, field_names, variables)`
(`drive_matic.theme`) parcourt les champs fichier demandes et renvoie une liste
`label` / `url` / `format` / `size` prete a afficher ; il reattache au passage
les cache tags des fichiers. Le SDC `product-characteristics` recoit une prop
`downloads` (les slots `notice` / `documentation` disparaissent) et dessine le
bouton « contour + icone » de la maquette.

Le **libelle vient de la description du fichier**, saisie en back-office
(`description_field: true` sur `field_file_notice` et `field_file_doc`, avec un
texte d'aide qui explique que cette description devient le libelle du bouton).
A defaut de description, repli sur le **nom du fichier prive de son extension**
— jamais de bouton sans libelle. Le **format et le poids restent calcules**.

Ecarte : un libelle fixe porte par le theme (« Notice technique », « Dossier
général »), plus previsible mais qui affirmerait quelque chose que le document
depose ne garantit pas.

## Consequences

- **Etendu a tout le site** (decision du 2026-08-14, dans la foulee) : le libelle
  editorial ne concerne pas que le bloc multi-documents. `description_field` est
  active sur les **9 instances** de champ fichier des paragraphes, et l'override
  global `file-link.html.twig` affiche `download_label` (description saisie).
  Deux rendus subsistent — le bouton global du formatter pour un document
  unique, le helper pour un bloc en portant plusieurs — mais **un seul
  comportement editorial**.
- **Description obligatoire** (decision du 2026-08-14) : plutot que de traiter
  le libelle manquant comme un cas courant, on l'empeche a la saisie. Le cœur
  n'offrant pas ce reglage (`description_field` n'a pas de pendant
  « obligatoire »), un `#process` ajoute apres celui de `FileWidget` marque la
  description `#required` et la renomme « Libellé du bouton de téléchargement »
  (`drivematic_forms`, hook `field_widget_single_element_file_generic_form_alter`).
  Comme le cœur ne construit cet element **qu'avec un fichier joint**, un champ
  fichier laisse vide reste facultatif : la contrainte ne mord que sur un
  document reellement deverse.
- **Replis conserves comme filet, pas comme comportement** : « Télécharger »
  pour un document unique, **nom du fichier** pour un bloc multi-documents. Ils
  ne sont plus atteignables par le formulaire ; ils couvrent le contenu cree
  **avant** cette contrainte et les insertions **programmatiques** (seed,
  migration), qui ne passent pas par la validation de formulaire.
- **Doublon de libelle non surveille** : rien n'empeche de saisir deux fois le
  meme libelle dans un bloc multi-documents. C'est de la responsabilite
  editoriale ; y ajouter une validation couterait plus que le probleme.
- **Saisie editoriale a expliquer** : la description d'un fichier n'est pas un
  champ evident en back-office. Le texte d'aide de chaque champ precise qu'elle
  devient le libelle du bouton — a reprendre pour tout nouveau champ fichier.
- Deux rendus de telechargement coexistent desormais dans le theme — l'un
  global (« Télécharger »), l'autre par composant (nomme). C'est le prix de la
  fidelite maquette ; le format reste en **majuscules** dans les deux (« PDF »),
  malgre le « pdf » minuscule de la maquette, pour ne pas creer d'incoherence
  interne.
- Un bloc qui rend un fichier hors formatter **doit** reattacher ses cache tags
  (sinon le remplacement d'un fichier n'invalide pas la page).
- Fichiers impactes : `drive_matic.theme`, `components/product-characteristics/`
  (`.component.yml`, `.twig`, `.scss`),
  `templates/paragraph/paragraph--product-characteristics.html.twig`,
  `images/icons/download.svg`.
- ~~Reutilisable tel quel par **F6 Documentations**~~ — **corrige le
  2026-08-19** : la maquette reelle de F6 (Figma 398-12119), non revue au
  moment de cette decision, ne porte pas le bouton « contour + icone »
  d'Option C mais une **ligne de liste zebree entierement cliquable**. Un
  troisieme rendu visuel existe donc bien, contrairement a ce
  qu'anticipait cette clause. Ce qui est repris a l'identique, c'est le
  **mecanisme** (Option C/D : tuple `label`/`url`/`format`/`size` calcule hors
  formatter, cache tags reattaches, libelle = description du fichier) — via
  une fonction sœur, `_drive_matic_field_downloads()`, plutot qu'un appel a
  `_drive_matic_document_downloads()` : celle-ci boucle plusieurs champs
  **mono-valeur nommes** (un bouton par champ), F6 porte deux champs a
  **cardinalite illimitee** (un bouton par delta) — la forme de l'iteration
  differe, pas seulement le style. Voir `components/documents-list/`.
