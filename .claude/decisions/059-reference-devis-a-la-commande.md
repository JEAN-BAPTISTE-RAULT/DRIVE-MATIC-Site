# ADR-059 : La référence "W..." d'un devis est posée à la commande, pas à sa création

## Statut

Accepté

## Date

2026-09-14

## Contexte

Bug signalé par l'utilisatrice : un devis créé "à finaliser" le 07/09, puis
commandé seulement le 14/09 (après avoir dormi en brouillon), affichait une
référence `W20260907-001` — une date antérieure à la date de commande
réellement affichée ailleurs (`date_comptable`, colonne "Date" de
"Mes devis"). Vérifié en préprod : 1 devis sur 5 déjà commandés/archivés
présentait ce décalage (le seul dont la commande n'a pas eu lieu le jour
même de la création du brouillon).

Cause : `QuoteReferenceGenerator::generate()` produit une référence
`W<AAAAMMJJ>-NNN` à partir de la date du jour **au moment de l'appel** — mais
cet appel avait lieu dans `QuotePersister::persist()`, exécuté dès la
**première sauvegarde** du devis (`STATUS_A_FINALISER` ou
`STATUS_A_COMMANDER` indifféremment), jamais à la transition ultérieure vers
`STATUS_A_COMMANDER` via `QuotePersister::update()` (reprise "Modifier").

**Décision de l'utilisatrice** : la référence est le numéro de la commande,
pas un identifiant de brouillon — elle doit être posée au moment où le
partenaire valide sa demande auprès de Drive Matic, jamais avant. Cohérent
avec l'interface existante, qui masque déjà la colonne "N° devis" sur
l'onglet "à finaliser" (`MyQuotesController`).

## Décision

- **`Entity/Quote.php`** : le champ `reference` n'est plus `setRequired(TRUE)`
  — absent (`NULL`) tant que le devis reste `STATUS_A_FINALISER`.
- **`QuotePersister::persist()`** : génère la référence uniquement si
  `$status === Quote::STATUS_A_COMMANDER` (création directe d'une commande,
  sans étape brouillon). Reste `NULL` pour `STATUS_A_FINALISER`.
- **`QuotePersister::update()`** : génère la référence à la première
  transition vers `STATUS_A_COMMANDER` (condition sur le statut ET sur une
  valeur actuelle vide, idempotent) — c'est le point qui manquait,
  responsable du bug.
- Aucun changement à `QuoteReferenceGenerator` : sa logique (compteur
  journalier par `LIKE`, verrouillé) reste correcte, il suffisait de
  l'appeler au bon moment.

## Prérequis avant ce chantier

Purge complète de tous les devis existants (`drush drivematic:quotes-purge`,
nouvelle commande) sur chaque environnement, pour ne pas mélanger des devis
créés sous l'ancien comportement (référence datée de la création) avec les
nouveaux (référence datée de la commande) — décision de l'utilisatrice,
plutôt qu'une correction rétroactive au cas par cas.

## Conséquences

- Un devis "à finaliser" n'a plus de référence tant qu'il n'est pas commandé
  — déjà invisible côté partenaire (colonne masquée), et le seul endroit qui
  pourrait l'exposer côté Drive Matic (Vue admin `/admin/content/devis`,
  filtrable sur ce statut) affichera une colonne vide pour ces lignes :
  cohérent, pas un bug.
- `Quote::label()` (alias de `reference`, utilisé pour le titre de la page
  de détail admin) est vide pour un devis "à finaliser" — acceptable, ce
  n'est pas encore un devis au sens commande.
- Aucun autre code ne suppose `reference` non vide pour un statut autre que
  `a_commander`/`commande`/`archive` (vérifié : PDF, e-mails, remises DM ne
  s'exécutent que sur ces statuts).
- Vérifié de bout en bout en local (interface réelle, pas seulement
  `drush php:eval`) : "Enregistrer le devis" → `reference` NULL ; reprise
  via "Modifier" → "Commander" → référence posée à la date du jour de la
  commande ; création directe → "Commander" (sans passer par "à finaliser")
  → référence posée immédiatement par `persist()`. Les deux chemins de code
  produisent une référence cohérente avec `date_comptable`.
- Fichiers modifiés : `Entity/Quote.php`, `Service/QuotePersister.php`.
