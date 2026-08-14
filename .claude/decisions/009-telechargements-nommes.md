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

### Option D : nom du fichier (ou champ « description ») comme libelle

- Avantages : correspond a la lettre de la spec F5 (« nom/format/poids ») ;
  l'editeur maitrise le libelle.
- Inconvenients : le nom de fichier brut est rarement presentable
  (`DOC_2024_v3_final.pdf`) ; passer par le champ « description » impose un
  changement de config **et** une saisie supplementaire a chaque upload, sans
  garantie de qualite.

## Decision

**Option C.** Un helper `_drive_matic_document_downloads(paragraph, labels, variables)`
(`drive_matic.theme`) prend une carte `nom de champ => libelle front` et renvoie
une liste `label` / `url` / `format` / `size` prete a afficher ; il reattache au
passage les cache tags des fichiers. Le SDC `product-characteristics` recoit une
prop `downloads` (les slots `notice` / `documentation` disparaissent) et dessine
le bouton « contour + icone » de la maquette.

Le libelle front est donc porte par le **theme** : c'est du texte structurel du
composant (ce bloc a toujours une notice et une documentation), traduisible via
`t()`, et non de la donnee editoriale.

L'option D reste **ouverte cote produit** : si le libelle doit venir du document
choisi par l'editeur, il suffira de preferer la description du fichier au
libelle fixe dans le helper (+ activation du champ description). Trace dans le
PRD F5.

## Consequences

- Le bouton global (`file-link.html.twig`) reste **inchange** pour tous les
  blocs mono-document (`text_centered`, `text_left_aligned`, `image_text_*`,
  `accordion_element`…) : aucune regression sur l'existant.
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
- Reutilisable tel quel par **F6 Documentations** (meme besoin de nommer les
  documents) — c'est le point d'entree a reprendre plutot que d'inventer un
  troisieme rendu.
