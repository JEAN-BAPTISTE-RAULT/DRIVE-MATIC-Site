# Progress — Configurateur de devis (F14)

> Point de reprise pour la suite du chantier F14. Etat au 2026-08-26.

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

## Reste à faire (hors périmètre de cet écran)

- **Écran 2 « Devis »** : tableau récapitulatif par configuration (tarif
  catalogue HT, tarif remise HT, quantités, totaux), totaux véhicule /
  configuration / général (HT, remise, TVA 20 %, TTC). Dépend du catalogue
  produit (F17) pour les tarifs.
- **Écran 3 « Livraison »** : adresse de facturation (lecture seule),
  choix/ajout/modification d'adresse de livraison persistée en back-office.
- **Entités métier Devis/Configuration** (F14-F15) : rien n'est persisté au
  delà de `form_state` pour l'instant — le bouton « Voir mon devis » affiche
  un message de confirmation temporaire et ne fait rien d'autre.
  Numérotation `WAAAAMMJJ-001`, cycle de vie (à finaliser / en cours /
  archives) à concevoir.
- **Catalogue produit / équipements (F17)** : les 4 équipements resteront
  codés en dur tant que ce chantier n'est pas pris — impacte directement
  l'écran 2 (pas de tarif catalogue sans lui).
- `docs/content-model.md` ne référence toujours pas les 3 taxonomies
  véhicule (dérive documentaire antérieure à ce chantier, non corrigée).

## Repère pour la suite

Avant de reprendre, `/plan` sur l'écran 2 — dépend de F17 (catalogue) pour
les tarifs, donc probablement à séquencer après ou en parallèle de F17.
