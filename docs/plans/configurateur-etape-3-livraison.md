# Plan — Configurateur étape 3 « Livraison » (F14 3/3)

Date : 2026-09-01
Statut : implemente et verifie (voir `docs/active/configurateur-etape-3-livraison/verification.md`)

## 1. Intention

Terminer le configurateur de devis (F14) : écran 3 « Livraison » (choix/ajout/
modification/suppression d'une adresse de livraison), et faire passer le
devis de l'état « brouillon PrivateTempStore » à une **persistance réelle**
(entités Devis/Configuration/Ligne d'équipement, PRD §5) au clic sur
« Enregistrer le devis » (statut « À finaliser ») ou « Commander » (statut
« En cours »), avec archivage automatique à J+30.

## 2. Fichiers impactés

**Nouvelles entités de contenu** (`web/modules/custom/drivematic_configurator/src/Entity/`) :
- `DeliveryAddress.php` — adresse de livraison réutilisable, propriété d'un
  partenaire (`raison_sociale`, `adresse`, `complement`, `code_postal`,
  `ville`, `uid`).
- `Quote.php` — devis (`reference` WAAAAMMJJ-001, `status`, `uid`,
  `date_creation`, `date_commande`, `date_archivage`, totaux gelés,
  adresse de livraison **gelée** en champs propres — voir §4).
- `QuoteConfiguration.php` — groupe véhicule d'un devis (`quote_id`,
  `vehicle_brand`/`vehicle_model`/`motorisation` figés, `vehicle_count`).
- `QuoteEquipmentLine.php` — ligne d'équipement (`configuration_id`, libellé,
  quantités, tarifs HT/remisé gelés).

Pattern repris d'`EquipmentPrice` (`drivematic_catalog`, seule entité custom
existante à ce jour) : attributs PHP 8 `#[ContentEntityType(...)]`,
`baseFieldDefinitions()`, pas de fichier `.install` (schéma auto-généré).
Nouveauté par rapport à ce précédent : ces 4 entités sont **multi-instance
par partenaire** (jamais le cas d'`EquipmentPrice`, catalogue unique importé
en bloc) → premier besoin réel de contrôle d'accès par propriétaire dans ce
projet, à construire (§4).

**Nouveaux services** :
- `Service/QuoteReferenceGenerator.php` — numérotation `WAAAAMMJJ-001`
  (compteur journalier, remis à 0 chaque jour).
- `Service/QuotePersister.php` — matérialise le brouillon `PrivateTempStore`
  + le résultat déjà calculé par `QuoteCalculator` en entités Devis/
  Configuration/Ligne (aucun recalcul : réutilise les prix déjà gelés par
  `QuoteCalculator`, cf. ADR-031).

**Nouveau formulaire d'étape** :
- `Form/DeliveryForm.php` — écran 3, route `/configurer/livraison`.

**CRUD adresse de livraison** :
- `Form/DeliveryAddressForm.php` — ajout/édition (modale), même formulaire
  pour les deux cas (pré-rempli si édition).
- `Form/DeliveryAddressDeleteForm.php` — `ConfirmFormBase`, modale de
  confirmation de suppression.

**Cron** :
- `drivematic_configurator.module` (n'existe pas encore) — `hook_cron()`,
  archive tout Devis `en_cours` dont `date_commande` dépasse 30 jours.

**Modifiés** :
- `Form/QuoteForm.php` — `deliveryPlaceholderSubmit()` remplacé par une
  vraie redirection vers `drivematic_configurator.delivery`.
- `drivematic_configurator.routing.yml` — 4 nouvelles routes (`delivery`,
  `delivery.address_add`, `delivery.address_edit`,
  `delivery.address_delete`), toutes `_role: 'partenaire'`.
- `src/scss/_delivery-form.scss` (nouveau, même famille que
  `_quote-form.scss`/`_configurator-form.scss`) + import dans `style.scss`.

**Documentation** (fin de tâche, `/sync`) : `docs/PRD.md` (cases à cocher
F14 étape 3), `docs/E2E_SCENARIOS.md`, nouvel ADR (voir §6), mémoire auto.

## 3. Interfaces publiques

- 4 nouvelles routes Drupal (listées ci-dessus), toutes protégées
  `_role: 'partenaire'`.
- 4 nouveaux types d'entité de contenu (`quote`, `quote_configuration`,
  `quote_equipment_line`, `delivery_address`) — pas d'impact sur la
  configuration du linter (pas de nouveau global/export JS ; aucun JS
  custom n'est prévu, cf. §5 modale).

## 4. Sécurité

- **IDOR — priorité haute** : c'est la première fois que ce projet expose
  des routes CRUD sur des entités **multi-instances par partenaire**
  (`DeliveryAddress`). Chaque route d'édition/suppression doit vérifier
  côté serveur que l'entité chargée appartient bien à
  `$this->currentUser->id()` — jamais uniquement via l'URL. Implémentation :
  `hook_ENTITY_TYPE_access()` (ou `AccessControlHandler` dédié) sur
  `delivery_address`, refusant `update`/`delete` si `uid` ne correspond pas
  à l'utilisateur courant, doublé par un contrôle explicite en tête de
  chaque submit handler (défense en profondeur, pattern déjà établi par
  `PersonalInformationForm::loadCurrentAccount()`).
- **Gel des données à la création du devis** : `Quote`/`Configuration`/
  `Ligne` copient les valeurs (adresse de livraison, prix, libellés
  véhicule) au moment de la création plutôt que de référencer par ID
  vivant — nécessaire pour qu'un devis déjà « En cours »/« Archivé » ne soit
  jamais modifié rétroactivement par l'édition/suppression ultérieure d'une
  `DeliveryAddress` ou d'un tarif catalogue. Même principe que le gel des
  prix déjà appliqué par `QuoteCalculator` (ADR-031).
- **Validation serveur des champs adresse** : `code_postal` au format
  `\d{5}` (même contrainte que `field_postal_code`/webform existants),
  `raison_sociale`/`adresse`/`ville` requis, jamais fié au seul `required`
  HTML.
- CSRF : géré nativement par `FormBase`/`ConfirmFormBase`.
- Aucune donnée partenaire n'atteint un anonyme (routes déjà `_role:
  'partenaire'`, redirection sitewide en place).

## 5. Risques et contraintes techniques

- **Piège FormBase + #ajax déjà rencontré sur ce chantier** : si
  `DeliveryForm`/`DeliveryAddressForm` utilisent `#ajax` (rechargement du
  bloc adresses après ajout/suppression), toute propriété-service injectée
  par constructeur doit être `protected`, jamais `private` (sinon 500 au
  2e cycle AJAX — cf. mémoire `formbase-ajax-protected-properties`).
- **Modale — écart avec le pattern `help-modal` existant** : `help-modal`
  (seul pattern de modale du projet) est un `<dialog>` HTML natif + JS
  vanilla, pensé pour du contenu **statique**. Il ne convient pas à un
  formulaire nécessitant validation serveur. Ce plan introduit donc le
  système de modale **Drupal core** (`use-ajax` + `data-dialog-type:
  modal`, `OpenModalDialogCommand`) — première utilisation dans ce projet
  (seule la bibliothèque `drive_matic/dialog`, habillage CSS, existe déjà).
  Avantage : aucun JS custom à écrire, formulaire validé serveur comme
  n'importe quel autre. À documenter en ADR (§6).
- **Nouvelles tables de BDD** : ajouter des types d'entité à un module déjà
  activé nécessite `drush entity:updates` (détection du schéma manquant),
  pas un `hook_update_N` — non destructif, mais à vérifier explicitement en
  local avant toute mise en prod.
- **Génération de la référence `WAAAAMMJJ-001`** : compteur journalier —
  prévoir un verrou (`\Drupal::lock()` ou requête atomique) pour éviter une
  collision si deux partenaires commandent à la même seconde (volumétrie
  très faible, ~100 partenaires, mais le cas doit être géré correctement).
- **i18n** : toutes les chaînes visibles via `$this->t()`.
- **Accessibilité** : la modale Drupal core gère nativement le focus trap
  et `aria-*` (jQuery UI dialog) ; le groupe de radios « Sélectionner une
  adresse de livraison » doit être un vrai `#type: radios` avec légende,
  jamais des `input` isolés.

## 6. Cohérence avec les spécifications

- Aligné avec `docs/PRD.md` F14 étape 3 (« adresse de facturation non
  modifiable en front ; choix/ajout/modification d'adresse de livraison,
  persistée en back-office ») et le modèle de données §5 (Devis →
  Configuration → Ligne d'équipement, Adresse de livraison N→1 partenaire).
  La **suppression** d'adresse (déviation utilisatrice #3) n'est pas dans
  les critères F14 mais ne contredit rien — extension cohérente.
- **Hors périmètre, confirmé avec l'utilisatrice** : F13 (Tableau de bord
  partenaire) et le reste de F15 (onglets « Mes devis », Dupliquer,
  Modifier un devis existant, PDF, archivage manuel) restent des chantiers
  séparés, non commencés (`<nolink>` dans le menu compte). Cette tâche
  persiste l'entité Devis et affiche les messages de confirmation demandés,
  sans construire de page de consultation.
- **Ligne TVA de la maquette** : omise (confirmé) — aucun champ TVA
  n'existe sur le compte partenaire, seul `field_siret` existe.
- 2 ADR à créer en cours de route : (a) architecture des 4 entités devis
  (relations, gel des données, accès par propriétaire) ; (b) introduction
  du système de modale Drupal core (vs `help-modal` vanilla JS).
- Nouveau scénario E2E à ajouter (`docs/E2E_SCENARIOS.md`) : parcours complet
  Configuration → Devis → Livraison → Enregistrer/Commander.

## 7. UX adresse — tranché avec l'utilisatrice (écart supplémentaire vs maquette)

La maquette mobile (671:21277) montre un résumé « Mon adresse de livraison »
+ un bouton unique « Modifier l'adresse de livraison », suivi d'une section
séparée « Sélectionner une adresse de livraison » (radios). **Retour
utilisatrice : le bloc résumé + bouton isolé est un résidu obsolète de la
maquette, à retirer.** Structure retenue :

- Seule la section **« Sélectionner une adresse de livraison : »** est
  affichée pour la livraison — **toujours**, même quand le partenaire n'a
  qu'une seule adresse (jamais de cas caché/replié en dessous de 2).
- Chaque ligne (radio + adresse) porte **un lien Modifier et un lien
  Supprimer**, sans exception — même pattern que les actions par
  configuration de `QuoteForm.php` (icônes `pen.svg`/`trash.svg`).
- **Amorçage** : à la toute première arrivée d'un partenaire sur l'étape 3
  (aucune `DeliveryAddress` en base), une **vraie entité** est créée
  automatiquement à partir des champs du compte (`field_company_name` →
  `raison_sociale`, `field_company_address` → `adresse`,
  `field_address_complement` → `complement`, `field_postal_code` →
  `code_postal`, `field_city` → `ville`), sélectionnée par défaut. Elle est
  ensuite traitée **exactement comme toute autre adresse** (modifiable,
  supprimable) — aucun cas particulier dans le code d'affichage. Si le
  partenaire supprime la dernière adresse restante, une nouvelle copie est
  ré-amorcée automatiquement à la prochaine visite de l'étape (liste jamais
  vide).

## 8. Plan d'implémentation (étapes vérifiables)

1. **Entités** (`DeliveryAddress`, `Quote`, `QuoteConfiguration`,
   `QuoteEquipmentLine`) → vérifier : `drush entity:updates -y` propre,
   création/lecture/suppression d'un enregistrement de chaque type via
   `drush php:eval`.
2. **`QuoteReferenceGenerator`** → vérifier : deux appels le même jour
   incrémentent (`W20260901-001`, `W20260901-002`), un appel simulé le
   lendemain repart à `-001`.
3. **`QuotePersister`** (brouillon tempstore + résultat `QuoteCalculator` +
   adresse retenue → entités) → vérifier : sur un brouillon de test, les
   montants des `QuoteEquipmentLine` créées correspondent exactement à ceux
   déjà affichés par `QuoteForm` (aucun recalcul divergent).
4. **CRUD `DeliveryAddress`** (routes, formulaires modale ajout/édition,
   confirmation suppression, contrôle d'accès propriétaire) → vérifier :
   test manuel avec 2 comptes partenaire distincts, confirmer qu'un compte
   ne peut ni voir ni modifier/supprimer l'adresse de l'autre (URL forgée).
5. **`DeliveryForm`** (écran 3 complet : facturation lecture seule +
   message contact ; amorçage de la 1re `DeliveryAddress` si liste vide ;
   liste « Sélectionner une adresse de livraison » toujours affichée,
   radios + lien Modifier/Supprimer sur chaque ligne ; bouton Ajouter ;
   boutons Enregistrer le devis/Commander branchés sur `QuotePersister` +
   messages de confirmation + purge du brouillon tempstore après
   persistance réussie) → vérifier : parcours complet en navigateur (mobile
   + desktop), comparaison à la maquette (mesures `get_design_context` au
   moment de coder — le résidu « Mon adresse de livraison » n'est **pas**
   reproduit, cf. §7), et vérifier qu'un partenaire qui supprime sa
   dernière adresse restante s'en voit ré-amorcer une au rechargement.
6. **`hook_cron`** (archivage à J+30) → vérifier : `drush php:eval` pour
   antidater `date_commande` d'un devis de test à J-31, `drush cron`,
   confirmer le passage à `archive` ; un devis à J-29 reste inchangé.
7. **SCSS** `_delivery-form.scss` → `npm run css`, vérifier les valeurs
   dans le `.css` généré.
8. **Qualité** : `npm run lint` + `npm run format:check` doivent passer.
9. **`/sync`** : PRD, E2E, 2 ADR, mémoire auto.

## 9. Stratégie de test et boucle de feedback

- Pas de suite PHPUnit en place sur ce projet (placeholder, `docs/PRD.md`) :
  vérification manuelle + `drush php:eval` pour la logique métier
  (entités/services), navigateur pour l'UI.
- Boucle la plus rapide : `drush php:eval` pour `QuoteReferenceGenerator`/
  `QuotePersister`/cron (pas de rechargement navigateur nécessaire) ;
  Browser MCP pour `DeliveryForm`/modale.
- **Cas d'erreur à tester en plus du happy path** :
  - Aucune `DeliveryAddress` existante (premier passage d'un partenaire) →
    l'adresse de compte par défaut doit suffire à Enregistrer/Commander.
  - Code postal mal formé soumis en contournant la validation HTML5
    (`fetch` direct ou attribut retiré en devtools) → rejeté serveur.
  - Tentative de modifier/supprimer l'adresse d'un autre partenaire (IDOR).
  - Suppression d'une adresse déjà utilisée par un devis existant « En
    cours »/« Archivé » → le devis gelé reste inchangé.
  - Devis à exactement J+30 vs J+29 (limite du cron).
  - Double clic rapide sur « Commander » → pas de doublon de référence
    (verrou du générateur).
