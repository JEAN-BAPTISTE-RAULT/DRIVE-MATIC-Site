---
name: plan
description: Planifier une feature ou un changement avant de coder. Ce skill doit etre utilise quand l'utilisateur demande d'implementer une feature, de modifier du code existant, ou de resoudre un bug non trivial. Il ne doit pas etre utilise pour des corrections triviales (typo, ajout d'un commentaire) ni pour de la recherche exploratoire.
---

# Planification avant implementation

Produire un plan structure avant toute implementation. Ne pas commencer a coder avant validation du plan par l'utilisateur.

Pour les changements complexes (multi-session, choix architectural), utiliser `think hard` pour activer la reflexion etendue et sauvegarder le plan approuve dans `docs/plans/<feature>.md`.

## Processus de planification

Pour "$ARGUMENTS", repondre a chaque point :

### 1. Intention

Quel probleme resout-on ? Pour qui ? (une phrase)

### 2. Fichiers impactes

Lister les fichiers qui seront crees ou modifies.

### 3. Interfaces publiques

Identifier les fonctions, endpoints ou exports ajoutes ou modifies. Verifier si la configuration du linter doit etre mise a jour (globals, exports, etc.).

### 4. Securite

- Ce changement touche-t-il l'authentification ou l'autorisation ?
- La verification cote serveur est-elle assuree avant toute action protegee ?
- Passer en revue la checklist securite du CLAUDE.md (injection, XSS, IDOR, donnees sensibles, moindre privilege, validation des entrees).

### 5. Risques et contraintes techniques

Passer en revue les contraintes de la stack Drupal / Twig / Vanilla JS / SCSS :

- **Cache Drupal** : le rendu est mis en cache (render cache, dynamic page cache, page cache anonyme). Tout nouveau contenu variable doit declarer ses `cache tags` / `cache contexts` corrects. Piege : un contexte manquant sert du contenu partenaire a un anonyme.
- **Securite / acces partenaire** : ~100 partenaires authentifies + grand public anonyme. Toute ressource reservee re-verifie l'acces cote serveur (voir garde-fous CLAUDE.md).
- **Accessibilite** : viser WCAG 2.1 AA (site grand public).
- **Performance front** : JS/CSS agreges par Drupal ; eviter les dependances lourdes (stack "vanilla JS", pas de gros framework). Attacher les assets via `libraries.yml`, pas d'inline.
- **Compatibilite** : navigateurs modernes (2 dernieres versions majeures) ; pas de build transpile => rester dans un JS supporte nativement.
- **Mises a jour destructives** : les `hook_update_N` et changements de schema/config sont difficilement reversibles en prod — les concevoir idempotents et testables sur une copie.
- **i18n** : toute chaine visible doit etre traduisible (`t()` / `|t`).

### 6. Coherence avec les specifications

- Le changement est-il aligne avec les features documentees dans `docs/PRD.md` ?
- Contredit-il une decision verrouillée (PRD §3) ?
- Introduit-il un nouveau parcours utilisateur necessitant un scenario E2E ?

### 7. Plan d'implementation

Lister les etapes en chunks verifiables. Chaque etape doit laisser le code fonctionnel (pas de big bang).

### 8. Strategie de test et boucle de feedback

Avant de coder, repondre a ces questions :

<!-- Outils de feedback disponibles dans la stack Drupal :
     - Verification statique : npm run lint (ESLint + Stylelint + PHPCS), npm run format:check
     - Rendu : recharger la page ; drush cr pour vider le cache apres un changement de template/hook/libraries
     - Debug : dpm()/kint (module devel), watchdog / drush watchdog:show, DevTools navigateur
     - Etat config : drush cst / drush cim (verifier l'import de config)
     - Tests Drupal (a mettre en place si besoin) : PHPUnit (Unit/Kernel/Functional), Nightwatch pour le JS
-->


- **Comment verifier chaque etape du plan ?** (tests unitaires, tests d'integration, verification manuelle, E2E ?)
- **Quelle boucle de feedback est la plus rapide ?** (hot reload, test runner en watch, REPL, curl, navigateur ?)
- **Si aucun test automatise n'est possible** : quelles etapes de verification manuelle ? Decrire precisement quoi verifier et comment.
- **Cas d'erreur** : quels scenarios d'echec tester en plus du happy path ?
