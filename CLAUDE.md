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

**Twig**

- Ne jamais desactiver l'auto-echappement (`|raw`, `verbatim`) sans justification de securite explicite : c'est le principal vecteur XSS cote theme.
- Logique metier dans le preprocess PHP, pas dans le template : le Twig reste presentationnel.

**JavaScript (vanilla)**

- Encapsuler le comportement dans `Drupal.behaviors.<name> = { attach(context, settings) { … } }`, le tout dans une IIFE (`(function (Drupal, once) { … })(Drupal, once);`).
- Utiliser `once('<id>', selector, context)` pour ne lier un handler qu'une seule fois (evite le double-binding lors des rechargements AJAX).
- Passer les donnees serveur -> client via `drupalSettings`, jamais en dur dans le JS ; ne jamais y exposer de donnee sensible.

**SCSS**

- Architecture SMACSS (base / layout / component / state / theme) et nommage de classes en BEM (`.block__element--modifier`).
- Pas de couleurs/tailles en dur repetees : utiliser des variables SCSS ou des custom properties CSS.

### Regles metier critiques

<!-- A REMPLIR AU FUR ET A MESURE — regles du domaine metier qui impactent les choix
     techniques. Ex : "Les identifiants stables sont les codes (cd_*), jamais les
     libelles (lb_*)" ou "Le prix TTC est toujours calcule cote serveur, jamais
     cote client" -->

Les interfaces publiques sont declarees dans la configuration du linter (globals/exports).
Les decisions architecturales posterieures au PRD sont dans `.claude/decisions/`.

### Ce que Claude ne doit JAMAIS faire

- Remettre en question les decisions d'architecture verrouillees (voir docs/PRD.md)
- **Modifier le core ou le code contrib** (`web/core/`, `web/modules/contrib/`, `web/themes/contrib/`) : passer exclusivement par hooks, plugins, event subscribers ou sous-theme. Si un patch contrib est indispensable, le gerer via `composer-patches`.
- **Se fier au frontend pour l'autorisation partenaire** : toute route ou ressource reservee re-verifie les droits cote serveur (permission, `hook_ENTITY_access`, `_custom_access`). Masquer un lien en Twig n'est pas un controle d'acces.
- **Exposer des donnees partenaire a un utilisateur anonyme** ou les injecter dans `drupalSettings` / le markup rendu au public.
- **Committer des secrets** : `settings.php`/`settings.local.php`, cles API, identifiants de base ou tokens ne sont jamais versionnes (utiliser variables d'environnement / fichiers ignores).
- **Executer une commande destructrice sur une base de donnees** (`drush sql:drop`, `drush sql:cli` avec DROP/TRUNCATE, `hook_update_N` destructif) sans confirmation explicite de l'utilisateur.
- **Neutraliser une protection de securite** : desactiver l'auto-echappement Twig, le CSRF, le Flood control ou la sanitation d'entrees pour "faire marcher" une fonctionnalite.

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
