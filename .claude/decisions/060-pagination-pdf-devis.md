# ADR-060 : Pagination réelle et alignement d'en-tête du PDF de devis

## Statut

Accepté

## Date

2026-09-14

## Contexte

Deux défauts signalés par l'utilisatrice sur le PDF de devis (pièce jointe
e-mail), confirmés sur un vrai fichier fourni (`W20260914-002.pdf`, 2 pages) :

1. Le pied de page affichait "Page 1/1" identique sur **chaque** page, quel
   que soit leur nombre réel. Cause : `quote-pdf.html.twig` est rendu par
   Dompdf en **une seule passe HTML** (`renderInIsolation()` produit une
   chaîne de caractères unique) — au moment où ce gabarit s'exécute, Dompdf
   n'a pas encore paginé : il n'existe ni "numéro de page courant" ni "nombre
   total de pages" à injecter en Twig. Le texte était donc littéralement
   statique.
2. "Adresse de facturation :" (colonne droite de l'en-tête) démarrait plus
   haut que "18 rue WOLFENBRUTTEL" (colonne gauche), cette dernière étant
   précédée du logo (`.pdf__logo`, 32px + 14px de marge) que la colonne
   droite n'a pas.

## Décision

**Pagination** — dessinée directement sur le canvas Dompdf, après `render()`,
plutôt que dans le gabarit Twig :

- `QuotePdfGenerator::addPageNumbers(Dompdf $dompdf)` (nouvelle méthode
  privée), appelée juste après `$dompdf->render()` et avant l'écriture du
  fichier. Utilise `Canvas::page_script()` (rejoué page par page une fois la
  pagination réelle connue) pour dessiner `"Page {num}/{count}"`, calculé et
  aligné à droite via `FontMetrics::getTextWidth()` (largeur variable selon
  le nombre de chiffres, recalculée à chaque page).
- **Alternative rejetée** : activer `Options::setIsPhpEnabled(true)` pour
  utiliser un bloc `<script type="text/php">` inline dans le gabarit (motif
  documenté par Dompdf pour ce même besoin). Rejeté : la documentation de
  Dompdf qualifie elle-même cette option de risque de sécurité (exécution de
  PHP arbitraire au sein du HTML rendu) — l'API canvas (`page_text()`/
  `page_script()`) obtient le même résultat sans l'activer.
- Position (x/y) recalculée à la main à partir des **mêmes valeurs** que le
  CSS du gabarit (`@page` et `.pdf__footer`) : le canvas Dompdf n'a aucun
  moyen de lire une position déjà calculée en CSS. Duplication assumée,
  commentée dans le code (constantes `MM_TO_PT`/`PX_TO_PT`, dpi Dompdf par
  défaut = 96) — à re-vérifier si `.pdf__footer`/`@page` changent un jour.
- Le `<span>Page 1/1</span>` et la règle `.pdf__footer-legal span:last-child`
  (qui le stylait en gras/aligné à droite) sont retirés du gabarit — cette
  règle CSS aurait sinon migré sur le texte légal RCS restant, devenu
  `:last-child` par défaut une fois l'autre `<span>` supprimé.

**Alignement d'en-tête** — `padding-top: 46px` ajouté à `.pdf__header-right`
(32px de hauteur de logo + 14px de marge, valeurs déjà posées sur
`.pdf__logo`), poussant tout le bloc adresses (facturation + livraison) au
niveau du texte de la colonne gauche.

## Vérification

Aucune donnée réelle nécessaire : un script `drush php:script` autonome
fabrique un render array `#theme: quote_pdf` (8 configurations, assez pour
forcer 4 pages) sans passer par une entité `Quote` persistée, invoque
`QuotePdfGenerator::addPageNumbers()` par réflexion (même code que la
production, pas une réimplémentation), génère un PDF dans `/tmp`, lu et
vérifié visuellement (outil Read) : "Page 1/4" → "Page 4/4" corrects sur
chacune des 4 pages, "Adresse de facturation :" aligné avec "18 rue
WOLFENBRUTTEL". Aucune donnée de test en base, rien à purger.

## Conséquences

- Fichiers modifiés : `templates/quote-pdf.html.twig`,
  `Service/QuotePdfGenerator.php`.
- Toute évolution future du CSS `@page`/`.pdf__footer` (marges, hauteur de
  ligne) doit être répercutée dans les constantes de
  `QuotePdfGenerator::addPageNumbers()` — pas de source unique possible tant
  que le numéro de page reste dessiné hors du flux HTML/CSS.
