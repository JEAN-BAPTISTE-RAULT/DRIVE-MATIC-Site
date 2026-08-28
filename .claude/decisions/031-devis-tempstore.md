# ADR-031 : Écran Devis (F14 étape 2/3) — PrivateTempStore, pas de persistance avant l'étape 3

## Statut
Accepte

## Date
2026-08-27

## Contexte
F14 (configurateur, 3 étapes) devait afficher un devis chiffré à l'étape 2, en s'appuyant sur
le catalogue de tarifs (ADR-030) et le taux de remise partenaire (ADR-026, `field_discount_rate`).
Question ouverte : comment faire transiter les données de l'étape 1 (véhicules/équipements) vers
l'étape 2 sans persistance BDD, l'utilisatrice ne souhaitant créer aucun enregistrement avant
l'étape 3 (Livraison) — sur un clic explicite « Enregistrer le devis » (→ statut « à finaliser »)
ou « Commander » (→ « à commander »/« commande en cours »).

## Options considerees

### Mecanisme de brouillon
**Option A (retenue) : `PrivateTempStore`** — mécanisme natif Drupal pour ce cas exact
(formulaires multi-étapes sans BDD intermédiaire). Scope automatiquement par utilisateur courant
(le cookie de session ne porte qu'un identifiant, jamais les données elles-mêmes).
**Option B écartée : stocker dans un cookie côté client** — proposition initiale de
l'utilisatrice, reformulée après clarification technique (limite de taille, pas de mécanisme
natif pour y stocker des structures complexes en sécurité).

Un seul brouillon par partenaire à la fois (clé fixe `draft`, collection `drivematic_configurator`) :
cohérent avec le parcours strictement linéaire actuel.

## Decision
`PrivateTempStore`, collection `drivematic_configurator`, clé `draft` — structure identique aux
valeurs soumises par `ConfigurationForm` (aucune transformation), pour que les deux formulaires
se relisent l'un l'autre sans mapping.

Nouveau service `QuoteCalculator` (`drivematic_configurator`) : calcule lignes et totaux à partir
du brouillon + catalogue + taux de remise. Pensé pour être **rejoué tel quel par la future
persistance F15** (mêmes formules pour geler les prix à la création d'un devis) — aucune
transformation propre à l'affichage n'y est mêlée.

Nouvelle route `/configurer/devis` (`QuoteForm`, FormBase) : Modifier (lien vers `/configurer`),
Supprimer (retire du brouillon, rebuild sans AJAX — plus simple qu'un état client à synchroniser),
Ajouter une configuration (ajoute un bloc vide au brouillon puis redirige étape 1), CTA final en
placeholder (étape 3 n'existe pas).

`ConfigurationForm` (étape 1) modifiée pour lire le brouillon au premier rendu (préremplit les
blocs) — nécessaire pour que Modifier/Ajouter une configuration depuis l'étape 2 fonctionnent.

## Deux bugs reels decouverts en verifiant (pas de simple relecture de code)

1. **`QuoteCalculator::loadPrice()`** filtrait sur `vehicle_model` même pour les tarifs fixes
   (rétrovision ext./int.), qui ont ce champ à `NULL` en base (ADR-030) — la requête ne
   correspondait donc plus à rien, rétrovision ressortait systématiquement "tarif indisponible".
   Corrigé : ne filtrer sur `vehicle_model`/`motorisation` que pour les types qui en dépendent
   réellement (`telecommande_vor`, `pedalier`).
2. **`drivematic_forms/js/vehicle-select.js`** reconstruit inconditionnellement les options de
   modèle/motorisation à l'attache (pas seulement sur un changement utilisateur) et réinitialisait
   systématiquement leur valeur à vide — invisible jusqu'ici (rien ne préremplissait jamais ces
   champs), mais effaçait silencieusement le brouillon préchargé dès l'arrivée sur `/configurer`.
   Corrigé : `rebuild()` préserve la valeur courante quand elle reste valide dans la nouvelle
   liste d'options.

Les deux ont été détectés en calculant un devis connu à la main puis en le comparant à l'affichage
réel (pas en relisant le code) — cf. CLAUDE.md, « conforme n'est pas intégré ».

## Consequences
- Le calcul de remise/TVA/totaux vit dans un seul endroit (`QuoteCalculator`), réutilisable par F15.
- Aucune entité Devis/Configuration/Ligne d'équipement créée à ce stade : reste un chantier séparé,
  déclenché uniquement par les 2 boutons de l'étape 3 (hors périmètre de cet ADR).
- Un brouillon expire avec la `PrivateTempStore` (durée par défaut du cœur) : un partenaire qui
  abandonne son parcours perd sa saisie — accepté, cohérent avec l'absence de persistance voulue
  avant l'étape 3.
