# ADR-051 : Page « Mes devis » (F13, étape 2/2) — 3 onglets, correctif des compteurs du tableau de bord

## Statut

Accepté

## Date

2026-09-09

## Contexte

Étape 2 annoncée par l'ADR-046 : la page de listing `/user/mes-devis`, avec ses 3
onglets (maquettes desktop 493-14389/493-15278/493-16109, mobile
605-35196/605-36030). Périmètre de cette étape : la page, les onglets, l'affichage
des devis ligne par ligne. Le menu déroulant par ligne (3 points verticaux) reste
hors périmètre — ajouté avec l'archivage manuel partenaire prévu par l'ADR-045.

## Options considérées

### Répartition des statuts par onglet

En comparant les maquettes de cette page au code déjà livré, une divergence réelle
est apparue avec l'ADR-046 :

- **ADR-046 (tel que livré)** : compteur « en cours » = `a_commander` seul ;
  compteur « archivés » = `commande` + `archive` réunis.
- **Maquettes de cette page + demande utilisatrice** : l'onglet « en cours »
  mélange visiblement les badges `a_commander` (orange, « A commander ») ET
  `commande` (vert, « Commandé le [date] ») ; l'onglet « archivés » ne montre que
  des devis au statut `archive`.

Confirmé avec l'utilisatrice : les maquettes de cette page font foi. L'ADR-046
groupait ces statuts en anticipant une page qui n'existait pas encore — cette
anticipation s'avère fausse à l'usage.

- **Garder ADR-046 tel quel** : écarté — les 3 compteurs du tableau de bord
  afficheraient des totaux ne correspondant plus au contenu réel de chaque
  onglet (bug silencieux, visible uniquement en comparant les deux pages).
- **Aligner `DashboardController` sur le découpage réel** (retenue) : `a_finaliser`
  seul, `[a_commander, commande]` pour « en cours », `[archive]` seul pour
  « archivés ».

### Affichage du statut pour un devis archivé

Aucune maquette (desktop ou mobile) ne montre de badge « Archivé » distinct : la
maquette dédiée à cet onglet (493-16109, `LigneArchivee`) affiche le même badge
vert « Commandé le [date] » sur TOUTES ses lignes d'exemple. `QuoteDetailController::
formatStatus()` (back-office DM) ne fait au contraire aucun cas particulier pour
`STATUS_ARCHIVE` et retomberait sur le libellé brut « Archivé ».

- **Réutiliser tel quel `formatStatus()` du back-office** (badge « Archivé »
  générique) : écarté — contredit directement les 5 maquettes de cette page.
- **Étendre la même règle de date au statut archivé** (retenue) : le statut
  affiché au partenaire reste « Commandé le {date_confirmation} » pour `commande`
  ET `archive` — l'information utile pour le partenaire reste la date de
  confirmation, l'archivage n'est qu'un classement d'onglet, pas un évènement
  métier à afficher. Logique dupliquée dans `MyQuotesController` plutôt que
  mutualisée avec `QuoteDetailController::formatStatus()` (portée différente :
  page partenaire vs back-office DM, jamais rendue par le même contrôleur).

### Colonne Action (menu 3 points) pendant cette étape

- **Icône décorative en placeholder** : écartée — poserait un élément visuel
  sans aucune action derrière, contraire à la règle « pas d'implémentation à
  moitié faite » ; aurait aussi exigé d'exporter une icône `more-vertical.svg`
  qui n'existe pas encore dans le thème.
- **Omettre entièrement la colonne** (retenue) : ajoutée avec le vrai menu
  déroulant, à la prochaine étape.

### Architecture des composants

- **Un SDC par onglet** : écarté, duplication massive (3 gabarits quasi
  identiques) pour une différence qui se limite à 2 colonnes optionnelles.
- **`quote-list` (parent) + `quote-row` (enfant, slot `rows`)** (retenue) :
  même pattern que `dashboard-actions`/`dashboard-action-card` (ADR-046).
  `quote-row` prend des props optionnelles (`reference`, `amount`) pour les
  colonnes qui varient par onglet ; la même ligne s'adapte en carte empilée en
  CSS pur sous le breakpoint mobile (aucun gabarit mobile séparé).

### Format de date

Les 5 maquettes n'affichent jamais d'heure, sur aucune ligne (`jj/mm/aaaa`
partout). Le format `short` (`j M Y - H:i`, avec heure) est pourtant déjà la
convention établie pour cette même donnée ailleurs dans le projet
(`QuoteDetailController::formatDate()`, et la Vue admin `quotes`).

- **Réutiliser `short`** : écarté — contredit directement les 5 maquettes,
  qui n'ont pas varié sur ce point.
- **Format `custom` dédié, `d/m/Y`** (retenue) : `short` sert le back-office
  DM (où l'heure de création peut avoir un sens opérationnel) ; cette page
  sert le partenaire, sur une donnée qu'il consulte au jour près. Pas de
  nouvelle entité `date_format` versionnée — `DateFormatterInterface::format()`
  accepte un format `custom` inline, sans configuration supplémentaire.

### Pagination

- **Vue Drupal (comme `/admin/content/devis`)** : écartée — la page n'est pas
  bâtie sur Views (cohérent avec le choix ADR-037 pour la page de détail :
  Controller + render array).
- **`pager.manager` + `#type => 'pager'`** (retenue) : le gabarit `pager.html.twig`
  du thème est déjà calé sur ce composant exact (mesures relevées sur la
  maquette 493-14894, commentaire déjà présent dans `_pager.scss`) — rien à
  créer, seule la boucle de requête (`range()` + `pager.manager`) est nouvelle
  dans ce projet.

## Décision

- Route unique `drivematic_partner.my_quotes` (`/user/mes-devis`,
  `_role: 'partenaire'`), `?onglet=a-finaliser|en-cours|archives` (défaut
  `a-finaliser` si absent/invalide) sélectionne l'onglet actif — convention
  actée dès l'ADR-046.
- `MyQuotesController::build(Request $request)` : requête `quote` bornée à
  `uid = currentUser()`, `accessCheck(FALSE)` (même justification qu'ADR-046—
  aucune donnée individuelle exposée via un id transmis par le client, aucun
  lien vers une route de devis individuel à ce stade).
- Colonnes par onglet : « à finaliser » (Date, Marque/Modèle/Type,
  Équipement(s), Statut) ; « en cours » (+ N° devis, Montant €HT) ; « archivés »
  (Montant €HT, sans N° devis). Sur mobile, le N° devis reste absent même pour
  « en cours » (fidèle à la maquette mobile 605-36030, qui l'omet).
- Montant = `total_ht` (l'en-tête de colonne dit explicitement « Montant €HT »,
  jamais `total_ttc`). Date = `created`. Marque/Modèle/Type = une ligne par
  `quote_configuration` du devis. Équipement(s) = libellés dédupliqués de tous
  les `quote_equipment_line` du devis, joints par virgule.
- `DashboardController` corrigé : compteurs `[STATUS_A_COMMANDER,
  STATUS_COMMANDE]` pour « en cours » et `[STATUS_ARCHIVE]` seul pour
  « archivés » (`STATUS_A_FINALISER` seul inchangé) ; les 3 `href` en dur
  remplacés par `Url::fromRoute('drivematic_partner.my_quotes', [], ['query' =>
  ['onglet' => ...]])`, comme prévu par l'ADR-046 dès la création de cette page.
- Cache : `contexts => ['user', 'url.query_args:onglet',
  'url.query_args.pagers:0']` + tags de liste `quote` — sans le contexte
  `onglet`, le Dynamic Page Cache servirait le contenu du premier onglet visité
  à tous les suivants pour un même partenaire.
- Lien de menu « Mes devis » (menu `account`, contenu en base, jamais
  synchronisé) repointé vers `route:drivematic_partner.my_quotes` — même
  opération manuelle que « Tableau de bord » (ADR-046), à rejouer sur chaque
  environnement (cf. piège déjà documenté sur ce même menu).

## Conséquences

- Le tableau de bord et cette page redeviennent cohérents : le nombre affiché
  par chaque compteur correspond exactement au nombre de lignes du bon onglet.
- Un devis archivé n'affiche plus jamais son statut brut « Archivé » nulle part
  côté partenaire — seul le back-office DM (`QuoteDetailController`) le fait,
  et seulement pour l'historique des statuts, jamais pour le statut courant
  d'un devis archivé consulté depuis cette page (page absente : le partenaire
  n'a pas de vue détaillée d'un devis individuel à ce stade).
- Reste à faire (étape ultérieure) : menu déroulant par ligne (Modifier/
  Dupliquer/Supprimer sur « à finaliser » ; Commander/Modifier/Dupliquer/
  Supprimer sur « à commander » ; Dupliquer/Archiver sur « commandé » ;
  Télécharger le devis sur « archivé » — actions lues sur les maquettes mais
  non implémentées), lien vers une page de détail individuelle (nécessiterait
  alors un `QuoteAccessControlHandler` par propriétaire, cf. ADR-046).
- Fichiers créés : `drivematic_partner/src/Controller/MyQuotesController.php`,
  SDC `quote-list` et `quote-row`. Fichiers modifiés :
  `drivematic_partner.routing.yml`, `DashboardController.php`.

## Addendum du 2026-09-09 : colonne Date = création, pas dernier enregistrement

**Contexte** : la colonne « Date » lisait `created` (date de création) sur les
3 onglets. Or un devis « à finaliser » est destiné à être édité plusieurs fois
avant d'être commandé (édition pas encore construite, cf. « Reste à faire »
ci-dessus) — un devis remodifié doit remonter en tête de liste, ce que
`created` ne peut jamais refléter.

### Un seul champ générique vs deux champs à sémantique distincte

- **Un seul champ `changed`, utilisé partout** : écarté — dans « en cours »/
  « archivés », le devis n'est plus édité (le partenaire ne peut plus le
  modifier une fois commandé) mais `changed` continuerait de bouger à chaque
  action Drive Matic dessus (remise exceptionnelle, marquage « Commandé »,
  archivage) : le tri de ces 2 onglets se déréglerait sans rapport avec une
  action du partenaire.
- **`changed` (édition) + `date_comptable` (passage à « à commander »)**
  (retenue) : chacun des 3 onglets trie sur la date qui correspond à ce que le
  partenaire y fait réellement — éditer (à finaliser) ou avoir commandé (en
  cours/archivés, qui partagent la même notion de date puisqu'un devis
  archivé a nécessairement été « à commander » avant).

### `date_comptable` : nouveau champ vs réutiliser `date_commande`

`date_commande` existe déjà et est posé exactement à la même occasion
(`QuotePersister::persist()`, `$status === Quote::STATUS_A_COMMANDER`) — les
deux valent donc rigoureusement la même chose aujourd'hui.

- **Réutiliser `date_commande` pour l'affichage** : écarté — son seul autre
  rôle actuel (`QuoteDetailController::buildCreationEntry()`, déduire le
  statut initial d'un devis antérieur à l'historique des statuts) est une
  logique interne au back-office DM, sans rapport avec l'affichage partenaire ;
  les coupler aurait rendu un futur changement de l'un risqué pour l'autre.
- **Nouveau champ `date_comptable`, posé au même instant** (retenue) : les
  deux valeurs coïncident tant qu'aucun mécanisme ne fait passer un devis
  « à finaliser » déjà existant au statut « à commander » sans repasser par
  `QuotePersister` (aucun n'existe aujourd'hui) — mais restent conceptuellement
  distinctes et pourront diverger le jour où un tel mécanisme sera construit
  (le « Commander » du menu déroulant, lu sur les maquettes mais non
  implémenté).

### Population de `date_comptable` : hook générique vs point d'appel explicite

- **`hook_ENTITY_TYPE_presave()` générique sur `quote`** : écarté — aucun
  autre champ « à la transition de statut » de cette entité (`date_confirmation`,
  `date_archivage`) n'est posé de cette façon ; ils le sont chacun explicitement
  au point de code qui déclenche la transition (`QuoteMarkOrderedForm`,
  `drivematic_configurator_cron()`). Un hook générique aurait introduit un
  2e mécanisme pour le même genre de besoin, sans qu'aucun autre code de ce
  module n'en ait besoin aujourd'hui.
- **Posé explicitement dans `QuotePersister::persist()`, à côté de
  `date_commande`** (retenue) : cohérent avec le pattern déjà établi ; le futur
  mécanisme de transition (« Commander » sur un devis « à finaliser » existant)
  devra explicitement poser `date_comptable` lui aussi, exactement comme il
  devra explicitement poser une entrée `quote_status_change` — aucune surprise,
  le principe est déjà celui de toutes les transitions de ce devis.

## Décision (addendum)

- Champ `changed` (type `changed` core, auto-géré par `ChangedItem::preSave()`)
  et `EntityChangedInterface`/`EntityChangedTrait` sur `Quote` — aucune ligne
  de code supplémentaire nécessaire pour qu'il se mette à jour à chaque
  modification du devis (remise DM comprise, mais celle-ci n'a plus d'effet
  sur le tri de « à finaliser » puisqu'un devis remisé est déjà « à commander »
  à ce moment).
- Champ `date_comptable` (`timestamp`), posé dans `QuotePersister::persist()`
  en même temps que `date_commande`, jamais remis à jour ensuite.
- `MyQuotesController::build()` : le champ de tri ET la valeur affichée dans
  la colonne Date dépendent de l'onglet (`changed` pour « à finaliser »,
  `date_comptable` pour les 2 autres) — un seul point de bascule (`$date_field`),
  consommé à la fois par `->sort()` et par la construction de chaque ligne.
- 3 `hook_update_N` (`drivematic_configurator_update_11013/11014/11015`) :
  installation des 2 champs puis migration des devis déjà en base — `changed`
  initialisé à `created` (aucun historique d'édition antérieur à ce champ) ;
  `date_comptable` repris depuis la 1re entrée `quote_status_change` au statut
  `a_commander` de chaque devis (source la plus fiable de la date réelle de
  transition, ADR-038), à défaut repli sur `created`.

## Conséquences (addendum)

- Un devis « à finaliser » remodifié (une fois l'édition partenaire
  construite) remontera automatiquement en tête de cet onglet, sans code
  supplémentaire à écrire à ce moment-là.
- Le classement de « en cours »/« archivés » reste stable même si Drive Matic
  agit sur un devis après coup (remise, marquage commandé, archivage) — seule
  la date de passage à « à commander » compte, jamais retouchée par ces
  actions.
- Fichiers modifiés : `Entity/Quote.php` (champs + interface),
  `Service/QuotePersister.php`, `drivematic_configurator.install` (3 hooks),
  `MyQuotesController.php`.
