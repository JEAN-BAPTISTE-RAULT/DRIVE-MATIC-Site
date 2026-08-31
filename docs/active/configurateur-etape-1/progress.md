# Progress — Configurateur de devis (F14)

> Point de reprise pour la suite du chantier F14. Etat au 2026-08-31.

## Fait (écran 1 « Configuration »)

- Module `drivematic_configurator`, `ConfigurationForm` (FormBase), route
  `/configurer` (`_role: partenaire`). Commité (`6380bda`).
- Cascade véhicule (marque/modèle/motorisation), 4 équipements en dur (F17
  pas encore là), quantité rétrovision extérieure bornée 1-2, blocs
  répétables max 10, suppression dès le 2ᵉ bloc.
- Détail complet (plan, décisions, ~10 corrections post-livraison,
  mesures) : [plan](../../plans/configurateur-etape-1.md),
  [verification.md](verification.md),
  [ADR-028](../../../.claude/decisions/028-configurateur-formbase-vs-webform.md).

## Fait (écran 2 « Devis »)

- Route `/configurer/devis` (`QuoteForm`), brouillon `PrivateTempStore`,
  calcul des tarifs/remise/TVA/totaux par `QuoteCalculator`. Modifier/
  Ajouter une configuration/pastille « Configuration » du fil d'étapes
  ramènent tous les 3 à l'étape 1 avec le même brouillon prérempli.
  Commité (`e9aa974`, `0323b2d`) — [ADR-031](../../../.claude/decisions/031-devis-tempstore.md).
- Totaux (« Tarif par véhicule »/« Tarif total véhicules »/« Total
  configuration(s) ») alignés au pixel près sur les colonnes du tableau
  d'équipements, conformes aux maquettes desktop 508-13961/mobile
  606-37565. Détail technique (raisons des 2 structures DOM parallèles
  desktop/mobile, formule `calc()` de la ligne générale) : README.md
  (section configurateur écran 2) et mémoire auto.
- **Hors périmètre de cet écran** : étape Livraison (F14 3/3), entités
  métier Devis/Configuration/Ligne d'équipement (F15).

## Reste à faire

- **Écran 3 « Livraison »** : adresse de facturation (lecture seule),
  choix/ajout/modification d'adresse de livraison persistée en back-office.
- **Entités métier Devis/Configuration** (F14-F15) : rien n'est persisté au
  delà de la session (`PrivateTempStore`) pour l'instant — le CTA final de
  l'écran Devis (« Choisir ma livraison ») est un placeholder. Numérotation
  `WAAAAMMJJ-001`, cycle de vie (à finaliser / en cours / archives) à
  concevoir.
- `docs/content-model.md` ne référence toujours pas les 3 taxonomies
  véhicule (dérive documentaire antérieure à ce chantier, non corrigée).

## Repère pour la suite

Avant de reprendre, `/plan` sur l'écran 3 « Livraison ».
