# ADR-055 : Statut du combinatoire pilote la publication du vehicule, Type de VOR stocke

## Statut
Accepte

## Date
2026-09-11

## Contexte

Audit du combinatoire client (`Drive_Matic_Combinatoire_20260909.xlsx`) : deux colonnes du
fichier, lues par `CatalogImporter::parse()`, n'etaient jamais persistees ni exploitees
(cf. memoire `catalogue-colonnes-statut-vor-type-ignorees.md`, desormais resolue) :

- **Statut** (« À publier sur le site » / « Ne pas publier », 96/47 lignes sur le fichier
  audite) : lue pour la seule validation d'en-tete, jamais stockee. Aucun filtre de
  publication n'existait nulle part (`ConfigurationForm::loadTermOptions()`,
  `drivematic_forms_vehicle_map()` chargeaient tous les `vehicle_model` sans distinction) :
  un modele marque « Ne pas publier » etait selectionnable exactement comme un modele publie.
- **Type de VOR** (PLUG & PLAY / COMMODO A ENVOYER / APPELER DML) : documentee dans le plan
  initial (`docs/plans/catalogue-tarifs-import.md`, ligne 26) comme un des 3 champs du
  combinatoire VOR, mais jamais modelisee sur `equipment_price` — perdue a chaque import.

Decidee avec l'utilisatrice le 2026-09-10 : les deux doivent avoir un effet reel.

## Options considerees

### Statut → publication

**Option A (retenue) : depublier (`status = 0`), jamais supprimer.** Le terme `vehicle_model`
et ses lignes de tarif restent en base, juste masques de tout selecteur cote partenaire/
visiteur.
- Avantages : coherent avec le choix deja fait dans ADR-030 pour la taxonomie en general
  (jamais de suppression, le webform contact stocke des soumissions `webform_term_select`
  qui referencent un ID de terme — une suppression casserait ces references historiques,
  meme risque qu'un vider-recreer litteral).
- Inconvenients : un modele « Ne pas publier » reste techniquement en base (pas de « vrai »
  retrait), acceptable puisque invisible partout ou ca compte.

**Option B (ecartee) : supprimer le terme et ses tarifs.** Repond plus litteralement a
« ne plus importer », mais casse potentiellement l'affichage de soumissions de contact
archivees qui referencaient ce modele par son ID.

### Type de VOR

**Option A (retenue) : champ texte libre** (`string`, meme normalisation que `reference`).
- Avantages : une 4e valeur future du client (le vocabulaire n'est pas ferme, contrairement a
  `motorisation`) passe sans modification de code.
- Inconvenients : aucune validation d'integrite si le client tape une variante orthographique
  — juge acceptable, cette donnee est une instruction pour l'equipe DM, pas une facette de
  filtrage ou d'affichage public.

**Option B (ecartee) : `list_string` a 3 valeurs fixes.** Coherent avec `type_equipement`,
mais bloquerait un import legitime si le client introduit une 4e valeur avant une mise a jour
du code — le meme risque que la validation stricte d'en-tete, juge disproportionne ici.

## Decision

- `equipment_price` gagne un champ `type_vor` (`string`, nullable, rempli uniquement pour
  `type_equipement = telecommande_vor`) — `hook_update_11001` dans le nouveau fichier
  `drivematic_catalog.install`.
- `CatalogImporter::applyTaxonomy()` appelle desormais `$term->setPublished()` /
  `$term->setUnpublished()` (jamais de suppression) selon la colonne Statut du modele
  (`self::PUBLISHED_STATUS_VALUE = 'À publier sur le site'` — toute autre valeur, y compris
  vide ou inattendue, masque par defaut : repli du cote le plus sur). Les lignes de tarif
  restent creees normalement, publiees ou non : la publication ne filtre que la
  **visibilite** du vehicule, jamais l'existence de son tarif catalogue.
- `ConfigurationForm::loadTermOptions()` et `drivematic_forms_vehicle_map()` filtrent
  desormais `status = 1` — un modele depublie disparait du configurateur ET de la cascade JS
  du formulaire de contact, pour un NOUVEAU choix.
- **Re-edition d'un devis existant (Modifier/Dupliquer, ADR-052)** : `QuoteVehicleTermResolver`
  resout un `vehicle_model` par nom sans filtrer sur son statut (inchange) — une configuration
  dont le vehicule a ete depublie depuis n'est donc jamais droppee (contrairement a un modele
  reellement supprime de la taxonomie, deja gere par `QuoteDraftBuilder`). Deux consequences
  cote UI :
  - `QuoteForm` (ecran « Devis ») affiche desormais un message
    « Cette configuration n'est plus proposée au catalogue. » a cote de toute configuration
    dont le vehicule est depublie (nouvelle methode `loadModelPublicationState()`).
  - `ConfigurationForm` (etape 1) : un `<select>` de modele ne peut pas afficher une valeur
    absente de ses propres `#options` sans se rabattre silencieusement sur une autre valeur
    (comportement navigateur, pas une erreur Drupal) — `buildConfigurationElement()` vide
    donc explicitement `model`/`motorisation` quand le modele sauvegarde n'est plus dans les
    options publiees, avec un message explicatif ; la marque reste presélectionnée (jamais
    depubliee independamment).

## Bugs decouverts et corriges en verifiant cette implementation

Sans rapport avec Statut/Type de VOR, mais trouves en testant le present changement sur le
fichier reel :

- **`EntityPublishedTrait::setPublished()` ne prend AUCUN argument** (core Drupal) — il force
  toujours `TRUE` ; `setUnpublished()` est la methode separee pour l'autre cas. Un premier
  jet `$term->setPublished($info['published'])` compilait et s'executait sans la moindre
  erreur, l'argument etait juste silencieusement ignore : **tous** les modeles se
  republiaient, quel que soit le Statut du fichier. Verifie explicitement en rechargeant
  l'entite depuis une nouvelle requete (jamais depuis l'objet PHP deja en memoire, qui aurait
  masque le probleme) avant de considerer la fonctionnalite correcte.
- **Cle de tableau flottante tronquee en entier** (PHP, pas specifique a Drupal) :
  `CatalogImporter::parse()` utilisait le tarif rétrovision lui-meme comme cle de tableau
  (`$retrovision[$type][$tarif] = ...`) pour detecter une incoherence de prix entre lignes du
  fichier. PHP caste une cle de tableau flottante en entier (troncature, pas arrondi) :
  51.48 devenait la cle 51, 29.46 devenait 29 — silencieusement, depuis le tout premier
  import du catalogue (commit `9deb829`). Corrige en cle `sprintf('%.2f', $tarif)` (string,
  jamais recastee par PHP car non « entier canonique »). Reimporte le fichier reel apres
  correctif : la base reflete desormais 51,48 €/29,46 € HT au lieu de 51,00 €/29,00 € HT.

## Consequences

- Fichiers modifies : `EquipmentPrice.php` (+champ), `drivematic_catalog.install`
  (nouveau, hook_update_11001), `CatalogImporter.php` (parse/diff/applyTaxonomy/
  createPriceRows), `EquipmentPriceListBuilder.php` (colonne Type de VOR),
  `CatalogImportForm.php` (comptages/avertissement publication a l'ecran de confirmation),
  `ConfigurationForm.php`, `QuoteForm.php`, `drivematic_forms.module`.
- Tout modele deja en base et marque « Ne pas publier » au prochain import sera depublie des
  le premier `drush updb` + reimport — aucune action manuelle supplementaire necessaire.
- Le catalogue de tarifs redevient fiable au centime pres pour la rétrovision : toute
  difference desormais constatee entre le fichier et la base est un vrai ecart de donnee, pas
  un artefact de troncature.
