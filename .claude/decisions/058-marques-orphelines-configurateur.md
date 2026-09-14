# ADR-058 : Marque sans modèle publié exclue du select Marque du configurateur

## Statut

Accepté

## Date

2026-09-14

## Contexte

Bug signalé par l'utilisatrice : une marque `vehicle_brand` dont **tous** les
`vehicle_model` sont dépubliés (Statut « Ne pas publier » au dernier import
du catalogue, ADR-055) reste elle-même publiée et continue d'apparaître dans
le `<select>` « Marque » de `ConfigurationForm` — la sélectionner vide
entièrement le `<select>` « Modèle » (`#required`), un cul-de-sac sans
message d'erreur.

Confirmé en base locale au moment du diagnostic : 4 marques publiées sans
aucun modèle publié (KIA, MG, PORSCHE, TESLA), sur 29 marques publiées au
total.

Cause : `vehicle_brand` et `vehicle_model` sont deux vocabulaires de
taxonomie distincts (`field_brand` relie un `vehicle_model` à sa marque).
`CatalogImporter::applyTaxonomy()` crée/supprime des termes `vehicle_brand`
selon leur présence dans le fichier catalogue, mais ne les dépublie jamais —
seul `vehicle_model` est publié/dépublié. `ConfigurationForm::
loadTermOptions()` liste tous les termes `status => 1` d'un vocabulaire
donné, sans jointure avec `vehicle_model` : ce comportement était documenté
et assumé, mais produit ce bug.

## Options considérées

### Dépublier la marque en cascade à l'import (`CatalogImporter`)

Étendre `applyTaxonomy()` pour dépublier une marque n'ayant plus aucun
`vehicle_model` publié après traitement des modèles — cohérent avec l'esprit
d'ADR-055 (le Statut du fichier catalogue pilote toute la publication).

**Écartée** : `vehicle_brand` est aussi utilisé par le champ « Marque » du
webform `contact` (demande de devis/SAV, `config/sync/webform.webform.
contact.yml`) — un partenaire y sélectionne la marque d'un véhicule déjà
équipé, y compris une marque que Drive Matic ne vend plus aujourd'hui.
Dépublier le terme lui-même casserait silencieusement ce cas d'usage SAV, où
la marque reste pertinente indépendamment de l'inventaire configurateur
actuel. Aurait aussi nécessité une correction de données (les marques déjà
orphelines en base ne seraient pas corrigées rétroactivement sans un
réimport ou une commande ponctuelle).

### Filtrer au moment de la lecture, dans `ConfigurationForm` (retenue)

`ConfigurationForm` calcule déjà `drivematic_forms_vehicle_map()` (pour
`drupalSettings`, cascade JS) — sa clé `modelsByBrand` (indexée par id de
marque) ne contient que les marques ayant au moins un `vehicle_model`
publié, par construction. Intersecter simplement :

```php
$brand_options = array_intersect_key($this->loadTermOptions('vehicle_brand'), $vehicle_map['modelsByBrand']);
```

Aucune modification de `CatalogImporter`/`CatalogImportForm` : pas de
nouvelle transition à afficher sur l'écran de confirmation d'import, rien n'y
change. `vehicle_brand.status` reste intact (le webform contact/SAV n'est
pas affecté).

## Décision

Approche retenue : filtre en lecture dans `ConfigurationForm::buildForm()`,
via `array_intersect_key()` avec `modelsByBrand` (déjà calculé, aucune
requête supplémentaire).

**Effet de bord corrigé dans le même changement** : un commentaire existant
(`buildConfigurationElement()`) affirmait que « la marque, elle, reste
valide : seul vehicle_model peut etre depublie » — hypothèse qui devient
fausse avec ce filtre. Reprise (Modifier) ou duplication d'un devis dont la
marque a depuis perdu tous ses modèles (ou a été supprimée par un réimport)
aurait sinon fixé un `#default_value` absent des `#options`, que le
navigateur résout silencieusement en sélectionnant une AUTRE marque — même
piège déjà documenté et déjà traité pour le modèle (`$model_unavailable`).
Ajout symétrique de `$brand_unavailable` (même pattern), qui vide le
`#default_value` de la marque dans ce cas.

**Aucune correction de données nécessaire** : le filtre se recalcule à
chaque affichage depuis l'état déjà correct de `vehicle_model.status` — les
4 marques déjà orphelines en base disparaissent du select dès le déploiement
du code, sur tous les environnements, sans réimport ni script ponctuel.

## Conséquences

- `vehicle_brand.status` continue de ne jamais être dépublié : le champ
  « Marque » du webform contact/SAV n'est pas affecté par ce correctif,
  conforme à l'usage attendu de ce formulaire.
- Une marque orpheline redevient sélectionnable automatiquement dès qu'un
  nouveau catalogue republie au moins un de ses modèles — aucune action
  manuelle requise.
- Vérifié en local (4 marques réelles déjà orphelines : KIA, MG, PORSCHE,
  TESLA) : absentes du select après le correctif (25 options au lieu de 29,
  curl en tant que partenaire réel) ; test de régression supplémentaire
  (dépublication temporaire des modèles BMW, reprise d'un devis « à
  finaliser » existant via Modifier) confirmant que `#default_value` se vide
  correctement (aucune marque incorrecte pré-sélectionnée), sans erreur PHP.
- Fichier modifié : `Form/ConfigurationForm.php` uniquement.
