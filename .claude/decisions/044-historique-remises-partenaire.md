# ADR-044 : Historique des changements de remise partenaire

## Statut
Accepte

## Date
2026-09-03

## Contexte
Depuis ADR-043 (addendum 2), un devis fige les 4 remises du compte partenaire a sa
creation et ne les suit plus jamais en direct ensuite. Consequence attendue mais qui
peut surprendre : un devis cree avant un changement de remise garde son ancien taux,
meme si le compte affiche un taux different aujourd'hui. Retour utilisatrice : eviter les
reclamations du type « pourquoi mon client a un devis a 5% alors que j'ai renseigne 10% »
— Drive Matic doit pouvoir verifier QUAND un taux a reellement change, vers quelle
valeur, et par qui, pour corroborer avec la date de creation du devis concerne.

## Decision

**Perimetre** : uniquement les 4 champs de remise par equipement
(`field_discount_retrovision_ext/retrovision_int/telecommande_vor/pedalier`) — pas
l'ensemble du profil partenaire. Correspond exactement au cas d'usage cite ; le reste du
profil (nom, societe, adresse...) n'a pas ce meme besoin de correlation temporelle avec
les devis.

**Detection** : nouvelle implementation `drivematic_configurator_user_update()`
(`hook_ENTITY_TYPE_update()` pour `user`) — generique, declenchee par TOUT enregistrement
d'un compte existant (formulaire admin `/user/{uid}/edit`, `drush`, script), pas un submit
handler de formulaire specifique. Compare `$account->original` et `$account` pour chacun
des 4 champs ; une entree `partner_discount_change` est creee par champ reellement
change (comparaison stricte NULL/valeur, jamais de cast implicite a 0). Une sauvegarde qui
ne touche aucune des 4 remises ne cree aucune entree.

**Entite** `PartnerDiscountChange` (`web/modules/custom/drivematic_configurator/src/Entity/PartnerDiscountChange.php`) :
`partner_id` (entity_reference→user, requis), `equipment_type` (string, requis, memes
cles que `QuoteEquipmentLine::equipment_type`), `old_rate`/`new_rate` (decimal 5,2,
**nullables** — contrairement a `QuoteDiscountChange`, ou NULL n'a plus lieu d'etre
depuis ADR-043 addendum 2), `uid` (entity_reference→user, non requis, l'administrateur
ayant fait la modification), `created`. Meme pattern que `QuoteStatusChange`/
`QuoteDiscountChange` : aucun handler d'acces propre.

**Affichage** : nouvelle fonction `_drivematic_partner_add_discount_history_summary()`
dans `drivematic_partner.module`, appelee depuis `drivematic_partner_form_user_form_alter()`
au meme endroit et sous la meme garde que le recapitulatif des adresses de livraison deja
existant (`!$editing_own_account && $is_admin`) : jamais visible en auto-edition, ces
remises restant une donnee commerciale interne. Tableau (Date / Equipement / Modification /
Modifie par), trie chronologiquement (comme l'historique d'un devis) ; un changement
vers/depuis « vide » s'affiche explicitement `« — (vide) »`, jamais confondu avec 0%.

**NULL et 0% distincts** : `old_rate`/`new_rate` restent des `decimal` nullables (pas de
`?? 0.0`) — l'absence de remise negociee est un etat metier reel et permanent, jamais
assimilable a un taux de 0% explicitement choisi.

**Pas de retro-datation** : les remises deja en place sur les comptes partenaire existants
n'ont aucune entree d'historique tant qu'elles n'auront pas ete modifiees apres ce
deploiement — meme limite assumee que `QuoteStatusChange`/`QuoteDiscountChange` (« on ne
fabrique pas un evenement qu'on ne connait pas reellement »).

**Modules** : entite + detection dans `drivematic_configurator` (deja proprietaire de
`PartnerDiscountResolver` et des entites d'historique similaires) ; affichage dans
`drivematic_partner` (deja proprietaire de la personnalisation de `user_form`) — `
drivematic_partner` **depend deja** de `drivematic_configurator` (sens unique), aucune
nouvelle dependance introduite.

## Verifie en conditions reelles
- Un changement reel (compte impersonne en admin via `drush`, pour eviter tout risque sur
  le vrai compte partenaire via un POST HTML reconstruit a la main) cree bien une entree,
  avec le bon auteur (`\Drupal::currentUser()`).
- Une sauvegarde touchant un champ SANS le modifier ne cree aucune entree.
- 2 champs modifies dans la meme sauvegarde produisent 2 entrees distinctes.
- Passage vide → valeur ET valeur → vide affiches correctement (`« — (vide) » → X %` et
  inversement).
- Section absente en auto-edition partenaire (0 occurrence dans le HTML recu).

## Consequences
- Fichiers impactes : nouveau `Entity/PartnerDiscountChange.php` ;
  `drivematic_configurator.install` (hook_update_11011) ;
  `drivematic_configurator.module` (`drivematic_configurator_user_update()`) ;
  `drivematic_partner.module` (`_drivematic_partner_add_discount_history_summary()` +
  2 helpers de formatage).
- Aucune nouvelle route, aucun nouveau formulaire, aucune nouvelle permission.
