# ADR-032 : Bandeaux de totaux du devis — espace identique entre metriques plutot qu'alignement colonne par colonne

## Statut
Accepte

## Date
2026-08-31

## Contexte
Les 4 premieres metriques d'un bandeau de totaux (`Total HT`, `Remise HT`, `Total remisé HT`,
`TVA 20 %`) portaient chacune une largeur fixe en px (145/130/190/145), choisie a l'origine
pour que leurs libelles s'alignent verticalement entre les 3 bandeaux d'une configuration
(« Tarif par véhicule », « Tarif total véhicules », et le bandeau général `&__grand-totals`) —
voir [[table-layout-auto-redistribution]].

Ces largeurs etaient dimensionnees pour un cas, pas mesurees pour couvrir toute la plage
reelle des montants. Consequence, signalee par l'utilisatrice sur un devis reel : l'espace
visible APRES chaque texte (« Total HT : 1 919,50 € », etc.) variait fortement — 13 a 31px
mesures selon la metrique — puisque le contenu reel ne remplit jamais ces largeurs de facon
uniforme. Plus grave, decouvert en mesurant : la boite « Remise HT » (130px) etait deja trop
etroite dans cet exemple et debordait de ~7 a 11px sur son propre espace reserve des qu'une
remise depassait ~140 € (`Remise HT` peut valoir jusqu'a l'ordre de grandeur de `Total HT`
selon le taux partenaire, ADR-026).

## Options considerees

### Option A : recalibrer les largeurs fixes existantes
- Avantages : conserve l'alignement vertical entre les 3 bandeaux d'une configuration ET entre
  configurations (verifie fonctionnel avant ce changement : memes positions x sur les 2 lignes
  testees).
- Inconvenients : les espaces resteront differents d'une metrique a l'autre (les montants n'ont
  jamais le meme nombre de chiffres) — ne repond pas a la demande d'espaces identiques. Un
  redimensionnement resterait lui aussi une estimation figee, potentiellement insuffisante pour
  un futur cas extreme (gros volume de vehicules, forte remise).

### Option B (retenue) : chaque metrique s'etire a son propre contenu, gap uniforme
- Avantages : espace reellement identique entre chaque metrique, garanti par construction
  (`gap: 20px` du conteneur flex, pas une largeur figee) — plus aucun risque de debordement
  interne quel que soit le nombre de chiffres.
- Inconvenients : les libelles (« Remise HT », « TVA »...) ne demarrent plus nécessairement a
  la meme position x entre les 3 bandeaux d'une configuration, ni entre configurations,
  puisque les montants precedents n'ont pas la meme largeur d'une ligne a l'autre.

Compromis presente explicitement a l'utilisatrice (pas tranche en silence, cf. CLAUDE.md
§1) : elle a choisi l'Option B, l'espace identique primant sur l'alignement colonne par
colonne.

## Decision
Option B. `&__metric--ht`, `&__metric--discount`, `&__metric--discounted-ht` et
`&__metric--vat` perdent leur `width` fixe (`src/scss/_quote-form.scss`) — `display:
inline-flex` sans largeur s'etire deja a son contenu par defaut. Seule `&__metric--ttc`
garde une largeur fixe (160px) : elle n'est pas concernee par l'espacement sequentiel
(poussee a part au bord droit via `margin-inline-start: auto`) et doit rester alignee avec
la colonne « Total remisé € HT » du tableau au-dessus — alignement independant de celui
abandonne ici.

## Consequences
- Plus de risque de debordement interne d'une metrique quel que soit le montant.
- Perte de l'alignement vertical strict entre bandeaux pour les 4 premieres metriques —
  assume, rien de choquant observe sur les cas reels testes (les 2 lignes d'une meme
  configuration restent visuellement proches).
- Si un besoin futur exige de restaurer un alignement colonne par colonne strict (ex. export
  PDF du devis, cf. F14 etape 3), reconsiderer cette decision plutot que de la contourner
  localement.
