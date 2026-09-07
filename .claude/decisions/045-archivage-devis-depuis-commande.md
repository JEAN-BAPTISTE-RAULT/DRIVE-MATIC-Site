# ADR-045 : Archivage automatique depuis « Commandé », suppression de l'archivage manuel BO

## Statut

Accepté

## Date

2026-09-07

## Contexte

ADR-038 avait posé un archivage automatique à J+30 depuis `STATUS_A_COMMANDER`
(nettoyage des devis jamais confirmés par Drive Matic), avec un archivage
manuel BO restreint au même statut. Retour utilisatrice, en préparation du
tableau de bord partenaire (F13) :

1. Le libellé du statut `a_commander` (constante inchangée) devient
   « Commande en cours » (était « À commander ») — cohérent avec les
   libellés déjà choisis pour les compteurs du tableau de bord (« Mes devis /
   commandes en cours »).
2. L'archivage automatique doit désormais partir de `STATUS_COMMANDE` (pas
   `STATUS_A_COMMANDER`), 30 jours après `date_confirmation` — délai fixe,
   sans mécanisme de report (aucune action ne modifie plus un devis une fois
   « Commandé »).
3. Drive Matic perd toute possibilité d'archiver un devis manuellement
   depuis le back-office. Seul le partenaire pourra le faire, depuis son
   tableau de bord — UI à construire dans une session ultérieure (page
   `/user/mes-devis`), hors périmètre de cet ADR.

## Options considérées

### Règle d'archivage auto : garder les deux règles vs remplacer

- **Garder les deux** (l'ancienne sur « Commande en cours » + la nouvelle
  sur « Commandé ») : filet de sécurité à chaque étape du cycle de vie.
  Écarté à la demande explicite de l'utilisatrice.
- **Remplacer** (retenue) : un devis qui reste indéfiniment « Commande en
  cours » sans confirmation Drive Matic n'a désormais plus aucun mécanisme
  de nettoyage automatique — accepté explicitement comme conséquence
  connue, pas un oubli.

### Archivage manuel BO : masquer le lien vs retirer la capacité serveur

- **Masquer seulement le lien** (`QuoteDetailController::buildActions()`) :
  écarté — laisse la route/le formulaire/la permission fonctionnels,
  contraire à la règle du projet de toujours re-vérifier l'autorisation
  côté serveur (masquer un lien Twig n'est pas un contrôle d'accès).
- **Suppression complète** (retenue) : route `drivematic_configurator.
  quote_archive` retirée, `QuoteArchiveForm` supprimé, lien « Archiver »
  retiré du contrôleur de détail.

## Décision

- Libellé `Quote::STATUS_A_COMMANDER` → « Commande en cours » (2 copies
  d'`allowed_values` à resynchroniser, `Quote.php` et `QuoteStatusChange.php`
  — dette déjà documentée par ADR-038/`QuoteStatusChange`), + libellé du
  filtre Views (`views.view.quotes.yml`, `group_items`).
- `drivematic_configurator_cron()` réécrit : `condition('status',
  Quote::STATUS_COMMANDE)` + `condition('date_confirmation', $threshold,
  '<=')`, au lieu de `STATUS_A_COMMANDER` + `date_commande`. Même seuil (30
  jours, littéral, inchangé), même écriture de `date_archivage` et entrée
  `quote_status_change` (`uid` absent = automatique).
- `QuoteArchiveForm.php` supprimé (fichier + route). Le seul reliquat de
  `date_commande` comme point de départ d'un délai (remise DM qui le
  remettait à l'heure courante, `QuoteDiscountForm`) est retiré : il ne
  servait qu'à alimenter l'ancienne règle, devenue sans objet — `date_commande`
  garde son seul autre rôle (déduire le statut initial d'un devis antérieur
  à l'historique, `QuoteDetailController::buildCreationEntry()`).
- Messages d'erreur utilisateur (`QuoteMarkOrderedForm`, `QuoteDiscountForm`)
  mis à jour pour citer « Commande en cours » au lieu de « À commander ».
- `.install` (historique de migration `en_cours` → `a_commander`) laissé
  intact : décrit un renommage déjà exécuté, pas l'état courant.

## Conséquences

- **Un devis qui reste indéfiniment « Commande en cours »** (jamais
  confirmé par téléphone par Drive Matic) n'est plus jamais archivé
  automatiquement, et Drive Matic ne peut plus l'archiver manuellement non
  plus — seul un futur archivage manuel côté partenaire (page
  `/user/mes-devis`, à construire) pourra le clore. Gap connu, accepté.
- Impact réel sur les devis déjà en base au déploiement : tout devis
  actuellement « Commande en cours » de plus de 30 jours, éligible à
  l'ancienne règle, ne sera PLUS archivé au prochain cron ; tout devis déjà
  « Commandé » de plus de 30 jours (`date_confirmation`) DEVIENDRA éligible
  et sera archivé dès le prochain cron après déploiement — à vérifier avant
  mise en préprod/prod si des devis réels existent déjà dans ces états.
- Fichiers impactés : `Entity/Quote.php`, `Entity/QuoteStatusChange.php`,
  `drivematic_configurator.module` (`hook_cron()`), `drivematic_configurator.
  routing.yml` (route retirée), `Controller/QuoteDetailController.php`,
  `Form/QuoteMarkOrderedForm.php`, `Form/QuoteDiscountForm.php`,
  `Form/QuoteArchiveForm.php` (supprimé), `views.view.quotes.yml`.
- Le futur chantier « page `/user/mes-devis` » devra construire l'archivage
  manuel partenaire (menu déroulant sur une ligne de devis « Commandé »,
  confirmé par l'utilisatrice) — aucun pattern serveur n'existe encore côté
  `drivematic_partner` pour modifier un statut de devis (vérifié : seule la
  création via `QuotePersister` touche `status` côté partenaire).
