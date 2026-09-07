# ADR-047 : Bloc titre de page tolérant aux routes hors node

## Statut

Accepté

## Date

2026-09-07

## Contexte

En construisant le tableau de bord partenaire (ADR-046), le `<h1>` « Tableau
de bord » (bloc `drive_matic_page_title`) ne s'affichait pas du tout. Cause
identifiée avec certitude dans le code core (pas une supposition) :
`\Drupal\block\BlockAccessControlHandler::checkAccess()` refuse
INCONDITIONNELLEMENT l'accès au bloc dès qu'une condition de visibilité a un
contexte présent mais dont la VALEUR est NULL (`MissingValueContextException`)
— **quel que soit `negate`**. Le commentaire du core cite explicitement ce
cas : « the node type condition will have a missing context on any non-node
route ». La condition `entity_bundle:node` (niée) posée sur ce bloc tombe
systématiquement dans ce cas sur toute route de contrôleur/formulaire custom
(aucun node dans le contexte de route), y compris des pages déjà existantes
(`/user/mes-informations-personnelles`).

ADR-027 avait déjà rencontré exactement ce symptôme et choisi de **ne pas**
toucher à cette condition partagée, par crainte de faire apparaître un `<h1>`
sur des routes où ce n'est ni demandé ni vérifié (`/user/login`,
`/user/password`, `/user/{uid}/edit`) — préférant un correctif scopé à
`/user/logout/confirm` seul (`hook_form_FORM_ID_alter()` + `$form['#title']`).

Cette session, l'utilisatrice a explicitement demandé de corriger la
condition partagée (bénéfice pour le tableau de bord ET
`/user/mes-informations-personnelles`) plutôt que de contourner localement
— **avec la conséquence vérifiée** que `/user/login` affichait alors un
`<h1>` « Se connecter », contraire à ADR-024 (le composant `login-panel`
doit porter seul tout le poids visuel de cette page).

## Options considérées

### Corriger `entity_bundle:node` directement

Impossible sans modifier le core (`\Drupal\Core\Entity\Plugin\Condition\
EntityBundle` et le comportement de `BlockAccessControlHandler` sont tous
deux dans `web/core/`, jamais modifiés directement).

### Nouveau plugin Condition tolérant, dans un module custom

Retenue. Un plugin qui ne déclare AUCUN contexte de plugin (résout le node
directement via `current_route_match`, sans passer par le système de
contexte de Drupal) ne peut jamais tomber dans le cas
`MissingValueContextException` : `evaluate()` retourne `FALSE` (jamais un
des bundles exclus) quand il n'y a pas de node, exactement le comportement
voulu par la négation.

**Placement** : d'abord tenté dans le thème `drive_matic` (`src/Plugin/
Condition/`, même dossier `src/` que `Hook/DriveMaticHooks.php`, déjà
fonctionnel). **Écarté après vérification empirique** :
`DefaultPluginManager::providerExists()` (core) filtre silencieusement toute
définition de plugin dont le `provider` n'est ni `core`/`component` ni un
**module** activé (`moduleHandler->moduleExists()`) — un thème ne passe
jamais ce test, quelle que soit la validité de sa découverte PSR-4 (confirmée
directement via `AttributeClassDiscovery`, qui trouvait bien le plugin ; seul
le filtrage en aval de `ConditionManager` l'excluait). Un plugin Condition
DOIT donc vivre dans un module, jamais dans un thème seul.

Nouveau module minimal `drivematic_page_title` (aucun module existant ne
correspondait sémantiquement : `drivematic_forms` est scopé aux
comportements de formulaires webform, pas à la visibilité de blocs
site-wide).

### Concilier avec ADR-024/027 (routes où le titre doit rester absent)

- **Exclure ces routes dans le NOUVEAU plugin lui-même** : écarté, mélange
  deux responsabilités (bundle de node ET liste de routes) dans un seul
  plugin.
- **2e condition en ET, `request_path` (core) niée** (retenue) : condition
  standard déjà fournie par core, combinée en ET (comportement par défaut
  des conditions de visibilité de bloc) à la nouvelle condition de bundle.

## Décision

1. Nouveau module `drivematic_page_title`, plugin `Plugin/Condition/
   NodeBundleCondition.php` (id `drivematic_node_bundle`) : remplacement
   direct de `entity_bundle:node`, même forme de configuration (`bundles`,
   checkboxes des types de contenu), mais résout le node via
   `current_route_match` plutôt que via le système de contexte de plugin.
2. Bloc `drive_matic_page_title` : condition `entity_bundle:node` remplacée
   par `drivematic_node_bundle` (mêmes `bundles`, même `negate`) + nouvelle
   condition `request_path` niée sur `/user/login`, `/user/logout/confirm`,
   `/user/password` — restaure exactement le comportement voulu par
   ADR-024/027 sur ces 3 routes, tout en corrigeant le bug partout ailleurs.
3. Édité via l'API d'entité (`drush php:eval`, chargement/`set('visibility',
   ...)`/`save()`) puis exporté (`drush cex`) — jamais un YAML modifié à la
   main pour un config entity.

## Conséquences

- **Positif** : `/user/tableau-de-bord` et `/user/mes-informations-
  personnelles` affichent désormais leur `<h1>` ; toute future route de
  contrôleur/formulaire custom en bénéficiera automatiquement, sans
  nouvelle exclusion à ajouter (sauf si elle doit explicitement rester
  sans titre, auquel cas l'ajouter à la liste `request_path`).
- **Vérifié sans régression** : homepage (1 seul `<h1>`, porté par le
  paragraphe hero), `/faq` (1 `<h1>` via le bloc), `/user/login` (0 `<h1>`),
  `/user/password` (0 `<h1>`), `/user/logout/confirm` (0 `<h1>`).
- `/user/{uid}/edit` (route core, distincte de `/user/mes-informations-
  personnelles`) n'était listée par ADR-027 que comme exemple de route
  affectée par l'ANCIEN bug, jamais comme routes devant explicitement rester
  sans titre — non ajoutée à l'exclusion `request_path`. Un titre y
  apparaît désormais (amélioration probable, non vérifiée visuellement) ;
  à surveiller si une maquette existait pour cette route admin.
- Toute future route qui NE DOIT PAS montrer de titre devra être ajoutée à
  la liste `request_path` du bloc `drive_matic_page_title` — un seul
  endroit à vérifier/modifier désormais (au lieu du contournement par route,
  pattern ADR-027, qui reste valable mais devient optionnel).
- Fichiers créés : `drivematic_page_title.info.yml`, `src/Plugin/Condition/
  NodeBundleCondition.php`. Fichier modifié : `config/sync/block.block.
  drive_matic_page_title.yml`, `config/sync/core.extension.yml`.

## Addendum (même jour) : régression double titre sur `/configurer/*`

Signalé par l'utilisatrice après déploiement : `/configurer` (et par
extension tout le configurateur) affichait désormais **deux** `<h1>`. Cause
directement liée à ce correctif, pas anticipée à l'écriture initiale : 6
`FormBase` du module `drivematic_configurator` (`ConfigurationForm`,
`QuoteForm`, `DeliveryForm`, `QuoteConfigurationDeleteForm`,
`DeliveryAddressForm`, `DeliveryAddressDeleteForm`) portent chacun un
`<h1 class="page-title">` posé **manuellement** dans leur `buildForm()`
(`$form['#prefix']`) — un contournement de l'ANCIEN bug (bloc de titre
absent sur toute route hors node), écrit avant ce correctif. Le bloc
partagé fonctionnant désormais aussi sur ces routes, les deux titres
coexistent.

**Pourquoi ne pas simplement supprimer ces `<h1>` manuels** : 4 des 6
(`QuoteForm`, `DeliveryForm`, `QuoteConfigurationDeleteForm`,
`DeliveryAddressDeleteForm`) affichent un texte **délibérément différent**
du `_title` de leur route — ex. les 3 écrans du configurateur partagent
tous le même titre « Configurez votre véhicule et obtenez votre tarif »
par décision utilisatrice, alors que leurs routes ont chacune un `_title`
technique distinct (« Votre devis », « Livraison »...), utilisé pour
l'onglet du navigateur et le fil d'Ariane. Supprimer le `<h1>` manuel et
laisser le bloc partagé prendre le relais aurait donc changé le texte
affiché — une régression silencieuse, pas une simplification neutre.

**Solution retenue** : ajouter `/configurer` et `/configurer/*` à la même
condition `request_path` niée que `/user/login`/`/user/password`/
`/user/logout/confirm` — tous les `_form` de ce module vivent sous ce
préfixe, aucune route du module n'est hors de ce périmètre (vérifié :
`entity.quote.canonical`, `quote_pdf` et `quote_mark_ordered` sont sous
`/admin/content/devis/*`, non concernées). Un seul couple de lignes couvre
les 7 routes concernées, plutôt que de les lister une par une.

**Réflexe pour toute future route custom** : avant d'ajouter une nouvelle
route de contrôleur/formulaire, vérifier si un `<h1 class="page-title">`
(ou équivalent) est déjà posé manuellement dans son `buildForm()`/render
array — signe d'un contournement de l'ancien bug, à traiter par exclusion
`request_path` plutôt que par suppression si le texte affiché diverge du
`_title` de la route.
