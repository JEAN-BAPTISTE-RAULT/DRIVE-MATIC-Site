# ADR-053 : Purge automatique des PDF de devis (2 ans)

## Statut

Accepté

## Date

2026-09-10

## Contexte

Les PDF de devis (ADR-041, `private://devis-pdf/{reference}.pdf`) s'accumulent
indéfiniment : aucun mécanisme ne les supprime, y compris pour un devis très
ancien. L'utilisatrice a demandé une purge automatique des fichiers vieux de
plus de 2 ans.

## Options considérées

### Option A : purge fondée sur la date du fichier (mtime)

- Avantages : simple, indépendante des entités `Quote` — un pur nettoyage
  disque.
- Inconvénients : un PDF est **régénéré** (fichier écrasé, mtime remis à
  zéro) à chaque remise Drive Matic accordée sur le devis (ADR-041) — un
  devis très ancien mais récemment remisé ne serait alors jamais purgé, sans
  rapport avec son âge réel. Incohérent avec le reste du cycle de vie du
  devis, qui se pilote toujours par une date métier (`hook_cron()` existant,
  archivage à J+30 depuis `date_confirmation`).

### Option B : purge fondée sur `Quote::date_confirmation` (retenue)

- Avantages : même date de référence que l'archivage automatique déjà en
  place (cohérence du cycle de vie) ; insensible aux régénérations du
  fichier ; un devis jamais confirmé (`date_confirmation` NULL) n'est
  jamais concerné, sans condition supplémentaire à écrire (NULL exclu
  naturellement par la comparaison SQL).
- Inconvénients : nécessite de requêter les entités `Quote` plutôt que de
  simplement lister un répertoire — négligeable, le volume de devis reste
  modeste et `hook_cron()` interroge déjà cette même entité juste au-dessus.

## Décision

Option B. Un devis dont `date_confirmation` dépasse 2 ans (730 jours, même
approximation sans année bissextile que le seuil de 30 jours existant) voit
son PDF supprimé par `_drivematic_configurator_purge_old_quote_pdfs()`,
appelée depuis `hook_cron()` (`drivematic_configurator.module`) juste après
l'archivage automatique existant. Aucun filtre de statut nécessaire : par
construction, un devis remplissant ce critère est déjà archivé depuis
longtemps (2 ans étant très supérieur au délai d'archivage de 30 jours).

Seul le **fichier** est supprimé (`QuotePdfGenerator::delete()`, nouvelle
méthode) — l'entité `Quote` et tout son historique restent intacts.
`QuoteDetailController::view()` n'affiche déjà le lien « Voir le PDF du
devis » que si le fichier existe (`file_exists()`) : aucune adaptation
d'affichage nécessaire, le lien disparaît de lui-même après purge.

Un échec de suppression sur un devis (permissions disque...) est journalisé
(`\Drupal::logger()`) mais n'interrompt jamais le traitement des devis
suivants — même réflexe défensif que les autres opérations non bloquantes
du module (envoi d'e-mail, génération de PDF à la commande).

## Conséquences

- Irréversible : aucune sauvegarde des PDF n'existe par ailleurs — une fois
  purgé, un PDF ne peut être régénéré que si le devis repasse par une action
  qui déclenche `QuotePdfGenerator::generate()` (aujourd'hui : uniquement
  une remise Drive Matic sur un devis « Commande en cours », impossible sur
  un devis déjà archivé — un PDF purgé est donc définitivement perdu).
- Fichiers impactés : `QuotePdfGenerator.php` (nouvelle méthode `delete()`),
  `drivematic_configurator.module` (`hook_cron()` étendu, nouvelle fonction
  `_drivematic_configurator_purge_old_quote_pdfs()`).
- Le seuil (2 ans) est en dur (même convention que le seuil de 30 jours
  existant) — à faire évoluer ici si la durée de conservation devait
  changer.
