# Plan — Rationalisation des Paragraphes

> Plan approuve. Etude → livrable documente + ADR (pas de code de prod). Prealable technique #3 du PRD (§7).

## 1. Intention
Etablir la bibliotheque de paragraphes **reellement necessaire et optimale**, en recoupant les modeles des specs (F1 : ~13 modeles + blocs de templates) avec les **maquettes validees**, pour ne concevoir en SDC que l'utile et mutualiser au maximum. Pour : l'equipe dev (moins de dette) et les editeurs (BO coherent).

## 2. Fichiers impactes
Aucun code de prod — que de la doc :
- `docs/plans/paragraphs-rationalization.md` — ce plan
- `docs/active/paragraphs/recoupement.md` — tableau de recoupement specs ↔ maquettes
- `.claude/decisions/NNNN-bibliotheque-paragraphes.md` — ADR actant la liste finale + les mutualisations
- Mise a jour de **F1** dans `docs/PRD.md` une fois l'etude actee

*(Les SDC + la config Paragraphs = chantier suivant, hors de cette etude.)*

## 3. Interfaces publiques
Aucune dans l'immediat. L'etude **definit** les futures interfaces des SDC (noms de composants, props, slots, variantes). Pas de modif linter.

## 4. Securite
- **Texte riche** (CK Editor) : filtrage via text formats Drupal, jamais de `|raw` injustifie (XSS).
- **Video** : champ **Media oEmbed** (allowlist YouTube/Dailymotion/Vimeo) plutot qu'embed HTML libre.
- **Liens optionnels** : valider/normaliser les URLs (pas de `javascript:`).
- Contenu **public** → pas d'enjeu de cloisonnement partenaire (bloc configurateur = CTA seul).
- **Cache** : blocs « 5 dernieres actualites » et « carrousel marques » → declarer les list cache tags.

## 5. Risques et contraintes techniques
- **Specs non fiables techniquement** : contraintes techniques ignorees sauf justification, la maquette prime.
- **Sur-fragmentation** : mutualiser via **variantes de props** plutot que dupliquer.
- **Media unifie** : image et video = entites **Media** (decision #11), branche sur le pipeline images (image styles/WebP/6 breakpoints).
- **SDC ↔ Paragraphe** : 1 paragraphe retenu = 1 SDC (props = champs, slots = zones), pas de logique metier en Twig.
- **Accessibilite AA** : accordeons = disclosure ARIA clavier ; carrousel accessible ; `alt` obligatoire ; hierarchie de titres coherente.
- **Reversibilite** : concevoir la structure **avant** de creer les types Paragraphs.

## 6. Coherence avec les specs
Aligne avec **F1**, decisions **#10 (SDC)** et **#11 (media)** ; prealable **#3** de la §7. Ne contredit aucune decision verrouillee. Pas de nouveau parcours E2E ; **F1 sera mis a jour** avec la liste reelle une fois l'etude actee.

## 7. Plan d'implementation (de l'etude)
1. **Inventaire specs** → liste exhaustive des modeles + blocs de templates. *Verif : liste complete vs texte specs.*
2. **Inventaire maquettes** → enumerer les frames Figma, identifier chaque ecran et ses blocs. *Verif : chaque ecran a sa liste de blocs.*
3. **Tableau de recoupement** : `modele spec → present en maquette ? → verdict (garder/fusionner/ecarter) → contrainte inutile ecartee → SDC cible (props/slots/variantes)`. *Verif : chaque ligne tranchee, aucun bloc orphelin.*
4. **Session d'arbitrage** → valider verdicts + mutualisations. *Verif : validation ligne par ligne.*
5. **Proposition d'optimisation** → liste finale des SDC + sous-composants mutualises (CTA, media, accordeon). *Verif : tous les blocs couverts avec le minimum de composants.*
6. **ADR + mise a jour F1**. *Verif : ADR complet.*

## 8. Strategie de test / boucle de feedback
- **Matrice de couverture** « bloc maquette → paragraphe retenu » sans trou.
- **Tracabilite** : chaque besoin fonctionnel des specs couvert par ≥ 1 paragraphe.
- **Revue humaine** (etape 4) = boucle principale.
- **Cas limites** : champs optionnels → props nullable ou variantes ? ; emplacement media image *ou* video → slot polymorphe ; blocs home/produit non reutilisables → SDC dedies ou integres ? ; accordeons imbriquant des sous-blocs.
- **Boucle Figma** : `get_metadata` + `get_screenshot` par frame.

## Statut
- [x] Plan valide
- [x] Etape 1 — Inventaire specs (docs/active/paragraphs/recoupement.md)
- [x] Etape 2/3 — Recoupement (inventaire specs + proposition ; maquettes tranchees directement par l'utilisatrice)
- [x] Etape 4 — Arbitrage (bibliotheque validee par l'utilisatrice)
- [x] Etape 5 — Bibliotheque finale (docs/active/paragraphs/library.md — 27 paragraphes + ratios de crop)
- [x] Etape 6 — ADR-001 + MAJ F1 + note ratios en §7

**Etude terminee.** Reste hors de cette etude : implementation des SDC + config Paragraphs (chantier ulterieur), etude images (ratios connus), types de contenu (prochaine session).
