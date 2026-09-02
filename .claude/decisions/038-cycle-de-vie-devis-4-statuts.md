# ADR-038 : Cycle de vie du devis a 4 statuts + remise Drive Matic par ligne

## Statut
Accepte

## Date
2026-09-02

## Contexte
Le PRD ([INFERE], §F15) anticipait deja cet affinement : le statut,
simplifie a 2 valeurs (« A finaliser »/« En cours ») pour la livraison F14
3/3, devait etre precise plus tard en distinguant « A commander » de
« Commande le jj/mm/aaaa ». Retour utilisatrice : 4 statuts (« A finaliser »,
« A commander », « Commande le JJ/MM/AAAA », « Archive »), 2 actions
manuelles back-office (marquer commande, archiver) et une remise
exceptionnelle par ligne d'equipement, accordee par Drive Matic tant que le
devis est « A commander ».

## Options considerees

### Option A : garder la valeur technique 'en_cours', changer seulement le libelle
- Avantages : aucune migration.
- Inconvenients : le code (hook_cron, DeliveryForm, QuotePersister...)
  continuerait de manipuler une constante `STATUS_EN_COURS` qui ne
  correspond plus a son libelle affiche (« A commander ») — confusion
  garantie pour quiconque relit ce code plus tard. Ecarte a la demande de
  l'utilisatrice.

### Option B (retenue) : renommage propre en base ('en_cours' -> 'a_commander')
- Avantages : le code reste lisible (`STATUS_A_COMMANDER`), coherent avec
  son libelle. Aucune vraie donnee de prod a ce stade — migration
  `hook_update_N` a cout quasi nul.
- Inconvenients : necessite le 1er fichier `.install` du module (pattern
  deja etabli ailleurs dans le projet, `drivematic_forms.install`, repris a
  l'identique).

### Prix effectif : stocke vs calcule a la lecture
- **Stocker le prix final (partenaire + DM) directement dans
  `discounted_unit_price`/`discounted_ht`** : ecarte — une remise DM
  appliquee deux fois (ou modifiee) recalculerait a partir d'une valeur
  DEJA remisee par la remise DM precedente, cumulant les remises sur
  elles-memes a chaque sauvegarde (bug de compounding).
- **Retenu : `unit_price`/`discounted_unit_price`/`ht`/`discounted_ht`
  restent geles pour toujours (base « catalogue + remise partenaire »),
  `dm_discount_rate` est simplement REMPLACE (jamais cumule sur
  lui-meme) a chaque sauvegarde, et le prix effectif se calcule a la
  lecture** (`QuoteEquipmentLine::getEffectiveDiscountedUnitPrice()`/
  `getEffectiveDiscountedHt()`). Aucun risque de compounding, une seule
  formule utilisee par le controleur d'affichage ET le formulaire de
  remise.

## Decision
Option B pour le renommage. 4 statuts (`Quote::STATUS_A_FINALISER`,
`STATUS_A_COMMANDER`, `STATUS_COMMANDE`, `STATUS_ARCHIVE`), nouveau champ
`date_confirmation` (distinct de `date_commande` — l'un est pose par le
partenaire en cliquant « Commander » sur le site, l'autre par Drive Matic
en back-office pour une commande conclue par telephone). Nouvelle
permission `edit drivematic configurator quotes`, distincte de la
permission de lecture existante (moindre privilege), geree par un simple
`_permission` sur les routes d'action — aucun nouvel access handler
custom (coherent avec ADR-037). 2 `ConfirmFormBase` (marquer commande,
archiver), chacun re-verifiant cote serveur que le devis est bien
« A commander » avant d'agir — pas seulement en cachant le bouton. 1
`FormBase` (remise par ligne) embarque dans `QuoteDetailController::view()`
via `formBuilder()->getForm()`, sans route dediee (aucun besoin identifie
en dehors de cette page).

**Archivage manuel restreint a « A commander » uniquement** (jamais depuis
« Commande ») : diverge d'une ligne du PRD (§F15, « Un devis peut etre
archive manuellement qu'il soit commande ou non »), ecrite avant que la
distinction « A commander »/« Commande » n'existe. La reponse plus recente
et plus precise de l'utilisatrice prevaut ; le PRD est mis a jour en
consequence.

Enregistrer une remise DM remet aussi `date_commande` a l'heure actuelle
(PRD F15, « cas limites » : « le compteur des 30 jours... remis a
zero ») — le meme champ sert donc a la fois de date d'origine ET de point
de depart du delai d'archivage, redemarrable par une remise.

## Consequences
- `drivematic_configurator.install` (nouveau, 3 `hook_update_N` :
  migration statut, 2 nouveaux champs) — a rejouer sur tout environnement
  avant deploiement (`drush updb`).
- `hook_cron()` n'archive plus que les devis `STATUS_A_COMMANDER` (jamais
  `STATUS_COMMANDE`) — verifie avec un devis fictif de 31 jours dans
  chaque statut.
- Le listing (Vue `quotes`) garde un libelle statique pour « Commandé »
  (sans date) — l'affichage riche avec date n'est compose qu'au niveau de
  la page de detail (`QuoteDetailController::formatStatus()`), le futur
  dashboard partenaire (F13/F15, hors perimetre) etant le vrai porteur de
  ce besoin d'affichage detaille.
- Fichiers impactes : `Entity/Quote.php`, `Entity/QuoteEquipmentLine.php`,
  `drivematic_configurator.install` (nouveau),
  `drivematic_configurator.module`, `drivematic_configurator.
  permissions.yml`, `drivematic_configurator.routing.yml`,
  `Controller/QuoteDetailController.php`, `Form/QuoteMarkOrderedForm.php`
  (nouveau), `Form/QuoteArchiveForm.php` (nouveau),
  `Form/QuoteDiscountForm.php` (nouveau), `Form/DeliveryForm.php`,
  `Service/QuotePersister.php`, `views.view.quotes.yml`.

## Addendum (meme jour) : historique des statuts + retours utilisatrice sur l'UI

Retour utilisatrice apres la premiere livraison, 4 points :
1. Les dates individuelles (`date_commande`/`date_confirmation`/
   `date_archivage`) dans « Resume » ne montraient pas QUI avait fait
   chaque changement — remplacees par une section « Historique » listant
   CHAQUE transition (date + statut + auteur), du plus ancien au plus
   recent.
2. « Resume » : ajout d'une ligne « Remise partenaire » (taux courant du
   compte, `field_discount_rate`) sous « Partenaire », et le partenaire
   devient un lien vers `/user/{uid}/edit` (meme convention qu'ADR-035).
3. Colonne « dont remise DM » retiree du tableau en lecture seule des
   lignes d'equipement (redondante avec le formulaire de remise
   lui-meme).
4. Le formulaire de remise (`QuoteDiscountForm`) groupe desormais ses
   lignes PAR CONFIGURATION (comme la section lecture seule) — sans ce
   groupement, deux configurations partageant un equipement homonyme (ex.
   deux fois « Retrovision exterieure ») rendaient impossible de savoir a
   quel vehicule s'appliquait quelle remise.

**Nouvelle entite `quote_status_change`** (point 1) : une entree par
transition (creation du devis par `QuotePersister::persist()`, marquage
manuel par les 2 `ConfirmFormBase`, archivage automatique par
`drivematic_configurator_cron()` — `uid` absent pour cette derniere,
affiche « Automatique »). Meme pattern que `quote_configuration`/
`quote_equipment_line` (aucun `admin_permission`/handler d'acces propre,
chargee uniquement apres verification d'acces au `Quote` parent) plutot
qu'un champ JSON serialise sur `Quote` — coherent avec le reste du modele
de donnees du module, reutilisable si le futur dashboard partenaire en a
besoin. `drivematic_configurator.install` : 4e `hook_update_N`
(`installEntityType()`, pas seulement un champ — 1ere fois pour ce
module).

⚠️ **Piege rencontre en verifiant avec un devis a 2 configurations** (voir
CLAUDE.md, section PHP/Drupal) : une cellule de `#type: table` dont la
valeur est un render array (ici, le lien partenaire via
`Link::toRenderable()`) doit etre enveloppee dans `['data' => ...]` — sans
ce wrapper, `template_preprocess_table()` traite le tableau nu comme des
attributs HTML bruts, provoquant un 500 (`RuntimeException: Unexpected
type for $value (Url)`) invisible tant qu'on ne teste pas le rendu HTML
complet (le render array seul, inspecte en PHP, semblait correct).

⚠️ **Retour utilisatrice, meme jour** : l'Historique d'un devis CREE AVANT
l'ajout de `QuotePersister::logStatusChange()` (tous les devis de test de
la session, cree avant cet addendum) n'affichait AUCUNE ligne, pas meme la
creation — aucune entree `quote_status_change` n'existait pour eux.
`QuoteDetailController::buildCreationRow()` synthetise desormais
systematiquement cette 1ere ligne quand le journal est vide (`created` +
`uid` du devis lui-meme, statut initial deduit de la presence de
`date_commande` — jamais NULL apres coup, donc fiable). Jamais de doublon
pour un devis cree DEPUIS cet addendum (`QuotePersister` y a deja consigne
la meme entree, `$changes` n'est alors jamais vide) — verifie sur un vrai
devis passe par `QuotePersister::persist()`. **Limite assumee** : les
transitions LEGACY posterieures a la creation (ex. un devis deja marque
« Commande » avant l'addendum) restent absentes de l'historique — on ne
fabrique pas un auteur qu'on ne connait pas reellement.
