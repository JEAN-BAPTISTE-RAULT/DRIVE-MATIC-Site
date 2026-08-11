# Plan — Types de contenu

> Etude de definition (livrable doc + ADR, pas de code). Perimetre : **types editoriaux/publics** ; le modele **partenaire/devis** est un chantier distinct.

## Resolution
L'arbitrage a ete **fourni directement par l'utilisatrice** (liste complete des 15 types + 2 conventions transverses). Le modele est donc acte sans passer par les etapes d'inventaire/proposition initialement prevues.

- Modele de reference : `docs/active/content-types/model.md`
- Decision : [ADR-002](../../.claude/decisions/002-types-de-contenu.md)
- Precise ADR-001 (champ « lien » interne/externe + cible ; « fichier telechargeable » nom/format/poids)
- PRD : §5 (modele de donnees) pointe vers le modele

## Intention
Definir le modele de contenu editorial (types, champs, entites, taxonomie, mapping paragraphes) en coherence avec le menu, les maquettes et la bibliotheque de paragraphes (ADR-001).

## Décisions tranchées
1. ✅ `question`/`document`/`brand` = **nodes sans page publique** (hors sitemap + URL bloquée), pas des entités custom.
2. ✅ `product` sans champ catégorie (contenu 100% manuel).
3. ✅ Vocabulaire unifié sur `categories`.
4. ✅ `news` : date = dernière modification (`changed`).
5. ✅ `legals` : indexable, dans le sitemap, sans metatags.

## Statut
- [x] Perimetre valide (editorial ; partenaire/devis a part)
- [x] Modele fourni et acte (model.md + ADR-002)
- [x] Repercussion sur ADR-001/library.md (conventions liens/fichier)
- [x] MAJ PRD §5
- [x] Tous les points tranches — etude terminee
