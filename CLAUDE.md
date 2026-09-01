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
| **docs/content-model.md** | Reference du modele editorial | Types de contenu, champs, allowlists de paragraphes, conventions de formulaire (acte par ADR-002) |
| **docs/active/`<feature>`/** | Chantier **en cours** | `progress.md` (point de reprise), `verification.md` (self-review) |
| **docs/archive/** | Traces d'audit de features **livrees** | Non maintenu : decrit l'etat au moment de la recette, jamais l'etat courant |
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
- ⚠️ **Un `t('Chaine courte generique')` peut recuperer une traduction fr deja presente ailleurs sur le site pour cette meme chaine**, sans rapport avec l'usage voulu (le stockage de traduction est global, indexe par chaine source + `#context`). Cas vecu : `t('E-mail')` affichait « Courriel » (traduction communautaire deja importee pour ce terme, sans contexte). Verifier via `\Drupal::service('locale.storage')->getTranslations(['language' => 'fr', 'source' => '...'])` avant d'utiliser une chaine courte/generique, et disambiguer avec `t('...', [], ['context' => '...'])` si une collision existe.
- ⚠️ **Les hooks de theme s'executent apres ceux des modules, pour un meme hook de preprocess.** Un module (ex. `metatag`) peut donc avoir deja **compose** une variable de gabarit (ex. `head_title['title']` = « Titre de la page | Nom du site ») avant que le theme n'intervienne : l'ecraser entierement y perd ce qu'il contenait deja (le nom du site, ici), plutot que de la completer. Remplacer une portion de la valeur existante (`str_replace` cible), jamais l'ecraser en bloc, quand on sait qu'un module a pu la construire avant. Cas vecu sur le titre d'onglet de `/user/login`.
- ⚠️ **Le bouton de soumission d'un `EntityForm` (ex. `ProfileForm`) porte son propre `#submit` (`$actions['submit']['#submit'] = ['::submitForm', '::save']`, pose par `EntityForm::actions()`), qui prime sur `$form['#submit']` pour l'element declencheur.** Ajouter un callback (ex. une redirection) via `$form['#submit'][] = '...'` dans un `hook_form_FORM_ID_alter()` est alors **silencieux et sans effet** — aucune erreur, le callback n'est simplement jamais appele. Cibler `$form['actions']['submit']['#submit'][]` a la place. Cas vecu sur `user_form` (`drivematic_partner_form_user_form_alter()`, redirection post-sauvegarde vers « Mes informations personnelles »).
- ⚠️ **Restreindre un champ sur UN formulaire ne le restreint pas ailleurs : verifier si l'entite a d'autres formulaires partageant le meme affichage de formulaire (`entity_form_display`).** Un champ expose sur le mode `default` d'une entite est visible sur **tous** les formulaires qui l'utilisent (ex. `user_form`, utilise a la fois pour l'auto-edition et pour l'edition admin d'un autre compte) — le rendre lecture-seule dans un `FormBase` custom ne l'empeche pas d'etre modifiable ailleurs via ce meme affichage partage. Verifier les autres routes utilisant la meme entite/le meme form display avant de considerer une restriction de champ comme complete. Cas vecu : `/user/{uid}/edit` restait editable pour l'e-mail et les champs de profil partenaire, malgre leur caractere lecture-seule sur `PersonalInformationForm`.

**Twig**

- Ne jamais desactiver l'auto-echappement (`|raw`, `verbatim`) sans justification de securite explicite : c'est le principal vecteur XSS cote theme.
- Logique metier dans le preprocess PHP, pas dans le template : le Twig reste presentationnel.
- **Tester un slot vide avec `|trim`** : ecrire `{% if block('x')|trim is not empty %}`, jamais `is not empty` seul. Un champ vide rend un slot fait d'**espaces**, que le test `empty` laisse passer — d'ou une enveloppe vide et sa marge. Le piege est intermittent : quand un champ n'a aucune valeur, le formatter ne produit pas de `content.field_*` et le slot est vraiment vide, donc certains composants « marchent » sans le `|trim`.

**JavaScript (vanilla)**

- Encapsuler le comportement dans `Drupal.behaviors.<name> = { attach(context, settings) { … } }`, le tout dans une IIFE (`(function (Drupal, once) { … })(Drupal, once);`).
- Utiliser `once('<id>', selector, context)` pour ne lier un handler qu'une seule fois (evite le double-binding lors des rechargements AJAX).
- Passer les donnees serveur -> client via `drupalSettings`, jamais en dur dans le JS ; ne jamais y exposer de donnee sensible.
- ⚠️ **Egaliser la hauteur d'elements empiles (pas cote a cote) est impossible en CSS declaratif pur.** `align-items: stretch` (Grid/Flex) n'egalise que des elements qui partagent la **meme ligne** — en dessous d'un breakpoint ou plusieurs cartes passent d'une grille a 3 colonnes a un empilement 1 colonne, chacune occupe sa propre ligne et plus rien ne les compare entre elles ; leur hauteur redevient dependante de leur seul contenu (texte 1 a 3 lignes). Seul un `min-height` mesure en JS (le plus haut du lot, applique a tous, recalcule au resize) resout ce cas — a scoper au breakpoint concerne (`matchMedia`) pour ne pas interferer avec le stretch natif au-dela. Sans JS : degradation gracieuse, chaque carte garde sa hauteur naturelle. Cas vecu sur les 3 cartes d'action de `login-panel` (mobile), `login-panel.js`.

**SCSS / SDC**

- Tout composant front est un **SDC** (Single Directory Component) : Twig + CSS (+ JS) co-localises et scopes dans le dossier du composant. Rien hors SDC, hormis les **fondations globales** (reset, tokens/variables, typographie) et le markup qu'on **ne choisit pas** — celui du coeur ou d'un module, qui n'appartient a aucun composant : `_local-tasks.scss`, `_forms.scss`, `_page-title.scss`, `_pager.scss`, `_views-faq.scss`, `_breadcrumb.scss`. Toute nouvelle derogation se commente dans `style.scss`, avec sa raison.
- **Les classes du coeur ne passent pas le motif kebab-case de Stylelint** (`pager__item`, `field--type-webform`) : poser un `stylelint-disable selector-class-pattern` **cible et commente**, jamais desactiver la regle globalement. Meme chose pour une classe de SDC citee depuis une fondation — a l'interieur d'un SDC, l'ecriture `&__element` passe sans exemption.
- ⚠️ **Restyler un `system_menu_block` en layout non-liste (colonnes flex, grille...) : neutraliser explicitement `.menu-item { padding-top: 0.2em }`**, pose par le CSS du coeur pour le rythme d'une liste verticale par defaut. Sans quoi le premier enfant de chaque item (titre, lien) se decale de 3,2px par rapport a tout element voisin qui n'a pas ce padding — ecart silencieux, invisible sauf comparaison directe. Rencontre sur le footer (colonne "Restons connectes", codee en dur, non decalee, face aux titres de colonnes issus du menu `footer-solutions`).
- Nommage **BEM** (`.block__element--modifier`) a l'interieur de chaque composant.
- Pas de couleurs/tailles en dur : utiliser les tokens (variables SCSS / custom properties).
- **Espacement — trois tokens, trois roles** ([ADR-013](.claude/decisions/013-espacement-et-unites.md)) : `--dm-space-element` (entre elements d'un bloc), `--dm-space-block` (`padding-block` du bloc), `--dm-gutter` (`padding-inline`). Ne jamais reintroduire de valeur d'espacement en dur. Quand une mesure de maquette n'entre dans aucun de ces roles, la **nommer** en custom property locale plutot que de la semer en dur. ⚠️ **`--dm-space-element` reste une valeur UNIQUE, y compris entre mobile et desktop** — contrairement a `--dm-gutter`/`--dm-space-page`/l'echelle h1-h3 (addendum ADR-013 du 24/08), qui portent chacun une valeur mobile de base **et** une surcharge `@media (width >= 992px)`. Si une maquette mesure deux ecarts differents pour deux paires d'elements qui partagent aujourd'hui `--dm-space-element`, **le token ne peut structurellement satisfaire que l'un des deux** : ne pas trancher seul (ni en introduisant une exception locale, ni en changeant sa valeur mobile, qui rejaillirait sur les ~20 composants qui le consomment) — mesurer le delta des deux cotes et presenter le compromis a l'utilisatrice.
- **Gabarit de page — un seul ecart, `--dm-space-page`** (regle posee par l'utilisatrice, valable pour **toutes** les pages). Il cadence la charpente verticale, quelles que soient les valeurs de la maquette : **au-dessus du titre de page**, **au-dessus et au-dessous d'un filtre expose**, **au-dessous de la liste de contenus**, et avant le footer (pagination). Les maquettes donnent des valeurs differentes d'une page a l'autre (113/103 sur les actualites, 63/84 sur la FAQ) : on retient le rythme unique. Consommateurs actuels : `page-intro`, `news-list`, `brands-grid`, `faq-list`, `.view-faq .view-filters`, `.pager`, `.field--type-webform` (contact, partenaire ; `padding-block-start` uniquement en `:first-child`, quand aucun bloc ne precede le formulaire), `news-article` (uniquement quand l'actualite n'a aucun bloc — sinon c'est le dernier bloc qui pose l'ecart au footer), `legal-text` (pages legales, titre -> body et body -> footer), `login-panel` (page `/user/login`, ADR-024 ; pas de bloc titre de page sur cette route, le composant porte l'ecart seul).
- ⚠️ **Deux paddings de gabarit ne s'additionnent pas.** Quand un bloc suit un element qui a deja pose l'ecart (une liste sous un filtre expose), il **ne repose pas** son `padding-block-start` — sans quoi l'ecart double. C'est pour cela que `faq-list` n'a qu'un `padding-block-end`. Meme logique sur `.block-page-title-block` : il ne pose plus son propre `padding-block-start` depuis que `.breadcrumb` (`_breadcrumb.scss`) porte cet ecart (`--dm-space-element`, pas `--dm-space-page` — voir plus bas), le fil d'Ariane precedant desormais toujours ce bloc quand il est affiche.
- **Une custom property declaree a l'interieur d'un composant (`.site-header { --site-header-gutter: … }`) n'est heritee que par ses descendants DOM, pas par ses freres.** Un autre composant place a cote (pas a l'interieur) ne peut pas la lire — reproduire les valeurs a la main si un alignement pixel-parfait est requis entre les deux (ex. `.breadcrumb` calant sa gouttiere sur celle, non partagee, de `.site-header__bar`), avec un commentaire renvoyant a la source pour eviter la divergence silencieuse.
- **Ordre d'agregation CSS de Drupal : categorie avant poids.** Une regle equivalente en specificite l'emporte selon la categorie de sa librairie (`component` < `theme`, entre autres), le `weight` ne departageant qu'a l'interieur d'une meme categorie. `css/style.css` (theme) l'emporte donc toujours sur un CSS de composant du coeur/starterkit (`component`, souvent `weight: -10`) sans qu'aucune specificite superieure soit necessaire — utile pour neutraliser un style brut herite sans `!important`.
  ⚠️ **« categorie avant poids » ne joue qu'a specificite EGALE — une regle brute du coeur avec 2-3 classes combinees reste plus specifique que notre selecteur a 1-2 classes, quelle que soit la categorie.** jQuery UI (`ui-dialog.css`, non tokenise, jamais restyle jusqu'ici) en est plein : `.ui-widget.ui-widget-content { border: 1px solid #c5c5c5 }` bat `.ui-dialog { border: 0 }` (2 classes > 1) ; `.ui-dialog .ui-dialog-buttonpane .ui-dialog-buttonset { float: right }` bat `.ui-dialog .form-actions { display: flex; justify-content: flex-start }` (3 classes > 2 — et `float` continue de deplacer visuellement l'element malgre `display: flex`, contrairement a ce que dit la spec). Symptome reconnaissable : une propriete qu'on a pourtant declaree (verifiee dans le CSS compile ET dans `getComputedStyle`) reste sans effet visible, alors qu'une AUTRE propriete de la meme regle s'applique correctement — signe que la regle gagne dans l'ensemble mais qu'une regle plus specifique gagne sur CETTE seule propriete, non redeclaree par nous ailleurs. Corrige en egalant la specificite du selecteur brut (jamais `!important`), jamais en se contentant d'ajouter la propriete manquante a un selecteur moins specifique. Rencontre sur les 3 modales du configurateur (`_dialog.scss`) : bordure fantome, largeur de titre figee a 90 %, bouton plaque a droite malgre `justify-content: flex-start`.
  ⚠️ **Un bouton `#type: actions` deplace par jQuery UI hors de `.ui-dialog-content`, vers `.ui-dialog-buttonpane`, n'appartient PLUS a la grille CSS du formulaire — un `grid-column`/`grid-row` pose sur `.form-actions` ne cible alors qu'un doublon masque (`display: none`), jamais l'element reellement affiche.** Pas seulement `ConfirmFormBase` (deja documente pour Supprimer/Annuler) : tout `FormBase` dont `$form['actions']` porte la classe `form-actions` subit le meme deplacement. Verifier via `document.querySelectorAll('.ma-classe-de-bouton')` (plus d'une occurrence = doublon) avant de chercher a repositionner un bouton de modale par la grille — l'ecart vertical/horizontal se regle sur `.ui-dialog-buttonpane` (padding), jamais sur le wrapper d'origine du formulaire.
- **Lien etire (« stretched link »)** : pour rendre toute une carte/ligne cliquable sans dupliquer ni imbriquer de lien, poser `position: relative` sur le conteneur et un `::before` (ou `::after` si le lien n'a pas deja son propre `::after` decoratif) en `position: absolute; inset: 0;` sur l'**unique** lien existant — sa zone cliquable et son `:hover` s'etendent alors a tout le conteneur, un seul element reste focusable. Premier usage : `news-teaser` (liste des actualites).
  ⚠️ **Le « conteneur » positionne doit correspondre au contenu VISIBLE, pas a la piste de grille qui le porte.** Sur `news-teaser`, le conteneur choisi etait la ligne entiere (`.news-teaser`, grille 2 colonnes) : sa colonne de texte (`minmax(0, 1fr)`) est bien plus large que son contenu reel (titre court), et le `::before` couvrait donc aussi ce vide jusqu'au bord de la colonne de contenu — un clic loin de tout texte visible declenchait quand meme la navigation (regression signalee le 27/08). Corrige en deplacant `position: relative` sur `.news-teaser__body` (avec `justify-self: start`, qui le retrecit a son propre contenu sans affecter le retour a la ligne du titre) : l'ancre positionnee redevient aussi etroite que le contenu qu'elle affiche. **Un visuel FRERE du lien (pas descendant) ne peut pas heriter ce `::before`** — s'il doit rester cliquable (ex. l'image d'un `news-teaser`), completer par un clic JS delegue (`<component>.js`, amelioration progressive, `.is-ready` avant de promettre un curseur pointeur), jamais en elargissant le `::before` a un ancetre commun plus large que le contenu.
- **Gouttiere = `padding-inline`, jamais `margin-inline: auto`** : celui-ci ne fait que **centrer** une largeur plafonnee et ne garantit aucun ecart au bord sous ce plafond. Le plafond vaut « contenu + 2 gouttieres ». Exceptions : gouttiere d'un seul cote quand la piste deborde volontairement, et bloc full-bleed quand le fond court d'un bord a l'autre.
- ⚠️ **Piste qui deborde volontairement : poser `calc(50% - 50vw)` sur l'element qui centre lui-meme (`margin-inline: auto`), pas sur un de ses enfants.** Un enfant **en flux normal** (`position: static`/`relative`) resout son `%` contre le bloc de **contenu** du parent — plus etroit de `gutter` quand celui-ci n'a qu'un `padding-left` (le cote droit reste libre pour le debordement) — et non contre la largeur totale que le parent utilise pour son propre centrage. L'ecart qui en resulte est une constante (`gutter / 2`), independante de la largeur de fenetre, invisible a l'oeil sous macOS (scrollbars en surimpression) mais mesurable (`document.documentElement.scrollWidth`). Si le calc doit rester sur l'enfant, compenser par `+ (var(--dm-gutter) / 2)`. Rencontre sur `history`/`jumbo_home`/`news_home`/`product_features` ([ADR-008](.claude/decisions/008-slideshow-swiper.md)). ⚠️ **Exception `position: absolute`** : son `%` resout contre la **padding box** de l'ancetre positionne (gutter inclus, pas retire), donc le calc peut rester sur l'enfant absolument positionne sans l'erreur ci-dessus, meme avec un gutter symetrique sur l'ancetre. Cas d'usage : un panneau qui doit deborder de son conteneur capsule/centre sans toucher au conteneur lui-meme (`site-header` : le flyout mega-menu deborde `.site-header__bar`, capsule a 1440px, sans l'agrandir).
- ⚠️ **Le pattern de compensation `margin-right: calc(50% - 50vw + gutter/2)` + `padding-right: calc(50vw - 50% - gutter/2)`** (utilise sur `.history__header` pour ramener un enfant au gutter symetrique malgre un parent qui n'a qu'un `padding-left`) **s'annule silencieusement a zero des que le parent est cale a son `max-width`** (plus de marge automatique a jouer : `50%` s'y resout alors exactement contre `50vw`, quelle que soit la largeur d'ecran au-dela de ce plafond). Piege d'autant plus sournois qu'il **disparait exactement** au palier de test le plus courant (1440px, quand le `max-width` du composant vaut aussi 1440px) — le calc semble marcher tant qu'on ne teste pas un ecran plus large. Pour un enfant qui doit simplement rester au gutter (pas deborder), poser un `padding-right: var(--dm-gutter)` fixe a la place : independant de la largeur, marche a tout viewport. Rencontre sur `news-home` (titre, points de pagination et bouton « Voir toutes les actualites » decales de gutter/2 a droite du vrai centre de page, cf. `_breadcrumb.scss`/ADR-023 pour le meme motif applique correctement — le parent y est **plus etroit** que tout viewport realiste, donc la marge automatique existe toujours et le calc y fonctionne).
- ⚠️ **Sur un conteneur `justify-content: space-between` (+ `gap`), un changement de taille d'UN SEUL enfant (padding, largeur conditionnelle a un etat...) redistribue l'espace entre TOUS les enfants**, pas seulement autour de celui qui change — mesure sur `site-header` : le dernier item du nav gagnait 20px de padding a l'ouverture de son dropdown, et les 4 boutons du nav se decalaient de 10px, pas seulement lui. Pour un etat visuel (survol, `aria-expanded`...) qui ne doit pas faire bouger les voisins, ne jamais toucher `padding`/`width`/`margin` : utiliser un `::before`/`::after` en `position: absolute` (qui ne participe pas au flux) pour l'habillage visuel (fond, contour).
- **Pousser UN SEUL item flex au bord oppose sans deplacer ses freres : `margin-inline-start: auto` sur cet item, jamais `justify-content: flex-end`/`space-between` sur le conteneur.** Le `justify-content` du conteneur repositionne TOUS les enfants comme un groupe (deplace aussi ceux qui doivent rester alignes au bord de depart) ; `margin-inline-start: auto` sur un seul item consomme l'espace restant devant lui uniquement, sans toucher aux autres. ⚠️ Sans espace disponible dans le conteneur (ex. un conteneur flex shrink-to-fit, largeur = somme exacte de ses enfants), cette marge n'a rien a consommer et n'a aucun effet — poser `flex: 1` (ou `width: 100%`) sur le conteneur pour lui donner cette marge de manoeuvre. Cas vecu : bandeaux de totaux du configurateur de devis (`quote-form__metric--ttc`, seule metrique alignee a droite parmi 5).
- **Colonne de contenu — `--dm-content-column`** ([ADR-016](.claude/decisions/016-colonne-de-contenu.md)) : le « contenu » du plafond ci-dessus. 900px par defaut, **retunable par gabarit** sur `body.page-node-type-<bundle>` — jamais sur le SDC, parce que le bloc titre de page est un **frere** du contenu du node et ne serait pas atteint. Consommateurs : `.block-page-title-block`, `news-article`, `video-centered`, `image-centered`. ⚠️ **Le token n'est pas universel** : les composants qui gardent une largeur litterale ne suivront pas le gabarit, **sans erreur ni signe visible**. Avant de retuner un gabarit, lister les composants qui y figurent et les convertir si besoin.
  ⚠️ **Retune non consomme (`legals`)** : `body.page-node-type-legals { --dm-content-column: 960px; }` existe depuis l'ADR-019, mais le SDC `legal-text` (maquette 469-11689, 2026-08-20) mesure son body a ~1130px — il garde ce plafond en litteral (meme formule que `news-list`/`brands-grid`/la carte des formulaires), sans consommer le token. Le retune reste donc pose sans effet ; trancher avec une mise a jour de l'ADR-019 si l'ecart doit etre resorbe.
- ⚠️ **`figure` n'est pas dans le reset global** : le navigateur y pose `margin: 1em 40px`. Tout SDC dont la racine est un `<figure>` doit poser `margin-block: 0`. Les 40px lateraux sont masques par le `margin-inline: auto` de la colonne — seul l'ecart vertical se voit, et il faut le mesurer pour le voir.
- ⚠️ **Une enveloppe de node ne porte pas la colonne sur sa racine** : les paragraphes portent deja la leur et subiraient une **seconde** gouttiere. Grouper les champs du node dans un sous-element (`news-article__lede`) et laisser les blocs en dehors.
- **Unites : espacement en `px`, typographie en `rem`.** Une gouttiere ne grossit pas parce que le texte grossit ; les tailles de police, elles, doivent suivre la preference du navigateur (WCAG 1.4.4). Reserver `em` a ce qui est lie au texte lui-meme.
- **Un ecart appartient au bloc, pas au contenu saisi** : les marges du premier et du dernier enfant d'un champ texte riche sont neutralisees dans les fondations (`.text-formatted`). Et une marge ne se pose que s'il y a un element en dessous (`:not(:last-child)`) — les champs etant optionnels, un titre peut etre le dernier element affiche.
- **Icones** : quand la maquette fournit un glyphe, utiliser l'**asset SVG exporte** auto-heberge dans `images/icons/`, applique en `mask` CSS avec `background-color` sur un token (permet d'heriter de `currentcolor`). Ne pas redessiner un glyphe en bordures/rotations : c'est moins fidele et ca diverge du reste du thème. Reutiliser un asset existant avant d'en ajouter un. **Exception `mask` impossible** (ex. icone d'un `<input type="submit">`, qui ne peut pas porter de `::before`/`::after` pour heberger le mask) : l'icone passe alors en `background-image`, exportee a une couleur FIXE, incapable d'heriter de `currentColor` — un changement de couleur au survol necessite un SECOND asset (variante de couleur, ex. `plus-circle-white.svg`) swape sur `:hover`, pas une astuce CSS. Cas vecu sur `configurator-form__add`.
- ⚠️ **`vertical-align` (dont la valeur `super`, utilisee par l'asterisque `::after` des champs obligatoires du coeur) n'a aucun effet sur un item flex.** Passer un `<label>` en `flex` pour aligner un element voisin (ex. un lien) casse donc silencieusement le positionnement en exposant de tout `::after`/`::before` qui en dependait — il retombe sur la ligne de base. Si le flux inline existant (texte + `::after`) doit rester intact, positionner l'element ajoute en `position: absolute` (avec `position: relative` sur l'ancetre) plutot qu'en flex + `order`. Cas vecu sur le libelle « Mot de passe » de `/user/login` (lien « Mot de passe oublié » ajoute a cote).
- ⚠️ **Un `<input type="submit">` (ou `button`/`reset`) non stylise conserve un rendu natif qui reserve sa propre marge horizontale** (~15px de chaque cote sur ce socle), **invisible en isolation** mais qui empeche un `width: 100%` d'atteindre reellement les bords de son conteneur, et desynchronise sa hauteur d'un element voisin (ex. un `<a>` de meme padding). `document.styleSheets` ne montre alors AUCUNE regle d'auteur en cause : le comportement vient du rendu natif du controle, pas d'une specificite CSS a debusquer. Corrige par `appearance: none;` + `margin: 0;` explicites sur le bouton — poser les deux ensemble, `appearance: none` seul ne suffit pas. Generalise a `_reset.scss` (`button, input, select, textarea { margin: 0 }`) et aux boutons d'envoi de `_forms.scss`/`_personal-information-form.scss`. Rencontre sur les boutons de `/mes-informations-personnelles` (largeur/alignement en mobile et desktop).
- ⚠️ **La `<legend>` d'un `<fieldset>` s'ancre nativement a son bord SUPERIEUR en ignorant `padding-top`, et le rendu natif de bordure du fieldset (encoche pour loger la legende) peut empecher un `border-top` d'auteur de se peindre meme quand `getComputedStyle` le confirme correctement calcule.** Ne jamais poser une ligne de separation ou un ecart mesure sur le bord SUPERIEUR d'un fieldset qui porte une legende — ancrer sur le bord INFERIEUR du fieldset PRECEDENT a la place (`::after`, jamais concerne par ce comportement, une legende n'existant qu'en haut). Cas vecu sur le configurateur de devis (separateur vehicule → equipements, `_configurator-form.scss`).
- ⚠️ **Surcharger un controle stylise par la fondation `forms` (`.webform-submission-form input[type='...']`, `border`/`padding`/`border-radius`/etc. poses ensemble sur un meme selecteur) exige de reinitialiser TOUTES ses proprietes de boite, pas seulement celles visiblement differentes de la maquette.** Une regle plus specifique qui ne redeclare que 2 des 4 proprietes de la fondation laisse les 2 autres passer telles quelles (le CSS s'applique propriete par propriete, pas regle par regle) — sans erreur ni signe visible tant qu'on ne compare pas au `getComputedStyle` reel. Cas vecu : le champ central du pilulier de quantite (`configurator-form__quantity-input`) redeclarait `padding`/`border-block`/`border-inline` mais pas `border-radius`, laissant fuiter les 8px de la fondation (`input[type='number']`, `_forms.scss:203`) sur un champ que la maquette (508:12884) veut a angles droits.
- ⚠️ **Sur une grille CSS, `align-items: stretch` calcule la hauteur de ligne au MAXIMUM de chaque item (boite de contenu + marge propre) — une marge posee sur certains items d'une ligne seulement (ex. « tous sauf le dernier ») deforme les items SANS marge : ils sont etires a cette hauteur par leur propre CONTENU, faute de marge pour absorber la difference, decalant leur contenu interne (ex. une case a cocher) par rapport aux autres.** Poser la marge UNIFORMEMENT sur tous les items d'une meme ligne stretched, jamais en excepter le dernier meme quand son ecart suivant semble deja couvert autrement. Cas vecu sur la grille d'equipements du configurateur (checkbox « double pedalier » decalee de 15px apres avoir ete exclue de la marge).
- ⚠️ **Un item de grille CSS sans largeur explicite est `justify-self: stretch` par defaut : place sur une zone nommee qui couvre plusieurs colonnes, il s'etire a la largeur totale de cette zone au lieu de garder sa largeur intrinseque.** Poser `justify-self: start` (ou une largeur explicite) des qu'un item doit rester compact dans une zone plus large que son propre contenu. Bug silencieux : invisible dans toute verification qui ne mesure que hauteur/position, jamais la largeur de l'item lui-meme. Cas vecu sur le pilulier de quantite du configurateur (etire a la largeur du fieldset au lieu de rester a 127px).
- ⚠️ **La fondation `forms` applique `display: contents` a tout wrapper technique `#states` (`div.js-form-wrapper` direct enfant de `.fieldset-wrapper`) des qu'il devient visible, pour eviter qu'il cree sa propre cellule de grille.** Tout ce qui est pose sur CE wrapper (`grid-area`, marge, largeur) devient alors sans effet des que la condition `#states` s'active — cibler l'enfant REEL (le champ lui-meme), jamais le wrapper `#states` qui l'englobe. Symptome reconnaissable : `getBoundingClientRect()` renvoie un rectangle degenere (`top: 0`) une fois la condition active. Cas vecu sur le configurateur de devis (quantite de retrovision exterieure, revelee par une case a cocher).
- **Boutons — mixin partage** (`src/scss/_button-mixins.scss`, [ADR-029](.claude/decisions/029-mixin-boutons-partage.md)) : les 3 familles de boutons (plein gris, plein rouge, contour) sont des mixins Sass (`dm-btn-red`, `dm-btn-grey`, `dm-btn-outline`, `dm-btn-height`), pas des valeurs a redupliquer par composant. Consommer via `@include` sur le SELECTEUR PROPRE du composant (le CSS reste co-localise dans le SDC) — jamais une classe utilitaire posee dans le Twig. Necessite `@use 'button-mixins' as btn;` en tete de fichier ; les SDC en beneficient via un second `--load-path=src/scss` ajoute a `css:components`/`css:watch`.
- ⚠️ **Deux pieges cumulables, silencieusement responsables d'une hauteur de bouton fausse** (rencontres lors de l'harmonisation ADR-029, invisibles a la lecture du SCSS — seul `getBoundingClientRect()` les revele) : (1) une **bordure** (`border`) ajoute TOUJOURS a la hauteur d'une boite en hauteur automatique, meme sous `box-sizing: border-box` — celui-ci ne change l'effet de la bordure QUE si une `height` explicite est posee, jamais quand la hauteur vient du padding + du contenu ; retirer 1px de `padding-block` de chaque cote pour qu'un bouton borde egale la hauteur d'un bouton sans bordure a padding sinon identique. (2) Un bouton sans `line-height` explicite herite du `line-height` ambiant du corps de texte (`--dm-body-line`, 28px) au lieu d'une valeur resserree — grimpe alors a ~58px au lieu de ~46px : toujours poser `line-height: normal` (ou une valeur explicite) sur un bouton/lien-bouton.
- ⚠️ **`table-layout: auto` ne redistribue jamais une colonne EN DESSOUS du mot le plus long qu'elle contient sans espace/trait d'union** (son minimum incompressible), meme avec `width: 100%` pose sur la table — si la somme de ces minimums depasse le conteneur, la table DEBORDE malgre le `100%` (le layout `auto` grandit au-dela d'une largeur specifiee des que le contenu l'exige). Symptome trompeur si un ancetre proche a `overflow: hidden` (frequent pour des coins arrondis) : aucune barre de defilement, aucun scroll de page — juste du contenu rogne en silence, invisible sauf en comparant `scrollWidth`/`clientWidth` au navigateur. Mesurer le minimum reel via `ctx.measureText()` (police du calque) sur le mot le plus long de chaque colonne (entete ET donnees) avant d'ajuster ; seul le `padding` des cellules est habituellement compressible sans couper un mot ou changer le contenu. Cas vecu sur le tableau d'equipements du devis, mobile, a 320px.
- ⚠️ **Un `justify-content: center` sur un conteneur qui devient plus large que son contenu (`overflow-x: auto` ajoute pour eviter un debordement de page) clippe les DEUX bouts a parts egales au repos**, pas seulement le dernier — le centrage continue de s'appliquer a du contenu qui deborde, la moitie de l'exces partant hors champ de chaque cote. Un premier ET un dernier element partiellement tronques (bordure coupee) sans barre de defilement visible se lit comme un debordement casse, au meme titre qu'un vrai scroll de page. Remplacer par `justify-content: flex-start` + `width: fit-content` + `max-width: 100%` + `margin-inline: auto` : identique visuellement tant que le contenu tient (le `margin-inline: auto` centre une boite retrecie a son contenu), et si le filet `overflow-x: auto` doit un jour se declencher, seul le DERNIER element est concerne — jamais les deux. Cas vecu sur le fil d'etapes du configurateur (`configurator-form__stepper`), mobile.

**Media / images**

- Toute image passe par un **image style defini** (responsive, aligne sur les breakpoints du site), sortie **WebP**. Jamais de dimension/crop en dur ni de `<img>` hors image styles. Reference : etude images (docs/PRD.md §7).
- ⚠️ **Deux circuits, pas un seul, depuis [ADR-018](.claude/decisions/018-images-locales-par-paragraphe.md)** — le recadrage Drupal (Crop API) est rattache au **fichier**, pas au champ qui le referme (`Crop::findCrop($uri, $type)`) : reutiliser une meme image en mediatheque a deux endroits imposant chacun un ratio different force le meme cadrage aux deux. D'ou, pour les champs a ratio impose uniquement, l'abandon de la mediatheque au profit d'un champ image local :
  - **Champs a ratio impose** (9 paragraphes — `image_text_50`, `image_full`, `history_element`, `grid_element`, `jumbo_home_element`, `product_cross_element`, `product_image_element`, `product_video_element`, `video_centered` — + `news.field_photo`, + `contact.field_photo` depuis l'addendum du 2026-08-20) : **champ image local, sans mediatheque**, un fichier par usage, widget `image_widget_crop` scope au **seul** ratio du bundle (`crop_types_required` = ce ratio, un seul element).
  - **Champs sans ratio impose** (`image_centered`, `image_text_100`, `product_characteristics`, `brand`) : restent en **media-library** reutilisable, widget `media_library_widget`, ratio choisi au rendu via le mode d'affichage media (`free`/`ratio_*`). `crop_types_required` y est **vide** — rien dans ce perimetre n'impose plus de ratio a l'import.
  - Un **nouveau** paragraphe/champ portant une image : s'il impose un ratio, l'ajouter a la premiere liste (champ image local) ; sinon il releve normalement de la mediatheque. Ne pas presumer — verifier `field.storage.<entity_type>.<field_name>` : une storage est **partagee par tous les bundles qui la portent**, la convertir affecte donc tous ses bundles a la fois (piege deja rencontre : convertir `field_image` pour 9 bundles avait aussi converti, sans le vouloir, les 3 restants — corrige en renommant le champ pour le sous-ensemble concerne, meme pattern que `node.news` -> `field_photo`).
- **Le recadrage a ratio precis est OBLIGATOIRE et MANUEL** (regle posee par l'utilisatrice), quel que soit le circuit : il est effectue **par l'editeur, a l'import**, avant d'enregistrer son contenu. **Pas de recadrage automatique** (focal point ou autre) : le cadrage est une decision editoriale ([ADR-004](.claude/decisions/004-pipeline-images.md)).
- ⚠️ **Sur les champs restes en mediatheque, le recadrage impose a l'import ne prejuge pas du rendu.** Le ratio se choisit **au champ referant**, via le mode d'affichage media : un meme media peut sortir recadre a un ratio dans un emplacement et **non recadre** (`free`) dans un autre. **Deux ratios pour une meme image n'est donc pas un bug** — ne pas « corriger » un `free` en `ratio_*` sans verifier la maquette de l'emplacement. La meme dualite existe sur un champ local a ratio impose : `news.field_photo` sort cadre 16:9 dans les vignettes de liste et **non cadre** (`free`) sur la page de detail — meme fichier, deux formatters differents sur le meme champ.
- **Ne pas fabriquer de recadrage par script.** L'API entite ne valide pas les formulaires : un media/fichier cree programmatiquement echappe a l'obligation, et un crop centre pose au passage est une valeur machine qui usurpe la decision de l'editeur. Si un import par script doit etre rendu a un ratio, **le signaler pour un passage en back-office** plutot que de cadrer a sa place. Le recadrage etant porte par le couple **(fichier, type de crop)**, un fichier recadre pour un ratio et reutilise a un autre ressort **non recadre, sans erreur ni log** — le controle ne peut donc pas etre visuel.

**E-mails webform (HTML inline)**

- Le corps d'un handler d'email Webform (`config/sync/webform.webform.*.yml`, `handlers.*.settings.body`) est du **HTML autonome, CSS inline** (`html: true`, `twig: false`) : les clients mail n'executent ni CSS externe/lie ni les custom properties CSS — toute couleur se pose en `style="..."` avec la valeur hex du token (ex. `--dm-color-steel` -> `#2f3a45`), jamais la variable.
- Logo en **PNG** (pas SVG, souvent bloque par les clients mail type Outlook desktop), heberge dans `web/themes/custom/drive_matic/images/` et reference par **URL absolue** (`https://www.drivematiclegrand.com/...`) : un `<img>` d'email n'a pas de contexte Twig pour resoudre `active_theme_path()`.
- Gabarit commun a tous les emails transactionnels (F10, F11, [ADR-022](.claude/decisions/022-gabarit-email-webform.md)) : sans encadre ni centrage (texte aligne a gauche), `<h3>` de section en capitales (`text-transform: uppercase`), pied de page identique (« A bientot » + lien rouge + mention automatique). Ordre commun du bloc identite : Statut (si le formulaire a un champ « Vous etes ») - Entreprise - Nom (civilite+prenom+nom concatenes sur une ligne, sans label) - Adresse (adresse+complement+CP+ville concatenes, separateur `-`) - E-mail - Tel.
- **La maquette Figma d'un email ne montre pas toujours tous les champs reellement collectes** (deja rencontre : ligne Adresse absente de la maquette « devenir partenaire » alors que les 4 champs sont obligatoires au formulaire ; ligne « Piece jointe » absente des emails devis/question alors que la maquette SAV la montre). Regle retenue : une info metier reellement collectee par le formulaire **reste visible** dans l'email, meme absente de la maquette.
- ⚠️ **Une maquette dupliquee pour un nouveau cas peut garder un texte copie-colle de l'original** (le titre de section « Votre demande de devis » se retrouve identique sur les maquettes SAV, alors que le frame s'appelle « Modele Email SAV ») : ne pas le reproduire, garder le libelle deja etabli pour la famille d'emails.
- Pour qu'un fichier uploade (`managed_file`) soit **reellement joint** (pas seulement son nom mentionne en texte) : poser `attachments: true` sur le handler — independant de `#uri_scheme: private` du champ, qui n'empeche pas l'attachement sortant.
- ⚠️ **Un remplacement de texte partage entre plusieurs handlers d'un meme fichier YAML** (ex. renommer un libelle present a l'identique dans `devis_*`, `sav_*` et `question_*`) **fuit silencieusement vers les handlers non concernes si l'ancre n'est pas unique** au bloc vise — verifier le nombre d'occurrences avant ET apres toute edition sur ce type de fichier.

### Convention de formulaire back-office (TOUS les types de contenu)

Regle posee par l'utilisatrice, a appliquer **a chaque nouveau type de contenu** (et a verifier apres tout ajout de champ) :

- **Deux onglets horizontaux** (`field_group`, format `tabs` / `direction: horizontal`, groupes `group_tabs` > `group_general` + `group_content`) :
  - **« Informations generales »** : `title`, `path`, `field_meta_tags`, `status` — dans cet ordre.
  - **« Contenu »** : tous les autres champs, **les paragraphes en dernier**.
- **Champs jamais proposes a la saisie** : `uid`, `created`, `simple_sitemap`, `url_redirects`. Les deux premiers se desactivent dans le form display ; les deux autres sont ajoutes par leurs modules **sans consulter le form display** et sont retires en `#after_build` (`drivematic_forms_form_node_form_alter()`) — pas dans un `hook_form_alter`, qui passe avant eux.
- **Un seul titre : le `title`** ([ADR-014](.claude/decisions/014-titre-unique-porte-par-le-title.md), qui **remplace l'ADR-011**). Le `title` du node alimente l'affichage, le fil d'Ariane, le motif d'alias et la balise `title`. Il n'y a **plus de `field_title` sur les types de contenu** : ne pas le recreer. ⚠️ `field.storage.paragraph.field_title` est un champ **homonyme et distinct** (titre des blocs, 21 bundles, 21 templates SDC) — ne jamais le confondre avec l'ancien champ des nodes. Tout **nouveau type public** recoit : son `base_field_override` de `title`, un defaut `metatag.metatag_defaults.node__<bundle>` en `[node:title] | [site:name]`, et son motif Pathauto. Tout rendu hors page canonique (carte, teaser, ligne de vue) lit le `title`.
- **`title` + `body` sur tout type indexable, pour le SEO** : `title` alimente la balise `title`, `body` la meta description. Mais **ce qu'un type en affiche lui est propre et ne se deduit pas** — verifier son view display avant de conclure : `transform` et `product` **n'affichent ni l'un ni l'autre** (toute la page vient des paragraphes, dont le contenu est **libre** : aucun champ du node n'y est recopie ni force) ; les autres types affichent **`title` seul ou `title` + `body`**, au-dessus de leurs paragraphes / Vues / webforms. Sur un type qui affiche deja son `title`, ne pas creer de paragraphe pour reporter le titre ou le chapo.
- **Ou s'affiche le `<h1>`** : sur les types dont la maquette place le titre dans un bloc (`homepage`, `transform`, `product`), le bloc titre de page est **masque** et c'est le premier paragraphe **portant un titre** qui rend le `<h1>`, via la prop `heading_level` des SDC `image-full` / `text-centered`. Partout ailleurs, le bloc titre rend le `title` en `<h1>` au-dessus du contenu. Deux listes doivent rester alignees : `_drive_matic_hero_title_bundles()` et la condition `entity_bundle:node` (negee) de `block.block.drive_matic_page_title`. Un ecart donne une page a deux `<h1>` ou a aucun.
- **Alias d'URL** : motif Pathauto `/[node:title]` pour tout node public ; `news` fait exception avec `/actualites/[node:title]`. Un nouveau type public a besoin de son propre `pathauto.pattern.node_<bundle>` — **sauf s'il est a exemplaire unique** : dans ce cas **pas de motif du tout** et un alias **en dur** sur le node (`path` avec `pathauto: 0`), pour garder une URL courte quand le titre de la maquette est une accroche redigee. Cas actuels : `configurator` -> `/configurer`, `faq` -> `/faq`, `brands` -> `/marques-partenaires`. Types a exemplaire unique restant a traiter : `documents`, `all_news`, `contact`. ⚠️ `simple_form` (ex-`partner`) sort de cette liste depuis le 2026-08-25 : mutualise et devenu multi-instance ([ADR-024](.claude/decisions/024-mutualisation-formulaire-simple.md)), son motif Pathauto doit au contraire **rester actif**. Corollaire general : **renommer un contenu publie change son alias**.
- ⚠️ **Changer un alias a la main ne supprime pas l'ancien** : les deux repondent alors en **200** et le node existe a deux URL (contenu duplique). Pathauto ne cree une redirection que lorsque **lui** gere le changement. Apres un alias pose en dur, verifier `path_alias` pour le node (`loadByProperties(["path" => "/node/<nid>"])`), **supprimer l'entree perimee** et creer le 301 explicitement.
- **Sitemap** : l'indexation est **opt-in**. Sans fichier `simple_sitemap.bundle_settings.default.node.<bundle>`, le type n'est **pas** indexe — silencieusement. Tout nouveau type public en a donc besoin ; les fragments s'appuient au contraire sur cette absence.
- **Un fragment rendu ailleurs a besoin de son template** : `node.html.twig` rend `{{ label }}` des que `view_mode != "full"`, **et en lien vers la page canonique** — laquelle repond 403 sur un fragment. Ajouter un `node--<bundle>.html.twig` qui ne rend que `{{ content }}`. Controle : `href="/node/<id>"` dans `<main>` doit renvoyer zero resultat.
- **Nouveau type « fragment »** (contenu reutilisable sans page publique) : pas de `field_meta_tags`, pas de motif Pathauto, **pas** de reglage sitemap, et un `rabbit_hole.behavior_settings.node_type_<bundle>` en `access_denied`. Verifier le 403 en anonyme sur `/node/<id>`, jamais seulement l'absence de lien.
- Un champ ajoute a un type de contenu **doit etre range dans un des deux onglets** : sans groupe, il apparait hors onglets, en haut du formulaire.

Detail et raisons dans `docs/content-model.md`.

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
- **Contourner le pipeline images** : crops/dimensions en dur, `<img>` hors champ image (local ou media-library selon le circuit) / image styles, ou sortie non-WebP.
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

#### Integration d'une maquette : « conforme » n'est pas « integree »

Regle nee d'une erreur reelle (PRD, ecart #4) : plusieurs pages avaient ete notees
« conformes » alors que **seul leur contenu** avait ete verifie — bons blocs, bons textes,
un seul `<h1>`, bons alias — sans qu'aucune **mesure de mise en page** ne soit relevee.

Une page n'est integree que si :

1. les mesures ont ete **relevees sur la maquette** (`get_metadata` pour la geometrie,
   `get_design_context` pour couleurs/typo/rayons) ;
2. elles ont ete **comparees au rendu**, au navigateur (`getBoundingClientRect`,
   `getComputedStyle`), et non a l'oeil sur une capture ;
3. les ecarts restants sont **ecrits**, pas tus.

⚠️ Un commentaire de code qui dit « integration fine a faire » vaut plus que la ligne de
suivi qui dit « conforme » : verifier le code avant de conclure.

⚠️ **Un contenu migre ou saisi par script peut manquer sa structure HTML** sans que le
rendu visuel ne le crie : du texte sans balise `<p>` reste lisible, juste fusionne en un
seul bloc au lieu de plusieurs paragraphes distincts — un ecart qui se voit a la mesure
(`innerHTML`, pas juste une capture) ou en comparant au decoupage de
`get_design_context`, jamais a l'oeil sur un rendu qui « a l'air » correct. Reflexe a
chaque page dont le contenu vient d'une migration : lire l'`innerHTML` du champ rendu
avant de juger sa mise en page conforme.

⚠️ **Verifier l'alignement d'un TEXTE visible : mesurer le TEXTE, jamais le conteneur qui
l'englobe.** `getBoundingClientRect()` sur un conteneur flex/grid peut renvoyer une boite
parfaitement positionnee alors que son contenu flotte ailleurs a l'interieur (son propre
`justify-content`/padding/alignement interne) — la mesure « prouve » alors un alignement
que l'oeil dement. Cible la mesure sur l'element qui porte le texte lui-meme (le span du
libelle, ou `Range.getBoundingClientRect()` sur le noeud texte), jamais son wrapper.
Symptome vecu : une utilisatrice a fourni deux captures d'ecran avec une regle verticale
superposee montrant un ecart net, prises a tort pour un « effet d'optique » parce qu'une
mesure de boite (identique aux deux positions) semblait la contredire.

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
