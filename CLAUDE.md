Directives comportementales pour réduire les erreurs de code courantes des LLM. À fusionner avec les instructions propres au projet selon les besoins.

**Compromis :** Ces directives privilégient la prudence à la rapidité. Pour les tâches triviales, fais preuve de discernement.

## 1. Réfléchir avant de coder

**Ne rien présumer. Ne pas masquer la confusion. Exposer les compromis.**

Avant de mettre en œuvre :
- Énonce explicitement tes hypothèses. En cas de doute, pose la question.
- Si plusieurs interprétations sont possibles, présente-les — ne choisis pas en silence.
- Si une approche plus simple existe, dis-le. Conteste lorsque c'est justifié.
- Si quelque chose n'est pas clair, arrête-toi. Nomme ce qui prête à confusion. Demande.

## 2. La simplicité d'abord

**Le minimum de code qui résout le problème. Rien de spéculatif.**

- Aucune fonctionnalité au-delà de ce qui a été demandé.
- Aucune abstraction pour du code à usage unique.
- Aucune « souplesse » ou « configurabilité » qui n'a pas été demandée.
- Aucune gestion d'erreurs pour des scénarios impossibles.
- Si tu écris 200 lignes alors que 50 suffiraient, réécris-le.

Pose-toi la question : « Un ingénieur senior dirait-il que c'est trop compliqué ? » Si oui, simplifie.

## 3. Des modifications chirurgicales

**Ne touche qu'à ce qui est nécessaire. Ne nettoie que tes propres dégâts.**

Lors de la modification de code existant :
- N'« améliore » pas le code, les commentaires ou la mise en forme adjacents.
- Ne refactorise pas ce qui n'est pas cassé.
- Respecte le style existant, même si tu aurais fait autrement.
- Si tu repères du code mort sans rapport, signale-le — ne le supprime pas.

Lorsque tes modifications créent des éléments orphelins :
- Supprime les imports, variables et fonctions que TES modifications ont rendus inutilisés.
- Ne supprime pas le code mort préexistant, sauf si on te le demande.

Le test : chaque ligne modifiée doit pouvoir se rattacher directement à la demande de l'utilisateur.

## 4. Une exécution guidée par l'objectif

**Définir les critères de réussite. Boucler jusqu'à vérification.**

Transforme les tâches en objectifs vérifiables :
- « Ajouter une validation » → « Écrire des tests pour les entrées invalides, puis les faire passer »
- « Corriger le bug » → « Écrire un test qui le reproduit, puis le faire passer »
- « Refactoriser X » → « S'assurer que les tests passent avant et après »

Pour les tâches en plusieurs étapes, énonce un plan succinct :

```
1. [Étape] → vérifier : [contrôle]
2. [Étape] → vérifier : [contrôle]
3. [Étape] → vérifier : [contrôle]
```

Des critères de réussite solides te permettent de boucler de façon autonome. Des critères faibles (« fais que ça marche ») exigent des clarifications constantes.

---

**Ces directives fonctionnent si :** il y a moins de modifications inutiles dans les diffs, moins de réécritures dues à une complexité excessive, et les questions de clarification arrivent avant la mise en œuvre plutôt qu'après les erreurs.

## Guidelines spécifiques du projet DRIVE-MATIC

> Instructions operationnelles pour l'agent IA. Ne contient **que** ce qui ne peut pas etre deduit du code ou du README.

### Commandes de verification (source de verite)

Prerequis : `npm install` + `composer install` (une fois). Ces commandes doivent **toutes** passer avant de considerer le travail termine :

- Lint (JS + SCSS + PHP) : `npm run lint`
- Format (verification) : `npm run format:check`
- Tests : `npm test` _(placeholder tant que la strategie de test n'est pas definie — voir docs/PRD.md)_

Details : `npm run lint` enchaine `lint:js` (ESLint), `lint:css` (Stylelint), `lint:php` (PHPCS standards Drupal + DrupalPractice via `composer lint`). Correction auto : `npm run lint:fix` et `npm run format`. Pas de `typecheck` (JS vanilla, pas de TypeScript).

Regle : ne JAMAIS considerer le travail termine si l'une de ces commandes echoue.

**Toute modification SCSS exige un build** : les `.css` compiles sont versionnes a cote des `.scss` et c'est eux que Drupal sert. Enchainer `npm run css` puis **verifier dans le `.css` genere** qu'une valeur modifiee y figure. Le build exige **node >= 20** (`nvm use`) : sous une version plus ancienne il echoue sur `ERR_REQUIRE_ESM` et, si la sortie est redirigee, il *parait* reussir en laissant des `.css` perimes — on debugge alors un rendu qui ne correspond plus au source.

### Repartition de la documentation (ne jamais dupliquer entre fichiers)

| Fichier | Responsabilite | Contenu |
|---------|---------------|---------|
| **CLAUDE.md** (ce fichier) | Instructions agent IA | Conventions de code, garde-fous, regles metier, meta-instructions |
| **README.md** | Documentation technique de reprise | Architecture, structure, commandes, IDs, pipeline, workflow, points d'attention |
| **docs/PRD.md** | Specifications fonctionnelles | Features, modele de donnees, milestones, interface, tests, cas limites |
| **docs/E2E_SCENARIOS.md** | Scenarios E2E de non-regression | Parcours utilisateur a rejouer |
| **.claude/decisions/** | Decisions architecturales (ADR) | Decisions posterieures au PRD, avec contexte et raisonnement |
| **.claude/rules/*.md** | Regles modulaires path-scoped | Regles specifiques a un sous-dossier (monorepos) |

**Regle** : si une information est factuelle (un ID, une commande, une structure), elle va dans le README. Si c'est une specification fonctionnelle (une feature, un algorithme, un critere d'acceptation), elle va dans le PRD. Le CLAUDE.md ne contient que les directives comportementales pour l'agent.

### Conventions de code

Le formatage (indentation, guillemets, longueur de ligne) est gere par le linter/Prettier — ne pas le documenter ici. Ci-dessous, uniquement ce que le linter **ne peut pas** appliquer.

**Langue** : commentaires et documentation en francais. Identifiants du code (fonctions, hooks, variables, classes CSS, cles Twig) en anglais, conformement aux standards Drupal.

**PHP / Drupal**

- Prefixer toute fonction du code custom par le _machine name_ du module ou du theme (ex. `drivematic_partner_preprocess_node()`), et implementer les hooks sous la forme `MACHINE_NAME_hook_name()`.
- Ne jamais construire de requete SQL par concatenation : passer par l'API Database (requetes parametrees) ou l'Entity Query API.
- Textes visibles par l'utilisateur : toujours traduisibles — `t()` / `\Drupal::translation()` cote PHP, filtre `|t` cote Twig.
- Recuperer les services via l'injection de dependances dans les classes ; reserver `\Drupal::service()` au code procedural (`.module`, hooks).
- **Exception : une `ConfigFactoryOverride` n'injecte rien.** Elle est construite par `config.factory`, qui devient une dependance du traducteur de chaines des que `locale` est installe : injecter un service y referme une boucle et **le conteneur refuse de se construire** — plus une page, plus meme de bootstrap Drush. Resoudre le service paresseusement dans la methode qui s'en sert, et declarer la derogation par un `phpcs:ignore` cible. Cas vecu sur `drivematic_home`.

**Twig**

- Ne jamais desactiver l'auto-echappement (`|raw`, `verbatim`) sans justification de securite explicite : c'est le principal vecteur XSS cote theme.
- Logique metier dans le preprocess PHP, pas dans le template : le Twig reste presentationnel.

**JavaScript (vanilla)**

- Encapsuler le comportement dans `Drupal.behaviors.<name> = { attach(context, settings) { … } }`, le tout dans une IIFE (`(function (Drupal, once) { … })(Drupal, once);`).
- Utiliser `once('<id>', selector, context)` pour ne lier un handler qu'une seule fois (evite le double-binding lors des rechargements AJAX).
- Passer les donnees serveur -> client via `drupalSettings`, jamais en dur dans le JS ; ne jamais y exposer de donnee sensible.

**SCSS / SDC**

- Tout composant front est un **SDC** (Single Directory Component) : Twig + CSS (+ JS) co-localises et scopes dans le dossier du composant. Rien hors SDC, hormis les **fondations globales** (reset, tokens/variables, typographie).
- Nommage **BEM** (`.block__element--modifier`) a l'interieur de chaque composant.
- Pas de couleurs/tailles en dur : utiliser les tokens (variables SCSS / custom properties).
- **Espacement — trois tokens, trois roles** ([ADR-013](.claude/decisions/013-espacement-et-unites.md)) : `--dm-space-element` (entre elements d'un bloc), `--dm-space-block` (`padding-block` du bloc), `--dm-gutter` (`padding-inline`). Ne jamais reintroduire de valeur d'espacement en dur.
- **Gouttiere = `padding-inline`, jamais `margin-inline: auto`** : celui-ci ne fait que **centrer** une largeur plafonnee et ne garantit aucun ecart au bord sous ce plafond. Le plafond vaut « contenu + 2 gouttieres ». Exceptions : gouttiere d'un seul cote quand la piste deborde volontairement, et bloc full-bleed quand le fond court d'un bord a l'autre.
- **Unites : espacement en `px`, typographie en `rem`.** Une gouttiere ne grossit pas parce que le texte grossit ; les tailles de police, elles, doivent suivre la preference du navigateur (WCAG 1.4.4). Reserver `em` a ce qui est lie au texte lui-meme.
- **Un ecart appartient au bloc, pas au contenu saisi** : les marges du premier et du dernier enfant d'un champ texte riche sont neutralisees dans les fondations (`.text-formatted`). Et une marge ne se pose que s'il y a un element en dessous (`:not(:last-child)`) — les champs etant optionnels, un titre peut etre le dernier element affiche.
- **Icones** : quand la maquette fournit un glyphe, utiliser l'**asset SVG exporte** auto-heberge dans `images/icons/`, applique en `mask` CSS avec `background-color` sur un token (permet d'heriter de `currentcolor`). Ne pas redessiner un glyphe en bordures/rotations : c'est moins fidele et ca diverge du reste du thème. Reutiliser un asset existant avant d'en ajouter un.

**Media / images**

- Toute image passe par la **media-library** et un **image style defini** (responsive, aligne sur les breakpoints du site), sortie **WebP**. Jamais de dimension/crop en dur ni de `<img>` hors image styles. Reference : etude images (docs/PRD.md §7).

### Convention de formulaire back-office (TOUS les types de contenu)

Regle posee par l'utilisatrice, a appliquer **a chaque nouveau type de contenu** (et a verifier apres tout ajout de champ) :

- **Deux onglets horizontaux** (`field_group`, format `tabs` / `direction: horizontal`, groupes `group_tabs` > `group_general` + `group_content`) :
  - **« Informations generales »** : `title`, `path`, `field_meta_tags`, `status` — dans cet ordre.
  - **« Contenu »** : tous les autres champs, **les paragraphes en dernier**.
- **Champs jamais proposes a la saisie** : `uid`, `created`, `simple_sitemap`, `url_redirects`. Les deux premiers se desactivent dans le form display ; les deux autres sont ajoutes par leurs modules **sans consulter le form display** et sont retires en `#after_build` (`drivematic_forms_form_node_form_alter()`) — pas dans un `hook_form_alter`, qui passe avant eux.
- **Un seul titre : le `title`** ([ADR-014](.claude/decisions/014-titre-unique-porte-par-le-title.md), qui **remplace l'ADR-011**). Le `title` du node alimente l'affichage, le fil d'Ariane, le motif d'alias et la balise `title`. Il n'y a **plus de `field_title` sur les types de contenu** : ne pas le recreer. ⚠️ `field.storage.paragraph.field_title` est un champ **homonyme et distinct** (titre des blocs, 21 bundles, 21 templates SDC) — ne jamais le confondre avec l'ancien champ des nodes. Tout **nouveau type public** recoit : son `base_field_override` de `title`, un defaut `metatag.metatag_defaults.node__<bundle>` en `[node:title] | [site:name]`, et son motif Pathauto. Tout rendu hors page canonique (carte, teaser, ligne de vue) lit le `title`.
- **`title` + `body` sur tout type indexable, pour le SEO** : `title` alimente la balise `title`, `body` la meta description. Mais **ce qu'un type en affiche lui est propre et ne se deduit pas** — verifier son view display avant de conclure : `transform` et `product` **n'affichent ni l'un ni l'autre** (toute la page vient des paragraphes, dont le contenu est **libre** : aucun champ du node n'y est recopie ni force) ; les autres types affichent **`title` seul ou `title` + `body`**, au-dessus de leurs paragraphes / Vues / webforms. Sur un type qui affiche deja son `title`, ne pas creer de paragraphe pour reporter le titre ou le chapo.
- **Ou s'affiche le `<h1>`** : sur les types dont la maquette place le titre dans un bloc (`homepage`, `transform`, `product`), le bloc titre de page est **masque** et c'est le premier paragraphe **portant un titre** qui rend le `<h1>`, via la prop `heading_level` des SDC `image-full` / `text-centered`. Partout ailleurs, le bloc titre rend le `title` en `<h1>` au-dessus du contenu. Deux listes doivent rester alignees : `_drive_matic_hero_title_bundles()` et la condition `entity_bundle:node` (negee) de `block.block.drive_matic_page_title`. Un ecart donne une page a deux `<h1>` ou a aucun.
- **Alias d'URL** : motif Pathauto `/[node:title]` pour tout node public ; `news` fait exception avec `/actualites/[node:title]`. Un nouveau type public a besoin de son propre `pathauto.pattern.node_<bundle>` — **sauf s'il est a exemplaire unique** : dans ce cas **pas de motif du tout** et un alias **en dur** sur le node (`path` avec `pathauto: 0`), pour garder une URL courte quand le titre de la maquette est une accroche redigee. Cas actuels : `configurator` -> `/configurer`, `faq` -> `/faq`. Corollaire general : **renommer un contenu publie change son alias**.
- ⚠️ **Changer un alias a la main ne supprime pas l'ancien** : les deux repondent alors en **200** et le node existe a deux URL (contenu duplique). Pathauto ne cree une redirection que lorsque **lui** gere le changement. Apres un alias pose en dur, verifier `path_alias` pour le node (`loadByProperties(["path" => "/node/<nid>"])`), **supprimer l'entree perimee** et creer le 301 explicitement.
- **Sitemap** : l'indexation est **opt-in**. Sans fichier `simple_sitemap.bundle_settings.default.node.<bundle>`, le type n'est **pas** indexe — silencieusement. Tout nouveau type public en a donc besoin ; les fragments s'appuient au contraire sur cette absence.
- **Nouveau type « fragment »** (contenu reutilisable sans page publique) : pas de `field_meta_tags`, pas de motif Pathauto, **pas** de reglage sitemap, et un `rabbit_hole.behavior_settings.node_type_<bundle>` en `access_denied`. Verifier le 403 en anonyme sur `/node/<id>`, jamais seulement l'absence de lien.
- Un champ ajoute a un type de contenu **doit etre range dans un des deux onglets** : sans groupe, il apparait hors onglets, en haut du formulaire.

Detail et raisons dans `docs/active/content-types/model.md`.

### Regles metier critiques

<!-- A REMPLIR AU FUR ET A MESURE — regles du domaine metier qui impactent les choix
     techniques. Ex : "Les identifiants stables sont les codes (cd_*), jamais les
     libelles (lb_*)" ou "Le prix TTC est toujours calcule cote serveur, jamais
     cote client" -->

Les interfaces publiques sont declarees dans la configuration du linter (globals/exports).
Les decisions architecturales posterieures au PRD sont dans `.claude/decisions/`.

### Ce que Claude ne doit JAMAIS faire

- Remettre en question les decisions d'architecture verrouillees (voir docs/PRD.md)
- **Travailler ailleurs que sur `main`** : pas de branche de feature, pas de worktree (`.claude/worktrees/`), pas d'isolation `worktree` pour un sous-agent. `main` est la seule branche du depot ; tout commit s'y fait directement. Regle posee par l'utilisatrice.
- **Modifier le core ou le code contrib** (`web/core/`, `web/modules/contrib/`, `web/themes/contrib/`) : passer exclusivement par hooks, plugins, event subscribers ou sous-theme. Si un patch contrib est indispensable, le gerer via `composer-patches`.
- **Se fier au frontend pour l'autorisation partenaire** : toute route ou ressource reservee re-verifie les droits cote serveur (permission, `hook_ENTITY_access`, `_custom_access`). Masquer un lien en Twig n'est pas un controle d'acces.
- **Exposer des donnees partenaire a un utilisateur anonyme** ou les injecter dans `drupalSettings` / le markup rendu au public.
- **Committer des secrets** : `settings.php`/`settings.local.php`, cles API, identifiants de base ou tokens ne sont jamais versionnes (utiliser variables d'environnement / fichiers ignores).
- **Executer une commande destructrice sur une base de donnees** (`drush sql:drop`, `drush sql:cli` avec DROP/TRUNCATE, `hook_update_N` destructif) sans confirmation explicite de l'utilisateur.
- **Neutraliser une protection de securite** : desactiver l'auto-echappement Twig, le CSRF, le Flood control ou la sanitation d'entrees pour "faire marcher" une fonctionnalite.
- **Placer du CSS ou du Twig hors d'un SDC** (hors fondations globales) : casse l'isolation et la reutilisabilite des composants.
- **Contourner le pipeline images** : crops/dimensions en dur, `<img>` hors media-library / image styles, ou sortie non-WebP.
- **Reactiver `gin_toolbar` ou le module core `navigation`** : les deux doivent rester desinstalles ([ADR-012](.claude/decisions/012-presentation-admin-front.md)). Le paquet `gin_toolbar` reste sur disque — `drupal/gin` l'exige en dependance ferme — donc rien n'empeche techniquement de l'activer : la barre d'administration du front changerait sans autre signe.
- **Charger un asset front tiers via CDN** : toute librairie JS/CSS ou police tierce est **auto-hebergee / vendorisee** dans le theme (`vendor/`), jamais appelee depuis un CDN externe (RGPD ; cf. ADR-008 et PRD §6).

### Securite du code

A chaque ajout ou modification de code, **systematiquement** verifier :

- Absence de vulnerabilites applicatives (injection, XSS, IDOR, exposition de donnees sensibles, etc.)
- Respect du principe de moindre privilege (ne pas accorder plus de droits que necessaire)
- Validation et assainissement de toutes les entrees utilisateur
- Aucune donnee sensible en dur dans le code (tokens, IDs secrets, mots de passe)
- Re-verification des droits cote serveur avant chaque action protegee (ne jamais se fier uniquement au frontend)

Si un doute existe sur la securite d'un changement, le signaler explicitement avant de l'implementer.

### Process de developpement

#### Avant de coder : planifier (`/plan`)

**Obligatoire** avant toute implementation non triviale (nouvelle feature, modification de comportement, bug complexe). Ne pas commencer a coder avant que le plan soit valide par l'utilisateur. Le skill `/plan` structure l'analyse : intention, fichiers impactes, interfaces, securite, risques, coherence PRD, etapes.

#### Pendant le dev : decisions architecturales (ADR)

Si une decision technique non triviale est prise (choix d'approche, changement d'interface entre modules, trade-off), creer un ADR dans `.claude/decisions/` en suivant le template. Cela trace le **pourquoi**, pas seulement le **quoi**.

#### Fin de session : synchroniser (`/sync`)

Quand l'utilisateur invoque `/sync`, passer en revue **toute** la documentation et ne mettre a jour que ce qui a reellement change :
- `docs/PRD.md` — specifications fonctionnelles
- `README.md` — documentation technique
- Configuration du linter — globals/exports cross-fichiers
- `docs/E2E_SCENARIOS.md` — scenarios de non-regression
- `CLAUDE.md` — regles, conventions, pieges
- `.claude/decisions/` — ADR manquants
- Memoire auto — apprentissages de la session

#### Apres chaque implementation : self-review

Avant de considerer une implementation terminee, repondre a ces 3 questions :
1. Quelle a ete la decision la plus difficile ?
2. Quelles alternatives ai-je rejetees et pourquoi ?
3. De quoi suis-je le moins confiant ?

Consigner les reponses dans `docs/active/<feature>/verification.md` si applicable (voir template dans `docs/VERIFICATION_TEMPLATE.md`).

### Gestion du contexte

- Utiliser `/clear` entre chaque tache distincte, ou apres 2-3 tentatives ratees sur la meme approche
- Pour les sessions longues (>30 min sur le meme sujet) : resumer la progression dans `docs/active/<feature>/progress.md`, puis `/clear`, puis reprendre en lisant ce fichier
- Preferer la delegation de l'exploration aux sub-agents pour preserver le contexte principal
- **Regle des 2 echecs** : si 2 tentatives echouent sur la meme approche, s'arreter et demander une redirection a l'utilisateur

#### Compaction

Lors d'un `/compact`, toujours conserver :
- Le plan d'implementation en cours
- Les decisions architecturales prises dans cette session
- Les fichiers modifies et leur etat

Resumer le reste.

### Separation CLAUDE.md / memoire auto (MEMORY.md)

Les deux fichiers sont charges dans le system prompt a chaque session. Ne JAMAIS dupliquer entre les deux.

- **CLAUDE.md** (ce fichier) : regles prescriptives, conventions, garde-fous, process — ce que l'agent **doit faire**
- **Memoire auto** : connaissances acquises, pieges decouverts, etat du code, details d'implementation — ce que l'agent **a appris**

Si une information est une regle → CLAUDE.md. Si c'est un apprentissage → memoire auto.
