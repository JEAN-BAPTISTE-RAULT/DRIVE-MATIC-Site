# ADR-040 : Traçabilité des remises exceptionnelles Drive Matic

## Statut
Accepte

## Date
2026-09-02

## Contexte
La remise exceptionnelle par ligne d'équipement (`QuoteEquipmentLine::dm_discount_rate`,
introduite par ADR-038) n'avait aucune traçabilité : `dm_discount_rate` est un simple
champ scalaire remplacé à chaque soumission de `QuoteDiscountForm`, sans trace de qui l'a
modifié ni quand — contrairement au changement de statut, qui a `QuoteStatusChange`
(ADR-038, addendum). Retour utilisatrice : l'historique du devis
(`QuoteDetailController::buildHistoryTable()`) doit aussi montrer les remises
exceptionnelles accordées, avec l'administrateur les ayant accordées.

## Options considerees

### Option A : généraliser `quote_status_change` en entité d'historique générique
- Avantages : une seule entité/table pour tout événement du devis.
- Inconvenients : les deux événements n'ont pas la même forme (un statut est un scalaire
  unique ; une remise porte plusieurs lignes modifiées, chacune avec un ancien ET un
  nouveau taux). Généraliser demanderait soit un champ `type` + une charge utile
  sérialisée (JSON), contraire à la convention du projet qui préfère les
  `entity_reference` structurés, soit une migration du schéma existant. Avec seulement
  2 occurrences du pattern, l'abstraction n'est pas encore justifiée (CLAUDE.md §2,
  simplicité d'abord).

### Option B (retenue) : nouvelle entité dédiée `quote_discount_change`
- Avantages : reproduit à l'identique le pattern déjà établi et déjà validé
  (`QuoteStatusChange`) — une entité content par type d'événement, aucun handler d'accès
  propre (protégée par l'accès déjà vérifié sur le `Quote` parent), champs
  `entity_reference` structurés (`quote_id`, `line_id`, `uid`), jamais mise à jour ni
  supprimée.
- Inconvenients : un 5e `hook_update_N` sur ce module (idempotent, même garde que
  `_11004`).

### Granularité : un événement par ligne changée vs un événement par soumission
- `QuoteDiscountForm` porte sur toutes les lignes d'équipement du devis en une seule
  soumission, mais seule une partie peut réellement changer de taux.
- **Retenu : une entrée `quote_discount_change` par ligne dont le taux a réellement
  changé** (comparaison ancien/nouveau taux, arrondie à 2 décimales pour éviter le bruit
  flottant, avant d'écrire la nouvelle valeur). Une resoumission qui ne change aucun taux
  ne crée aucune entrée — pas de bruit dans l'historique.
- Écarté : un seul événement par soumission avec le détail des lignes changées dans un
  champ JSON — plus compact à l'affichage (1 ligne au lieu de N) mais introduit un champ
  sérialisé, contraire à la convention du projet.

### Auteur : pas de cas "automatique"
- Contrairement à `QuoteStatusChange::uid` (NULL possible pour l'archivage cron), une
  remise n'a qu'un seul point d'entrée : `QuoteDiscountForm`, réservé aux administrateurs
  authentifiés (permission `edit drivematic configurator quotes`). `uid` est donc
  toujours renseigné à l'écriture, mais reste un `entity_reference` non requis pour
  absorber une suppression de compte ultérieure (repli "Compte supprimé" à l'affichage,
  jamais "Automatique").

## Decision
Nouvelle entité `quote_discount_change` (`Entity/QuoteDiscountChange.php`) : `quote_id`
(entity_reference→quote, requis), `line_id` (entity_reference→quote_equipment_line,
requis), `old_rate`/`new_rate` (decimal 5,2), `uid` (entity_reference→user, non requis),
`created` (timestamp, auto). Écrite depuis `QuoteDiscountForm::submitForm()` (nouvelle
méthode privée `logDiscountChange()`), une fois par ligne réellement modifiée, juste
avant `save()` de la ligne (comparaison de l'ancien taux chargé et du nouveau taux
soumis).

`QuoteDetailController::buildHistoryTable()` fusionne désormais deux sources
normalisées (`buildStatusHistoryEntries()`, `buildDiscountHistoryEntries()`) en un
tableau unique trié par timestamp (`usort`), plutôt que deux tableaux ou une 4e colonne
"Type" : l'en-tête "Statut" devient "Événement", chaque ligne de remise affichant
`Remise Drive Matic : « <équipement> » <ancien>% → <nouveau>%`.

## Consequences
- `drivematic_configurator.install` : 5e `hook_update_N` (`drivematic_configurator_update_11005()`),
  idempotent — `drush updb` à rejouer sur tout environnement avant déploiement.
- Fichiers impactés : `Entity/QuoteDiscountChange.php` (nouveau),
  `Form/QuoteDiscountForm.php`, `Controller/QuoteDetailController.php`,
  `drivematic_configurator.install`.
- **Limite assumée, symétrique à celle de `QuoteStatusChange`** : les remises accordées
  AVANT cette feature ne sont pas rétroactivement tracées — seule la valeur courante de
  `dm_discount_rate` existe pour elles, sans historique. On ne fabrique pas un auteur ou
  une date qu'on ne connaît pas réellement.
- Aucun nouveau handler d'accès, aucune nouvelle route, aucune nouvelle permission —
  posture cohérente avec ADR-037/038.
