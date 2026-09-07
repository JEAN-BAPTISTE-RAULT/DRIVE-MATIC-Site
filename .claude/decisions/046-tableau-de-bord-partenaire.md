# ADR-046 : Tableau de bord partenaire (F13, étape 1/2)

## Statut

Accepté

## Date

2026-09-07

## Contexte

Nouvelle page d'accueil de l'espace partenaire (maquettes Figma 491-13703
desktop / 604-34427 mobile) : 2 raccourcis vers le configurateur, 3
compteurs de devis par groupe de statut, chacun redirigeant vers une future
page de listing (`/user/mes-devis`, étape 2, hors périmètre de cet ADR).
Le lien de menu « Tableau de bord » (menu_link_content id 42) existait déjà
en `<nolink>`, placeholder posé à l'avance.

## Options considérées

### Emplacement de la route/du contrôleur

- **Module `drivematic_configurator`** (où vit déjà `QuoteDetailController`) :
  écarté — cette page est un point d'entrée de l'espace partenaire, pas une
  vue de devis à proprement parler.
- **Module `drivematic_partner`** (retenue) : dépend déjà de
  `drivematic_configurator` (confirmé), cohérent avec `PersonalInformationForm`
  qui y établit déjà le pattern « page partenaire, route `_role: partenaire` ».

### Rendu des cartes : SDC unique paramétré vs composants dupliqués

- **4 templates quasi identiques** : écarté, duplication massive (rule
  simplicité).
- **Un SDC parent (slot `items`) + un SDC enfant paramétré** (retenue) :
  reprend le pattern déjà établi par `triptych`/`triptych-element`. Enfant
  `dashboard-action-card` (props `variant`, `label`, `count`, `icon`, `href`),
  parent `dashboard-actions` (slot `items`).

### Bloc CTA (image + titre + description + bouton) : réutiliser `jumbo-home-element` vs SDC dédié

- **Étendre `jumbo-home-element`** (slot `description` en plus) : écarté —
  coupler un composant homepage à un usage tableau de bord non prévu pour
  lui, alors qu'aucun des deux n'a de besoin de flexibilité au-delà de son
  usage actuel.
- **Nouveau SDC dédié `dashboard-quote-cta`** (retenue) : même esprit que
  `login-panel` (SDC propre à une page précise, pas un composant éditorial
  réutilisable). Contenu entièrement statique (image, titre, description,
  bouton) — confirmé par l'utilisatrice ; seuls `href` et l'URL de l'image
  varient (props).

### Comptage des devis : `accessCheck(FALSE)` vs handler d'accès dédié

- **`QuoteAccessControlHandler` (accès `view` par propriétaire)**, comme
  `DeliveryAddressAccessControlHandler` : nécessaire pour la future page de
  listing (liens vers des devis individuels), mais hors périmètre ici — le
  tableau de bord n'affiche que des agrégats, jamais un devis individuel.
- **`accessCheck(FALSE)` sur une requête déjà bornée à `uid = currentUser()->id()`**
  (retenue pour cette étape) : aucune donnée de devis individuelle exposée,
  requête jamais paramétrée par un identifiant transmis par le client.

## Décision

- Route `drivematic_partner.dashboard` (`/user/tableau-de-bord`,
  `_role: 'partenaire'`), contrôleur `DashboardController::build()` (pas de
  constructeur injectant les propriétés de `ControllerBase` — piège déjà
  documenté, ADR-037).
- 3 compteurs : `STATUS_A_FINALISER` seul, `STATUS_A_COMMANDER` seul,
  `STATUS_COMMANDE` + `STATUS_ARCHIVE` réunis (correspond exactement au
  3e onglet prévu pour la future page de listing).
- Liens des 3 compteurs posés en dur (`/user/mes-devis?onglet=...`, chaîne
  littérale) : la route n'existe pas encore. Convention actée avec
  l'utilisatrice : une seule route `/user/mes-devis`, `?onglet=a-finaliser
  |en-cours|archives` sélectionne l'onglet actif — à remplacer par
  `Url::fromRoute()` dès la création de cette page.
- SDC nouveaux : `dashboard-actions` (parent, slot `items`),
  `dashboard-action-card` (enfant, variantes `action`/`counter`),
  `dashboard-quote-cta` (image/titre/description/bouton). Bouton du CTA :
  mixin partagé `dm-btn-grey` + `dm-btn-height(text)` (ADR-029), pas de
  valeurs redupliquées.
- Icônes manquantes (`folder-edit`, `folder-clock`, `archive`) exportées
  depuis Figma vers `images/icons/`. Image statique (`dashboard-vehicle.webp`,
  convertie côté agent, 1200px/webp) dans `images/`, hors pipeline
  media/image-style (confirmé par l'utilisatrice : pas de gestion BO).
- Cache : `#cache.contexts = ['user']` + tags de liste de l'entité `quote` —
  sans quoi le Dynamic Page Cache aurait pu servir les chiffres d'un
  partenaire à un autre (vérifié avec un second compte partenaire
  temporaire, supprimé après test).
- Menu link id 42 repointé vers `route:drivematic_partner.dashboard`
  (contenu, pas config versionnée — édité directement en base, comme le
  reste des liens de ce menu).

## Conséquences

- **Piège rencontré** : un SDC rendu via `#type: component` (render array
  PHP, pas un `{% embed %}` Twig) n'hérite PAS des variables globales de
  page (`base_path` notamment) — `active_theme_path()` seul produit un
  chemin relatif résolu contre l'URL COURANTE, pas la racine du site.
  Solution : calculer l'URL absolue en PHP (`base_path() .
  $themeExtensionList->getPath('drive_matic') . '/images/...'`, même
  pattern que `QuotePdfGenerator::getPath()`) et la passer en prop.
- **Piège rencontré** : la validation de schéma d'un prop `enum` avec
  `default:` dans le YAML du composant n'est PAS appliquée par Drupal quand
  le prop est omis du tableau `#props` passé en PHP — la valeur validée
  reste vide plutôt que de retomber sur le défaut déclaré. Toujours passer
  explicitement chaque prop `enum`, ne pas compter sur son `default:` YAML
  pour un rendu via render array.
- **Découverte collatérale majeure, traitée dans ADR-047** : le bloc de
  titre de page ne s'affichait sur AUCUNE route hors node (bug préexistant,
  affectait déjà `/user/mes-informations-personnelles`) — corrigé dans le
  même mouvement, cf. ADR-047 pour le détail et son interaction avec
  ADR-024/027.
- Reste à faire (étape 2, session ultérieure) : page `/user/mes-devis` (3
  onglets), archivage manuel côté partenaire (menu déroulant sur une ligne
  de devis « Commandé », cf. ADR-045), remplacement des 3 liens en dur par
  `Url::fromRoute()`.
- Fichiers créés : `drivematic_partner/src/Controller/DashboardController.php`,
  route dans `drivematic_partner.routing.yml`, 3 SDC (`dashboard-actions`,
  `dashboard-action-card`, `dashboard-quote-cta`), 3 icônes + 1 image
  statique.
