# ADR-001 : Bibliotheque de Paragraphes

## Statut
Accepte

## Date
2026-08-11

## Contexte
Les specs (Passerelle v1.2) decrivent une serie de modeles de paragraphes et de blocs de templates (home, solutions, produit, docs, marques, actualites), mais de facon heterogene, redondante et avec des contraintes techniques non fiables (principe projet : les specs = besoins fonctionnels, pas une reference technique). Il fallait etablir la bibliotheque de paragraphes **reellement necessaire et optimale**, en recoupant avec les maquettes validees, avant de produire les SDC (decision #10) et de definir le pipeline images (decision #11).

Ce travail est le prealable technique #3 du PRD (§7). Analyse : `docs/active/paragraphs/recoupement.md`.

## Options considerees

### Option A : reprendre les modeles des specs tels quels (~30 blocs)
- Avantages : mapping 1:1 avec le document client.
- Inconvenients : forte redondance (plusieurs blocs « image+texte », FAQ/SEO dupliques, configurateur repete), contraintes techniques inutiles, dette front, BO confus pour les editeurs.

### Option B : bibliotheque rationalisee, imbrication Bloc/Element, ratios de crop normalises
- Avantages : moins de composants a maintenir, mutualisation par variantes/props, imbrication propre (Bloc → Elements via reference), ratios de crop maitrises (1:1, 16:9, 12:5, + « sans crop »), aligne SDC + pipeline images.
- Inconvenients : necessite un arbitrage explicite (fait avec l'utilisatrice).

## Decision
**Option B.** Bibliotheque de **27 paragraphes** validee par l'utilisatrice, dont plusieurs paires **Bloc / Element** (paragraphes imbriques). Liste de reference exhaustive (champs, obligatoires/optionnels, media, ratios, slideshow) : `docs/active/paragraphs/library.md`.

Ratios de crop retenus : **1:1**, **16:9**, **12:5**, plus un cas **« sans crop »** (largeur fixe selon maquettes, hauteur proportionnelle). Ils constituent l'entree de l'etude images (prealable #1).

## Consequences
- Chaque paragraphe retenu sera implemente comme un **SDC** (decision #10) ; un Bloc compose rend une liste de SDC « Element ».
- L'**etude images** peut demarrer sur des ratios connus (styles responsive sur les 6 breakpoints + WebP pour 1:1 / 16:9 / 12:5 ; regle de largeur fixe pour le « sans crop »).
- Les types « Element » sont exclus du placement direct (references `entity_reference_revisions`).
- Securite : les paragraphes video stockent thumbnail + lien embed → allowlister les providers (YouTube/Dailymotion/Vimeo) ; texte riche filtre via text formats ; liens valides.
- **F1 du PRD** est mis a jour pour pointer vers cette bibliotheque plutot que la liste heterogene des specs.
- Prochaine etape projet : definition des **types de contenu** (dont `news`, `brand`) qui hebergeront ces paragraphes.
