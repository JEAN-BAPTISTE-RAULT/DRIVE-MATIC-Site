# Decisions architecturales

Les decisions fondatrices sont dans [docs/PRD.md §3](../../docs/PRD.md).
Ce dossier documente les decisions **posterieures** au PRD initial.

| # | Titre | Statut | Date |
|---|---|---|---|
| [ADR-001](001-bibliotheque-paragraphes.md) | Bibliotheque de Paragraphes | Accepte | 2026-08-11 |
| [ADR-002](002-types-de-contenu.md) | Types de contenu editorial | Accepte | 2026-08-11 |

## Quand creer un ADR

- Choix de lib ou d'approche technique significatif
- Changement d'interface entre modules
- Tout choix qu'on pourrait regretter dans 6 mois sans trace du raisonnement

## Comment

1. Creer `NNN-titre-court.md` en suivant `TEMPLATE.md`
2. Ajouter une ligne dans ce tableau
3. Commiter avec le code qui implemente la decision
