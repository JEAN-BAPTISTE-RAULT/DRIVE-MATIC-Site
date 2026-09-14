# ADR-057 : Menu d'actions « Mes devis / commandes en cours » (Dupliquer, Archiver)

## Statut

Accepté

## Date

2026-09-14

## Contexte

ADR-052 avait volontairement laissé hors périmètre le menu 3 points de
l'onglet « en cours » de `/user/mes-devis` (mélange `a_commander` +
`commande`, ADR-051), en attendant l'archivage manuel partenaire annoncé par
ADR-045. Cette étape l'implémente, maquette 493-15278.

## Écart assumé par rapport à la maquette

La maquette montre deux variantes du menu ouvertes côte à côte à titre
indicatif : une à 2 lignes (Dupliquer/Archiver, sur une ligne « Commandé »)
et une à 4 lignes (Commander/Modifier/Dupliquer/Supprimer). **Décision de
l'utilisatrice** : seule la variante à 2 lignes est implémentée, et
uniquement pour un devis « Commandé » —

- `a_commander` (« Commande en cours ») : **Dupliquer seul**. Pas de
  Commander/Modifier/Supprimer sur ce statut : action délibérément retirée
  du périmètre (contredit la maquette, retenu tel quel).
- `commande` (« Commandé le JJ/MM/AAAA ») : **Dupliquer + Archiver**.

## Décision

- `MyQuotesController::buildActions()` étendu à l'onglet `en-cours` : lit le
  statut réel de chaque ligne (`a_commander` vs `commande`) pour décider si
  `archive_href` est fourni au SDC `quote-row-actions` (toujours vide pour
  `a_commander`). Prend désormais `$date_field` en paramètre (au lieu de lire
  `changed` en dur) : le libellé accessible du menu doit citer la date
  affichée par CETTE ligne, `date_comptable` sur cet onglet (ADR-051
  addendum), pas `changed`.
- `Dupliquer` réutilise tel quel `QuoteDuplicateController`/
  `drivematic_configurator.quote_duplicate` (ADR-052) : générique, ne
  suppose aucun statut source, redirige déjà vers l'onglet « à finaliser ».
  Aucune modification nécessaire.
- Nouveau : `QuoteArchiveForm` (`drivematic_configurator.quote_archive`,
  `/user/mes-devis/{quote}/archiver`, `_entity_access: 'quote.update'`) —
  même modèle que `QuoteDeleteForm` (`ConfirmFormBase` + `ModalRequestTrait`,
  dialogue Drupal core). Re-vérifie côté serveur que le devis est bien
  `STATUS_COMMANDE` (sinon `AccessDeniedHttpException`, même réflexe que
  `QuoteMarkOrderedForm`) : passe à `STATUS_ARCHIVE`, pose `date_archivage`,
  crée une entrée `quote_status_change` avec `uid` = le partenaire courant
  (distincte de l'archivage automatique du cron, `uid` absent). Redirige
  vers l'onglet « en cours » (celui d'où l'action a été lancée — le devis y
  disparaît simplement, même logique que `QuoteDeleteForm` sur « à
  finaliser »).
- SDC `quote-row-actions` : nouveau prop optionnel `archive_href` (icône
  `archive.svg`, déjà présente dans `images/icons/`), même gabarit
  générique que les autres actions (aucun changement de structure/CSS de
  liste, juste un item de plus).

## Conséquences

- Hors périmètre (inchangé) : le menu de l'onglet « archivés »
  (« Télécharger le devis »).
- Un devis `a_commander` reste sans action d'archivage manuel ou de
  finalisation depuis cette page (seul Drive Matic peut le faire passer à
  « Commandé », back-office) — conforme à ADR-045, qui a explicitement
  retiré tout archivage automatique/manuel sur ce statut.
- Fichiers créés : `Form/QuoteArchiveForm.php`. Fichiers modifiés :
  `drivematic_configurator.routing.yml`, `MyQuotesController.php`, SDC
  `quote-row-actions` (component.yml/twig/scss).
