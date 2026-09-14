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

## Addendum (2026-09-14, suite) : redirection après archivage

Décision initiale reversée sur demande explicite de l'utilisatrice :
`QuoteArchiveForm` redirigeait vers l'onglet « en cours » (d'où l'action
avait été lancée). Redirige désormais vers l'onglet **« archives »**, où le
devis vient de basculer — plus cohérent pour le partenaire, qui voit
directement où son devis a atterri.

- `getCancelUrl()` (onglet « en cours ») reste inchangée : toujours utilisée
  pour le bouton « Non » et le lien de retour de l'état dégradé (devis plus
  au statut « Commandé »), aucun des deux cas n'ayant réellement archivé
  quoi que ce soit.
- Nouvelle méthode privée `getArchivedUrl()` (onglet « archives »), utilisée
  uniquement pour les 2 redirections de succès (`submitForm()` non-AJAX,
  `ajaxSubmit()` — seul chemin réellement emprunté par le bouton).
- Vérifié via curl (AJAX réel) : la réponse contient
  `{"command":"redirect","url":"/user/mes-devis?onglet=archives"}`, le devis
  apparaît bien sur cet onglet juste après.

## Conséquences

- Hors périmètre (inchangé, cf. addendum ci-dessous) : le menu de l'onglet
  « archivés ».
- Un devis `a_commander` reste sans action d'archivage manuel ou de
  finalisation depuis cette page (seul Drive Matic peut le faire passer à
  « Commandé », back-office) — conforme à ADR-045, qui a explicitement
  retiré tout archivage automatique/manuel sur ce statut.
- Fichiers créés : `Form/QuoteArchiveForm.php`. Fichiers modifiés :
  `drivematic_configurator.routing.yml`, `MyQuotesController.php`, SDC
  `quote-row-actions` (component.yml/twig/scss).

## Addendum (2026-09-14) : « Télécharger le devis »

Demande explicite de l'utilisatrice : ajouter « Télécharger le devis » aux
deux statuts de l'onglet « en cours » (`a_commander` ET `commande`), pas
seulement à « commandé ». Libellé/icône repris de la maquette 493-16109
(onglet « archivés », composant `671:20787`, seule occurrence de cette
action dans le fichier Figma) — glyphe `Download` identique à l'asset local
`images/icons/download.svg` déjà présent (variante lucide, viewBox 16 au
lieu de 20, même tracé), réutilisé tel quel.

- Nouveau `QuotePdfDownloadController::download()` (`drivematic_configurator`,
  même module que `QuoteDetailController`) : sert le même fichier que
  `QuoteDetailController::pdf()` (back-office, `QuotePdfGenerator::getUri()`)
  mais en `DISPOSITION_ATTACHMENT` au lieu d'`INLINE` — le libellé partenaire
  appelle un enregistrement direct, pas une ouverture inline. Route dédiée
  `drivematic_configurator.quote_pdf_download`
  (`/user/mes-devis/{quote}/telecharger`, `_entity_access: 'quote.view'`,
  espace URL partenaire plutôt que `/admin/...`) plutôt que de réutiliser la
  route admin existante.
- Pas de modale ni de `_csrf_token` : lecture seule, sans effet de bord,
  contrairement à Dupliquer (écriture, protégé) ou Archiver (modale de
  confirmation).
- `MyQuotesController::buildActions()` : `download_href` fourni
  inconditionnellement sur l'onglet « en cours » (les deux statuts), sauf si
  `file_exists($pdfGenerator->getUri($quote))` est faux (même garde-fou que
  `QuoteDetailController::view()` côté back-office) — cas résiduel pour un
  devis antérieur à la génération du PDF ou une génération ayant échoué.
- Ordre du menu (aucune maquette ne combine les 3 actions) : Dupliquer,
  Télécharger le devis, Archiver — les deux actions communes aux deux
  statuts groupées avant l'action conditionnelle.
- Vérifié via curl (partenaire réel, 5 devis « Commande en cours ») : lien
  présent sur les 5 lignes, téléchargement renvoie un PDF valide
  (`Content-Disposition: attachment`, en-tête/xref/trailer PDF corrects).
- Fichiers créés : `Controller/QuotePdfDownloadController.php`. Fichiers
  modifiés : `drivematic_configurator.routing.yml`, `MyQuotesController.php`,
  SDC `quote-row-actions` (component.yml/twig/scss).

## Addendum (2026-09-14, suite) : menu de l'onglet « archivés »

Dernier onglet sans menu (cf. « Hors périmètre » ci-dessus) : demande
explicite de l'utilisatrice, une seule option, « Télécharger le devis »
(même route/logique que l'addendum précédent — aucun nouveau code serveur,
juste une 3e branche dans `MyQuotesController::buildActions()`).

- `show_actions` (prop de `quote-list`, réservation de la colonne d'en-tête
  pour le menu, cf. le fix d'alignement du même jour) passe désormais à `TRUE`
  inconditionnellement : les 3 onglets peuvent potentiellement afficher des
  actions, la colonne doit toujours être réservée.
- **Cas limite accepté, documenté en commentaire** : si le PDF d'un devis
  archivé est absent (jamais généré, ou légataire d'avant cette
  fonctionnalité — la purge à 2 ans d'ADR-053 n'a physiquement pas encore pu
  jouer), `buildActions()` renvoie `NULL` et la ligne n'a alors AUCUNE
  cellule « actions » — contrairement aux autres lignes du même onglet, qui
  en ont une. Cette ligne précise se désaligne (Équipement(s)/Statut/Montant)
  puisque `show_actions` réserve la colonne pour l'onglet entier, pas ligne
  par ligne. Aucun devis réel dans ce cas aujourd'hui : pas de plomberie
  supplémentaire (un `show_actions` par ligne, pas seulement par onglet) tant
  que ça reste théorique.
- Fichier modifié : `MyQuotesController.php` uniquement (aucun nouveau
  fichier, réutilise entièrement `QuotePdfDownloadController`/
  `drivematic_configurator.quote_pdf_download` de l'addendum précédent).
