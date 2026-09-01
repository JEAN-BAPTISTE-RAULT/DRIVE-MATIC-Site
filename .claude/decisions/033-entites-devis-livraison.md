# ADR-033 : Entites Devis/Configuration/Ligne d'equipement/Adresse de livraison

## Statut
Accepte

## Date
2026-09-01

## Contexte
F14 etape 3/3 (Livraison) devait faire passer le devis de l'etat « brouillon
`PrivateTempStore` » (ADR-031, aucune persistance avant cette etape) a un
enregistrement reel en base, au clic sur « Enregistrer le devis » (statut
« A finaliser ») ou « Commander » (statut « En cours »). Le modele
fonctionnel est deja specifie dans `docs/PRD.md` §5 : Devis (1→N
Configuration), Configuration (1→N Ligne d'equipement), Adresse de livraison
(N→1 partenaire). Restait a trancher la mise en oeuvre technique.

Egalement en jeu : comment representer l'« adresse de livraison par defaut »
(= celle du compte partenaire, PRD F14 §3) avant qu'aucune adresse
personnalisee n'ait ete ajoutee.

## Options considerees

### Modele Devis/Configuration/Ligne

**Option A (retenue) : 3 entites custom normalisees**, memes principes que
`equipment_price` (`drivematic_catalog`, seule entite custom preexistante) :
attributs PHP 8 `#[ContentEntityType(...)]`, `baseFieldDefinitions()`, pas de
`.install` (schema auto-genere).
- Avantages : conforme au modele PRD §5 ; permet un futur listing/filtrage
  par ligne (F15, back-office) sans migration ulterieure.
- Inconvenients : 3 tables au lieu d'une, plus de code de persistance
  (`QuotePersister`).

**Option B ecartee : un seul champ JSON sur Devis** (snapshot brut du
brouillon + resultat `QuoteCalculator`).
- Avantages : implementation plus rapide.
- Inconvenients : contredit le modele PRD deja specifie ; imposerait une
  migration vers un modele normalise des que F15 (onglets « Mes devis »,
  filtres/tri par colonne) sera implemente.

### Gel des donnees a la creation

**Decision** : `Quote` copie integralement les coordonnees de facturation
(`billing_*`, depuis le compte) et l'adresse de livraison retenue
(`delivery_*`, depuis `DeliveryAddress`) au moment de la creation — jamais de
reference vivante. `QuoteConfiguration` copie les libelles vehicule
(`vehicle_brand`/`vehicle_model`/`motorisation`) en **chaines**, pas en
reference `taxonomy_term`. Meme principe deja applique par `QuoteCalculator`
aux prix catalogue (ADR-031) : un devis deja « En cours »/« Archivé » ne doit
jamais changer retroactivement si le compte, une `DeliveryAddress` ou un
terme de taxonomie sont modifies/supprimes ensuite.

### Amorçage de l'adresse de livraison par defaut

**Decision** : a la premiere visite de l'ecran Livraison, si le partenaire
n'a **aucune** `DeliveryAddress`, une **vraie entite** est creee
automatiquement a partir des champs du compte (`field_company_name`/
`field_company_address`/`field_address_complement`/`field_postal_code`/
`field_city`). Elle est ensuite traitee exactement comme toute autre adresse
(modifiable/supprimable, cf. ADR ci-dessous sur les liens Modifier/
Supprimer) — **aucun cas particulier** dans le code d'affichage. Si le
partenaire supprime sa derniere adresse restante, une nouvelle copie est
ré-amorcee automatiquement au prochain passage (liste jamais vide).

Alternative ecartee : une ligne « virtuelle » non persistee tant qu'elle
n'est pas modifiee — rejetee par l'utilisatrice (retour du 2026-09-01) au
profit de la copie reelle immediate, plus simple (aucune distinction
reel/virtuel a maintenir dans l'UI).

### Controle d'acces par proprietaire (IDOR)

`DeliveryAddress` est la **premiere entite multi-instance par partenaire**
de ce projet (`equipment_price` est un catalogue unique importe en bloc,
sans notion de proprietaire). Nouvel `AccessControlHandler`
(`DeliveryAddressAccessControlHandler`) : `view`/`update`/`delete` autorises
uniquement si `uid` de l'entite == utilisateur courant. Verifie
cote serveur a 2 niveaux (defense en profondeur, CLAUDE.md) : requirement de
routing `_entity_access: 'delivery_address.update'`/`'.delete'` **et**
verification explicite en tete de chaque submit handler.

## Consequences
- Nouveau module : `web/modules/custom/drivematic_configurator/src/Entity/`
  (`Quote`, `QuoteConfiguration`, `QuoteEquipmentLine`, `DeliveryAddress`),
  `Service/QuotePersister.php`, `Service/QuoteReferenceGenerator.php`
  (numerotation `WAAAAMMJJ-001`, compteur journalier verrouille).
- Nouvelles tables installees via
  `\Drupal::entityDefinitionUpdateManager()->installEntityType()` (pas de
  `hook_update_N` : premiere installation d'un type d'entite deja-module-
  active, a rejouer identiquement en preprod/prod).
- `hook_cron()` (`drivematic_configurator.module`, nouveau fichier) archive
  automatiquement tout Devis « En cours » dont `date_commande` depasse 30
  jours (PRD F15, cas limite) — statut `archive`, `date_archivage` posee.
- Hors perimetre (confirme avec l'utilisatrice) : F13 (Tableau de bord
  partenaire) et le reste de F15 (onglets « Mes devis », Dupliquer, PDF,
  archivage manuel, remise exceptionnelle par ligne) — seules la
  persistance et les 2 messages de confirmation sont implementes ici.
