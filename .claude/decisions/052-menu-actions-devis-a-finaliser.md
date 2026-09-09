# ADR-052 : Menu d'actions « Mes devis à finaliser » (Modifier/Dupliquer/Supprimer)

## Statut

Accepté

## Date

2026-09-09

## Contexte

ADR-051 différait volontairement le menu 3 points par ligne de devis. Cette
étape l'implémente pour l'onglet « à finaliser » (maquette 493-14389),
3 actions : Modifier, Dupliquer, Supprimer.

Exploration préalable (voir rapport détaillé dans la session) : le
configurateur (`ConfigurationForm`/`QuoteForm`/`DeliveryForm`) fonctionne
exclusivement sur un brouillon `PrivateTempStore` dont la structure exige des
**tid de taxonomie** pour marque/modèle/motorisation — or `QuoteConfiguration`
ne garde que des **libellés gelés** (ADR-033, jamais de référence vivante).
Aucun code existant ne « rejoue » un devis persisté dans ce brouillon.
`QuotePersister` ne sait aujourd'hui que **créer**, jamais mettre à jour.
`Quote` ne référence pas non plus l'entité `DeliveryAddress` utilisée
(seulement ses champs figés).

## Clarification de règle métier (précise ADR-043, ne le contredit pas)

**Décision de l'utilisatrice** : un devis « à finaliser » n'est pas encore
figé au sens d'ADR-043 — ses prix se réactualisent à chaque édition
(catalogue + remise partenaire courants), y compris à l'édition finale avant
validation. Le gel des prix (ADR-043 : *« un devis est un instantané figé à
sa création »*) ne s'applique qu'à partir du statut `a_commander` inclus
(« commande en cours », « commandé le… », « archivé ») — jamais avant.
S'applique à Modifier ET Dupliquer : les deux recalculent via
`QuoteCalculator` avec le catalogue/la remise du jour, jamais une copie des
prix gelés du devis source.

## Options considérées

### Reconstruire le brouillon : résoudre les tid depuis les libellés vs nouveau mécanisme

- **Stocker aussi les tid sur `quote_configuration`** (en plus des libellés) :
  écarté — réintroduirait une référence vivante que ADR-033 a explicitement
  écartée (un terme renommé/déplacé changerait rétroactivement un devis déjà
  créé), pour un bénéfice qui ne sert que cette seule fonctionnalité.
- **Résoudre les tid à la volée depuis les libellés gelés, au moment de
  Modifier/Dupliquer** (retenue) : pas de nouveau champ sur
  `quote_configuration`. Stratégie : chercher le terme `vehicle_model` par
  nom, vérifier que son `field_brand` correspond au `vehicle_brand` gelé
  (désambiguïse un homonyme), puis chercher `motorisation` par nom (vocabulaire
  fermé à 4 valeurs, ADR-003) — nouveau service `QuoteVehicleTermResolver`.

### Terme introuvable (renommé/supprimé depuis la création)

**Décision de l'utilisatrice** : la configuration concernée est retirée du
devis reconstruit (pas seulement laissée vide) ; un message est affiché :
*« Une ou plusieurs de vos configurations ont dû être supprimées en raison
d'un changement du catalogue. Pour toute question, n'hésitez pas à nous
contacter. »*, lien vers `/contact` (résolu via `Url::fromUserInput()`, pas
en dur — c'est un alias de node, pas une route fixe). Si **toutes** les
configurations d'un devis échouent : Dupliquer est refusé avec un message
d'erreur (rien à dupliquer) ; Modifier redirige quand même vers l'étape 3,
qui affiche déjà nativement son état « Aucun devis en cours » quand le
brouillon est vide (`DeliveryForm::buildForm()`).

### Resauvegarde après Modifier : mise à jour en place vs suppression+recréation

**Décision de l'utilisatrice** : mise à jour en place. Le devis garde son
`id`/`reference`/`created` ; `QuotePersister::update()` (nouveau) supprime les
anciennes `quote_configuration`/`quote_equipment_line` et recrée les nouvelles
depuis le résultat `QuoteCalculator` recalculé, mêmes conventions de gel que
`persist()` pour les champs `billing_*`/`delivery_*` (recopiés depuis le
compte/l'adresse **au moment de cette mise à jour**, cohérent avec la
clarification de règle ci-dessus). `changed` se met à jour automatiquement
(champ `changed`, ADR-051 addendum) — un devis modifié remonte en tête de
l'onglet « à finaliser » sans code supplémentaire.

### Adresse de livraison à la reprise/duplication

`Quote` ne référence aujourd'hui aucune `DeliveryAddress` (ADR-033 : seuls les
champs `delivery_*` figés existent). L'utilisatrice veut la **même** adresse
systématiquement présélectionnée à la reprise/duplication, pas une
correspondance approximative.

- **Correspondance heuristique sur les champs figés** : écartée par
  l'utilisatrice — pas garanti exact (deux adresses aux champs identiques,
  ou adresse modifiée depuis).
- **Nouveau champ `delivery_address_id` (entity_reference) sur `Quote`**
  (retenue), posé par `QuotePersister` en plus des champs figés existants,
  jamais à leur place. Ne contredit pas ADR-033 : les champs `delivery_*`
  figés restent l'unique source pour tout affichage/PDF/e-mail, cette
  référence ne sert qu'à cibler quelle entité présélectionner à l'écran
  Livraison lors d'une reprise/duplication — jamais relue pour autre chose.
  Si l'entité référencée a été supprimée depuis (partenaire qui supprime une
  de ses adresses) : repli sur le comportement actuel de
  `DeliveryForm::buildAddressSelector()` (1re adresse). Aucune valeur pour les
  devis déjà en base avant ce champ (pas de correspondance rétroactive
  fiable) — même repli.

## Décision

### Nouveaux champs

- `Quote::delivery_address_id` (`entity_reference` → `delivery_address`),
  posé par `QuotePersister::persist()` ET `::update()`.

### Nouveau contrôle d'accès

- `QuoteAccessControlHandler` (mêmes principes que
  `DeliveryAddressAccessControlHandler`) : `checkAccess()` délègue d'abord à
  `parent::checkAccess()` (préserve l'`admin_permission` déjà déclaré sur
  `Quote`, back-office DM inchangé), sinon exige `uid` == utilisateur courant
  pour `view`/`update`/`delete`. Déclaré dans l'attribut `#[ContentEntityType]`
  de `Quote`.

### Nouveaux services

- `QuoteVehicleTermResolver` : résout tid depuis libellés gelés (voir
  ci-dessus), retourne `NULL` par configuration en cas d'échec.
- `QuoteDraftBuilder` : construit le tableau `card.vehicle.*`/`card.equipment.*`
  (structure exacte lue par `ConfigurationForm`/`QuoteCalculator`) depuis les
  `quote_configuration`/`quote_equipment_line` d'un devis, en s'appuyant sur
  `QuoteVehicleTermResolver` ; retourne aussi la liste des configurations
  écartées (pour le message d'avertissement).

### Nouvelles routes/contrôleurs (module `drivematic_configurator`, où vivent
déjà `Quote`/`QuotePersister`/les autres formulaires de devis)

- `drivematic_configurator.quote_modify` (`/user/mes-devis/{quote}/modifier`,
  `_entity_access: 'quote.update'`) : vérifie `status === STATUS_A_FINALISER`,
  construit le brouillon, l'écrit dans le tempstore existant (`draft`) + une
  nouvelle clé `editing_quote_id`, redirige vers
  `drivematic_configurator.delivery`.
- `drivematic_configurator.quote_duplicate` (`/user/mes-devis/{quote}/dupliquer`,
  `_entity_access: 'quote.view'`, action en un clic, `_csrf_token: 'TRUE'`) :
  construit le brouillon, recalcule via `QuoteCalculator` (compte courant),
  résout l'adresse (`delivery_address_id` si l'entité existe encore, sinon 1re
  adresse), appelle `QuotePersister::persist()` (nouveau devis, `a_finaliser`),
  redirige vers l'onglet « à finaliser ».
- `QuoteDeleteForm` (`drivematic_configurator.quote_delete`,
  `/user/mes-devis/{quote}/supprimer`, `_entity_access: 'quote.delete'`) :
  même modèle que `DeliveryAddressDeleteForm` (`ConfirmFormBase` +
  `ModalRequestTrait`, `use-ajax`/`data-dialog-type: modal`, CSS `_dialog.scss`
  déjà en place) — supprime le devis et ses `quote_configuration`/
  `quote_equipment_line`.

### `DeliveryForm`

- Lit `editing_quote_id` du tempstore : si présent,
  `buildAddressSelector()` présélectionne l'adresse dont l'id correspond au
  `delivery_address_id` du devis en cours d'édition (repli sur le
  comportement actuel si absent/supprimée) ; `persistQuote()` appelle
  `QuotePersister::update($quote, ...)` au lieu de `::persist()` ; la clé
  `editing_quote_id` est supprimée du tempstore avec `draft` à la fin.

### Interface

- `quote-row` (SDC, ADR-051) : nouveau slot `actions` (menu 3 points),
  rendu uniquement pour les lignes au statut `a_finaliser` à cette étape.
  Nouveau comportement JS vanilla (`Drupal.behaviors`, `once()`) pour l'ouverture/
  fermeture du menu — aucun précédent de ce type de menu dans le projet.
- `MyQuotesController` attache `core/drupal.dialog.ajax` (sans quoi le lien
  « Supprimer » dégraderait silencieusement en navigation page complète —
  piège déjà documenté, ADR-034 addendum 2).

## Conséquences

- Cas limite accepté : un devis créé avant ce chantier n'a pas de
  `delivery_address_id` — Modifier/Dupliquer fonctionnent quand même,
  simplement sans présélection exacte de l'adresse (repli identique au
  comportement actuel).
- `QuotePersister::update()` duplique une partie de la logique de `persist()`
  (création des configurations/lignes) — factorisation possible si un 3e
  besoin de ce type apparaît, pas nécessaire pour 2 méthodes aujourd'hui.
- Hors périmètre de cette étape (confirmé) : le menu des 2 autres onglets
  (« en cours »/« archivés », actions différentes par statut, cf. ADR-051).
- Fichiers créés : `QuoteAccessControlHandler.php`,
  `Service/QuoteVehicleTermResolver.php`, `Service/QuoteDraftBuilder.php`,
  `Controller/QuoteModifyController.php`, `Controller/QuoteDuplicateController.php`,
  `Form/QuoteDeleteForm.php`, JS du menu d'actions. Fichiers modifiés :
  `Entity/Quote.php`, `Service/QuotePersister.php`, `Form/DeliveryForm.php`,
  `drivematic_configurator.routing.yml`, `drivematic_configurator.install`,
  SDC `quote-row`, `MyQuotesController.php`.
