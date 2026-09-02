# ADR-037 : Page de detail d'un devis (back-office) en Controller simple

## Statut
Accepte

## Date
2026-09-02

## Contexte
Le listing admin `/admin/content/devis` (Vue `quotes`, ajoute le
2026-09-01) ne montre qu'un resume (reference, partenaire, statut, total
TTC, date) — aucun moyen de consulter le contenu complet d'un devis
(configurations vehicule, lignes d'equipement, adresses gelees). Retour
utilisatrice : « il manque un outil essentiel : comment ouvrir un devis
pour consulter tout son contenu ».

L'entite `Quote` n'a ni `links`, ni `view_builder`, ni `access` handler
custom — seulement `views_data`. `QuoteConfiguration`/`QuoteEquipmentLine`
(sous-entites, referencees par `quote_id`/`configuration_id`) n'ont aucun
`admin_permission` ni handler d'acces propre. Aucun `Controller` (au sens
`ControllerBase`) n'existait nulle part dans le projet : tout est en
`_form` jusqu'ici.

## Options considerees

### Option A : pattern natif Drupal (`_entity_view` + `view_builder` + view modes)
- Avantages : pattern Drupal « canonique », Field UI pour configurer
  l'affichage sans toucher au code.
- Inconvenients : `Quote` n'a pas de champs simples a formater — son
  contenu utile (configurations + lignes, entites SEPAREES) n'est pas
  exprimable par des formatters de champ standard. Aurait exige un
  `EntityViewBuilder` custom (`buildComponents()` surcharge) PLUS la
  config Field UI (`core.entity_view_display.quote.quote.default.yml`)
  pour un gain nul — la vue reste, dans les faits, ecrite a la main.
  Machinerie disproportionnee pour une page de consultation interne.

### Option B (retenue) : route `_controller` classique + render array pur
- Avantages : chirurgical — un `Controller`, un render array Drupal
  (`#type: table`/`#type: details`), zero nouveau `hook_theme()`, zero
  template Twig, zero SDC/CSS custom. Coherent avec le fait que
  `/admin/*` utilise deja le theme admin (verifie : la route de la Vue a
  `_admin_route: true`), jamais le theme front `drive_matic`.
- Inconvenients : premier `ControllerBase` du projet (aucun pattern a
  copier tel quel) ; `$quote->toUrl('canonical')` ne fonctionne que parce
  que `links: canonical` est neanmoins declare sur l'entite (necessaire
  pour que la Vue puisse lier la reference, independamment du choix
  Controller vs `_entity_view`).

## Decision
Option B. `links: ['canonical' => '/admin/content/devis/{quote}']` ajoute
a l'attribut de `Quote` (necessaire pour que Views resolve un lien via
`link_to_entity: true`, et pour `$quote->toUrl()`). Nouveau
`QuoteDetailController` (`entity.quote.canonical`), qui charge
`QuoteConfiguration`/`QuoteEquipmentLine` via `getQuery()->condition(...)
->sort('weight')` — jamais de controle d'acces sur ces sous-entites,
l'acces au `Quote` parent (deja verifie par `_entity_access: 'quote.view'`
sur la route) suffit.

**Aucun handler d'acces custom ecrit** : verifie dans le code source de
Drupal core (`EntityAccessControlHandler::checkAccess()`) que le handler
d'acces PAR DEFAUT accorde deja automatiquement toutes les operations a
tout compte ayant la permission `admin_permission` de l'entite — deja
declaree sur `Quote` (`view drivematic configurator quotes`). Le handler
par defaut suffit donc integralement.

## Consequences
- Reference cliquable dans le listing (`views.view.quotes.yml`,
  `link_to_entity: true` sur le champ `reference`, precedemment `false`).
- Premier `Controller`/`ControllerBase` du projet — pattern reutilisable
  pour de futures pages de consultation admin en lecture seule.
- ⚠️ **Piege rencontre** : `ControllerBase` declare deja une propriete
  `protected $entityTypeManager` (NON typee, avec un getter paresseux
  `entityTypeManager()`). Redeclarer cette propriete avec un type explicite
  via la promotion de propriete du constructeur (`protected
  EntityTypeManagerInterface $entityTypeManager`) leve un fatal PHP («
  Type ... must not be defined »). Reflexe pour tout futur `Controller`
  de ce projet : ne jamais redeclarer `$entityTypeManager` (ni les autres
  proprietes deja fournies par `ControllerBase`) — soit utiliser la
  methode heritee `$this->entityTypeManager()`, soit injecter le service
  sous un autre nom de propriete.
- Fichiers impactes : `Entity/Quote.php`, `Controller/QuoteDetailController.php`
  (nouveau), `drivematic_configurator.routing.yml`,
  `views.view.quotes.yml`.
