# ADR-043 : Remises partenaire par équipement (remplacement, plus de cumul)

## Statut
Accepte

## Date
2026-09-03

## Contexte
La remise partenaire etait un taux unique et global (`field_discount_rate`, un champ sur
le compte `user`), applique identiquement aux 4 equipements du configurateur (retrovision
ext./int., telecommande VOR, double pedalier auto-ecole). Demande utilisatrice : pouvoir
negocier un taux different par equipement, complete ou vide selon le cas.

En parallele, la remise exceptionnelle Drive Matic (`QuoteEquipmentLine::dm_discount_rate`,
ADR-038) s'appliquait EN CASCADE par-dessus le prix deja remise par le taux partenaire
(`discounted_unit_price`, gele a la creation) : `effectif = discounted_unit_price * (1 -
dm_rate/100)`. Retour utilisatrice : sur un devis « À commander », ce champ doit se
preremplir avec la remise partenaire de l'equipement, et une fois enregistre (meme sans
modification), devenir LA SEULE remise appliquee — jamais cumulee avec le taux partenaire
deja fige a la creation. Sans changement de formule, confirmer la valeur preremplie sans la
modifier aurait mecaniquement double la remise (le taux partenaire aurait ete applique une
2e fois par-dessus lui-meme).

## Options considerees

### Option A : copier le taux partenaire dans `dm_discount_rate` a la creation du devis
- Avantages : pas de resolution live, `dm_discount_rate` toujours une valeur explicite.
- Inconvenients : un devis deja cree avant la saisie des 4 nouveaux taux partenaire (cas
  reel ici : aucune migration de l'ancien champ, les 2 partenaires existants sont resaisis
  a la main apres coup) ne beneficierait jamais des valeurs saisies ensuite — contraire au
  besoin explicite de l'utilisatrice de « brancher le calcul des devis existants » sur les
  nouveaux champs.

### Option B (retenue) : resolution live, `dm_discount_rate` NULL tant que non enregistre
- Avantages : `dm_discount_rate` reste NULL tant que Drive Matic n'a jamais explicitement
  enregistre de remise sur cette ligne ; le prix effectif
  (`QuoteEquipmentLine::getEffectiveDiscountedUnitPrice()`/`getEffectiveDiscountedHt()`) lit
  alors en direct le taux partenaire courant (`PartnerDiscountResolver`, resolu depuis le
  compte proprietaire du devis) — un devis deja cree profite donc immediatement d'un taux
  partenaire saisi apres coup, sans script de migration. Des que Drive Matic enregistre une
  valeur (meme identique a la suggestion), elle remplace definitivement le taux partenaire
  pour cette ligne : jamais de cumul.
- Inconvenients : introduit un etat NULL distinct de 0 sur `dm_discount_rate` (deja gere,
  simple retrait du `setDefaultValue(0)` d'origine) ; le calcul depend d'un aller-retour
  supplementaire (Quote -> QuoteConfiguration -> proprietaire) a chaque lecture — volumes
  trop faibles pour etre un probleme (page 100% admin, quelques lignes par devis).

### Gel a la confirmation de commande
- Sans gel, un devis marque « Commandé » sans que Drive Matic n'ait jamais ouvert le
  formulaire de remise continuerait de suivre les modifications futures du compte
  partenaire indefiniment — contraire au principe deja applique au reste du devis (adresses,
  prix catalogue, tous geles a un moment du cycle de vie).
- **Retenu** : `QuoteMarkOrderedForm::submitForm()` fige desormais `dm_discount_rate` (au
  taux partenaire courant, ou 0 si aucun) sur toute ligne encore NULL, juste avant de passer
  le devis au statut `STATUS_COMMANDE`.

### Cle de rattachement ligne <-> remise partenaire
- `QuoteEquipmentLine` ne portait que `label` (chaine traduite) — pas une cle stable pour
  choisir lequel des 4 champs partenaire s'applique. Nouveau champ `equipment_type` (string,
  memes valeurs que `EquipmentPrice::type_equipement` / `QuoteCalculator::
  EQUIPMENT_CATALOG_TYPES` : `retrovision_ext`/`retrovision_int`/`telecommande_vor`/
  `pedalier`), persiste par `QuotePersister` depuis le resultat deja calcule par
  `QuoteCalculator`. Une ligne anterieure a ce champ reste NULL : resout alors a « pas de
  remise » (0%), jamais une erreur — meme limite deja acceptee pour `reference`
  (ADR-041)/`quote_discount_change` (ADR-040).
- Ce mapping (type catalogue -> champ `field_discount_<type>`) est le 5e endroit du projet a
  reciter les 4 memes cles (dette deja documentee, ADR-028) : pas resolu ici, hors
  perimetre de cette feature.

### Ancien champ `field_discount_rate`
- **Supprime purement et simplement** (config + donnees, purge immediate via
  `field_purge_batch()` dans un hook_update), sans migration : decision utilisatrice, seuls
  2 comptes partenaire existent et seront ressaisis a la main sur les 4 nouveaux champs.

## Decision
4 nouveaux champs sur `user` (`field_discount_retrovision_ext`/`retrovision_int`/
`telecommande_vor`/`pedalier`, decimal 5,2, non requis, admin uniquement — memes reglages
que l'ancien champ), resolus par un nouveau service `PartnerDiscountResolver::resolve
(?UserInterface $partner, ?string $catalogType): ?float`. `QuoteEquipmentLine::
dm_discount_rate` perd sa valeur par defaut (NULL tant que non enregistre) ; le calcul
effectif se fait desormais TOUJOURS depuis `unit_price` (jamais `discounted_unit_price`,
simple instantane de creation) : `effectif = unit_price * (1 - (dm_discount_rate ??
partnerRateFallback ?? 0) / 100)`. `QuoteCalculator` resout aussi le taux par equipement
(au lieu d'un taux unique) pour figer `discounted_unit_price`/`discounted_ht` a la creation
du devis (ce que voit le partenaire pendant qu'il configure). Les 4 nouveaux champs sont
ajoutes a `_drivematic_partner_profile_field_names()` (drivematic_partner.module) pour
rester masques du partenaire sur `/user/{uid}/edit`, memes conditions que l'ancien champ.

## Addendum (2026-09-03) : formulaire regroupe par TYPE, pas par ligne/configuration

Retour utilisatrice apres la 1ere livraison : `QuoteDiscountForm` affichait une section par
`QuoteConfiguration`, avec une ligne editable par `QuoteEquipmentLine` — sur un devis a
plusieurs vehicules, un meme type d'equipement (ex. « Rétrovision extérieure ») presente
dans 2 configurations exigeait de saisir 2 fois le meme taux. Demande : exactement **4
lignes** (une par type d'equipement, jamais plus), preremplies avec la remise partenaire,
et dont l'enregistrement remplace le taux **sur ce devis uniquement** (jamais sur le
compte partenaire, jamais sur un autre devis). Section renommee « Remises par équipement »
(elle ne porte plus la nuance « exceptionnelle », qui datait de la cascade abandonnee plus
haut).

**Decision** : `QuoteDiscountForm::buildForm()` construit une seule table de 4 lignes
fixes (`EQUIPMENT_TYPES`), regroupant toutes les `QuoteEquipmentLine` du devis par
`equipment_type` (`loadLinesByType()`) plutot que par configuration. Le prefill
(`resolveDefaultRate()`) reprend le taux explicite deja partage par les lignes de ce type
si une existe, sinon la remise partenaire courante. A la soumission, le taux saisi
s'applique **uniformement a toutes les lignes de ce type sur ce devis** — chaque LIGNE
individuelle continue de generer sa propre entree `QuoteDiscountChange` (granularite
inchangee, ADR-040), donc un devis a 2 configurations produit 2 entrees d'historique pour
un seul type modifie.

**Consequence sur les lignes anterieures a `equipment_type`** : une ligne sans ce champ
devient invisible depuis ce nouveau formulaire (impossible a rattacher a l'une des 4
lignes). `drivematic_configurator_update_11009()` retro-complete `equipment_type` a partir
de `label` (mapping fiable : 4 valeurs fixes, contrairement a un prix) — corrige la
limite « pas de reconstitution retroactive fiable » actee plus haut dans cet ADR, qui ne
tenait plus une fois cette cle demontree reconstructible sans ambiguite.

## Addendum 2 (2026-09-03) : abandon de la resolution live — snapshot gele a la creation

Correction de l'utilisatrice sur le comportement general (pas seulement le formulaire) :
un devis doit prendre les taux du compte partenaire **UNE FOIS, a sa creation**, et ne plus
jamais en bouger ensuite — jamais de suivi en direct des modifications ulterieures du
compte, contrairement a l'Option B retenue plus haut dans cet ADR (choisie a l'origine
pour que les devis deja crees avant l'existence des 4 champs profitent des valeurs saisies
apres coup). L'Option A (initialement ecartee) est donc finalement la bonne, mais
uniquement comme mecanisme **permanent** — la resolution live n'aura servi que de pont
temporaire pour rattraper les devis nes avant les 4 champs.

**Decision** : `QuoteCalculator::calculateConfiguration()` resout desormais le taux
partenaire UNE SEULE FOIS, au moment du calcul, et l'ajoute au tableau de la ligne
(`dm_discount_rate` en plus de `discounted_unit_price`) ; `QuotePersister` le persiste tel
quel a la creation. `QuoteEquipmentLine::getEffectiveDiscountedUnitPrice()`/
`getEffectiveDiscountedHt()` perdent leur parametre `$partnerRateFallback` : `NULL` est
desormais traite comme 0% par simple securite (jamais une resolution live), `PartnerDiscountResolver`
n'etant plus appele qu'a exactement 2 endroits : la creation (snapshot) et le tableau
« Résumé » de `QuoteDetailController` (qui reste, lui, un miroir volontairement live du
compte, confirme explicitement par l'utilisatrice — distinct du prix reellement applique
au devis). `QuotePdfGenerator` perd sa dependance a `PartnerDiscountResolver`, devenue
inutile.

**Rattrapage des devis existants** : `drivematic_configurator_update_11010()`
(remise a NULL des lignes jamais retouchees, pour permettre la resolution live) est
immediatement suivie de `drivematic_configurator_update_11011()`, qui transforme ce NULL en
snapshot definitif (taux partenaire resolu a cet instant, ou 0 si absent — plus jamais
NULL). Les deux mises a jour restent dans l'historique (jamais reecrites a posteriori,
convention du projet) : 11010 documente honnêtement l'intention initiale, immediatement
corrigee par 11011.

**Verifie en conditions reelles** : modifier `field_discount_retrovision_ext` du compte
partenaire de 10% a 99% ne change ni `dm_discount_rate` (reste a 10.00) ni le prix effectif
d'une ligne deja creee — seul un nouveau devis, ou une action explicite sur ce devis via
« Remises par équipement », peut desormais faire evoluer son prix.

## Consequences
- Fichiers impactes : 8 nouveaux/supprimes fichiers de config `field.*.user.*` (4 crees, 2
  supprimes) + `core.entity_form_display.user.user.default.yml` ; nouveau
  `Service/PartnerDiscountResolver.php` ; `drivematic_configurator.services.yml` (nouveau
  service + injection dans `quote_calculator`/`quote_pdf_generator`) ;
  `drivematic_configurator.install` (hook_update_11007 suppression `field_discount_rate`,
  hook_update_11008 ajout `equipment_type`) ; `Entity/QuoteEquipmentLine.php` (champ
  `equipment_type`, formule effective) ; `Service/QuoteCalculator.php`,
  `Service/QuotePersister.php`, `Form/QuoteForm.php` (comptent le partenaire, plus le taux
  brut) ; `Form/QuoteDiscountForm.php` (prefill + comparaison sur le taux effectif) ;
  `Form/QuoteMarkOrderedForm.php` (gel) ; `Controller/QuoteDetailController.php` (resume : 4
  lignes de remise au lieu d'1, fallback pour le tableau des lignes) ;
  `Service/QuotePdfGenerator.php` (meme fallback) ; `drivematic_partner.module`
  (exclusion des 4 nouveaux champs).
- Un devis cree AVANT cette feature (equipment_type NULL) affiche « pas de remise » via le
  fallback tant qu'il n'a jamais ete retouche — accepte explicitement par l'utilisatrice.
- Aucune nouvelle route, aucun nouveau handler d'acces, aucune nouvelle permission :
  `PartnerDiscountResolver` est un simple service de lecture, sans logique d'autorisation
  propre (protege par les controles deja en place sur les appelants).
