# Rationalisation des Paragraphes — recoupement specs ↔ maquettes

> ✅ **Arbitrage tranche** — la bibliotheque validee est dans `library.md` (27 paragraphes) et actee dans [ADR-001](../../../.claude/decisions/001-bibliotheque-paragraphes.md). Ce document reste la **trace de l'analyse** specs↔maquettes ; la proposition de mutualisation ci-dessous a ete remplacee par les choix de l'utilisatrice.
>
> Livrable de travail (etape 3 du plan). Statut d'origine : inventaire specs complet ; colonne maquettes partielle.

## 1. Inventaire specs (etape 1 — complet)

### A. Modeles de paragraphes generiques (specs §2.6.2)

| # | Modele (spec) | Composition (spec) |
|---|---------------|--------------------|
| A1 | Bloc « informations generales » | titre centre, texte centre optionnel, CTA optionnel |
| A2 | Image a droite / texte a gauche | titre G, texte G, CTA optionnel, image D |
| A3 | Image a gauche / texte a droite | titre D, texte D, CTA optionnel, image G |
| A4 | « Image a gauche fond gris » | image G, titre, texte, CTA optionnel |
| A5 | Texte centre | 1 titre centre *(spec incomplete : texte manquant ?)* |
| A6 | Texte large | titre, texte pleine largeur, lien optionnel (blank/interne), telechargement doc optionnel |
| A7 | Image centree | image pleine largeur, legende optionnelle |
| A8 | Video centree | video (YouTube/Dailymotion/Vimeo), legende optionnelle |
| A9 | « Image 100% largeur » | image plein ecran, titre optionnel, lien optionnel |
| A10 | « 3 blocs centres » | par bloc : texte (plus de) optionnel, chiffre, legende |
| A11 | « Histoire » | par bloc : titre, texte, image **ou** video |
| A12 | « FAQ » | question, reponse, lien optionnel (blank/interne) |

### B. Blocs specifiques Home (template dedie, §2.7)

| # | Bloc (spec) | Composition |
|---|-------------|-------------|
| B1 | Titre generique | titre |
| B2 | Jumbo (max 3) | visuel, titre, CTA optionnel |
| B3 | Bloc solutions auto-ecole & PMR | titre + 3 blocs (visuel, titre, produits + liens) |
| B4 | Bloc configurateur | visuel, titre, texte, lien configurateur |
| B5 | Bloc actualites | titre, 5 dernieres (image, titre, lien), lien « voir toutes » |
| B6 | Bloc marques partenaires | titre + carrousel (fleches, ordre alpha) |
| B7 | Bloc image a gauche fond gris | = A4 |
| B8 | Bloc SEO | accordeons (question, reponse, lien optionnel ; fermeture du precedent) |

### C. Blocs pages Solutions auto-ecole / PMR (§2.8)

| # | Bloc | Composition |
|---|------|-------------|
| C1 | Image 100% largeur | = A9 |
| C2 | Informations generales | = A1 |
| C3 | Bloc solutions | titre optionnel + 3 a 6 blocs (visuel, titre optionnel, produits + liens) — variante de B3 |
| C4 | Bloc configurateur | = B4 |
| C5 | Bloc FAQ | titre + accordeons — variante de A12/B8 |

### D. Blocs pages Produit (§2.9)

| # | Bloc | Composition |
|---|------|-------------|
| D1 | Image 100% largeur | = A9 |
| D2 | Bloc « argumentaires » | titre |
| D3 | Image a gauche / texte a droite | = A3 |
| D4 | Bloc swipe | max 5 (visuel **ou** video, titre, texte, CTA optionnel) |
| D5 | Caracteristiques techniques | titre, visuel, donnees (titre/texte), notice technique (doc optionnel, nom/format/poids), documentation (doc optionnel) |
| D6 | Bloc « titre » | titre + CTA optionnel |
| D7 | Bloc configurateur | = B4 |
| D8 | Bloc « cross selling » | titre + 1 a 5 produits (visuel, titre lien fiche) |

### E. Page Documentations (§2.10)

| # | Bloc | Composition |
|---|------|-------------|
| E1 | Informations generales | = A1 |
| E2 | Documentations auto-ecoles | liste docs (ordre BO, nom/format/poids) |
| E3 | Documentations PMR | idem E2 |

### F. Page Marques partenaires (§2.11)

| # | Bloc | Composition |
|---|------|-------------|
| F1 | Informations generales | = A1 |
| F2 | Bloc marques partenaires | liste de logos, ordre alpha — = B6 sans carrousel |

### G. Actualites (§2.12 / 2.13)

| # | Bloc | Composition |
|---|------|-------------|
| G1 | Liste actualites | informations generales + liste (photo, titre, date, lire la suite), pagination 10 |
| G2 | Detail actualite | titre, date, visuel, bloc (titre, texte, lien opt, doc, video opt) + paragraphes texte/image/video ajoutables |

## 2. Recoupement (etape 2/3 — colonne maquettes a completer)

> `present maquette` : ✅ observe / ⛔ absent / ❓ a verifier (acces Figma requis). Home et planche composants deja observees.

| Bloc spec | Present maquette | Verdict (proposition) | Contrainte spec a ecarter | SDC cible (proposition) |
|-----------|------------------|-----------------------|---------------------------|-------------------------|
| A1 / A5 / A6 | ❓ | Fusionner | alignements figes en 3 modeles distincts | `text_block` (variantes : align centre/large ; options CTA, lien, doc) |
| A2 / A3 / A4 / B7 | Home ✅ (fond gris) | Fusionner | 3-4 modeles pour 1 pattern « media + texte » | `media_text` (props : media_position G/D, background gris on/off, CTA opt) |
| A7 / A8 / A9 | Home ✅ (hero) | Fusionner | image et video traitees separement | `media_full` (props : type image/video, largeur contenue/pleine, legende/titre/lien opt) |
| A10 | ❓ | A confirmer | — | `stats` (items : chiffre, legende, texte opt) |
| A11 | ❓ | A confirmer | — | `story` (items : titre, texte, media) |
| A12 / B8 / C5 | Home ✅ (SEO/FAQ) | Fusionner | FAQ, SEO, FAQ-solutions = meme pattern | `accordion` (items Q/R/lien ; comportement « fermeture du precedent ») |
| B1 | ❓ | A confirmer | — | sous-titre de section (peut-etre pas un paragraphe) |
| B2 | Home ✅ | Garder | — | `jumbo` (items : media, titre, CTA opt ; max 3) |
| B3 / C3 | Home ✅ | Fusionner | 3 fixes (home) vs 3-6 (solutions) | `solutions_grid` (props : min/max blocs ; item visuel, titre, produits+liens) |
| B4 / C4 / D7 | Home ✅ | Fusionner | duplique sur 3 templates | `configurator_cta` (visuel, titre, texte, lien) |
| B5 | Home ✅ | Garder | — | `news_teaser` (5 dernieres ; list cache tags) |
| B6 / F2 | Home ✅ (carrousel) | Fusionner | carrousel (home) vs liste (page marques) | `brands` (prop : layout carrousel/grille) |
| D2 / D6 | ❓ | Fusionner ? | 2 blocs « titre » quasi identiques | `section_title` (titre + CTA opt) |
| D4 | ❓ | Garder | — | `swipe` (max 5 ; item media image/video, titre, texte, CTA opt) |
| D5 | ❓ | Garder | — | `tech_specs` (donnees + docs telechargeables) |
| D8 | ❓ | Garder | — | `cross_selling` (1-5 produits) |
| E2 / E3 | ❓ | Fusionner | 2 listes identiques (auto-ecole/PMR) | `document_list` (prop : categorie) |
| G1 | ❓ | Garder (vue) | — | vue Drupal + `page_header` (pas un paragraphe) |
| G2 | ❓ | Garder | — | template actualite + paragraphes reutilises |

**Sous-composants transverses (pas des paragraphes) :** `cta` (bouton, liste fermee de libelles), `media` (slot image/remote_video → entite Media, pipeline images/WebP/breakpoints), `product_link` (visuel + titre + lien fiche).

## 3. Bilan provisoire

- **~30 blocs specs** (A-G) → **~13-15 SDC** proposes apres mutualisation (dont ~7 sous-composants/paragraphes tres reutilises).
- Principaux gains : `media_text` (−3), `media_full` (−2), `text_block` (−2), `accordion` (−2), `configurator_cta` (−2), `brands`/`solutions_grid`/`document_list` (fusions de variantes).
- **A valider avec l'utilisatrice** (etape 4) : chaque verdict, puis la liste finale des SDC + leurs props/variantes.

## Reste a faire

- [ ] Completer la colonne « present maquette » (acces Figma) : ecrans Solutions, Produit, Documentations, Marques, Actualites, Formulaires, Espace partenaire.
- [ ] Ecarter definitivement les blocs absents des maquettes.
- [ ] Arbitrage des fusions avec l'utilisatrice.
- [ ] Figer la liste SDC + props → ADR + MAJ F1.
