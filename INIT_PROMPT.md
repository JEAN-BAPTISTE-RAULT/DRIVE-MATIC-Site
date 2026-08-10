# Prompt d'initialisation de projet

> Copier le prompt ci-dessous et le fournir a Claude Code dans la premiere session d'un nouveau projet.
> Adapter les sections entre `[CROCHETS]` avant de le coller.
> Ce fichier ne fait PAS partie du projet final — le supprimer apres initialisation.

---

## Le prompt a copier-coller

```
Je demarre un nouveau projet a partir d'un template anti-dette cognitive.
Le dossier contient deja la structure de documentation et les skills Claude Code.

### Contexte du projet

**Nom** : [NOM DU PROJET]
**One-liner** : [Une phrase : quoi, pour qui, quel probleme]
**Stack technique** : [Ex: Google Apps Script + HTML Service, ou Next.js + PostgreSQL, ou Python FastAPI, etc.]
**Contraintes** : [Ex: pas de npm runtime, pas de framework frontend, limite de 6min d'execution, etc.]
**Utilisateurs** : [Ex: ~700 managers internes, ou API publique, ou backoffice admin, etc.]

### Ce que je veux que tu fasses

1. **Lis tous les fichiers du projet** pour comprendre la structure du template.

2. **Adapte le CLAUDE.md** :
   - Remplace [NOM DU PROJET] par le vrai nom
   - Remplis "Conventions de code" avec les conventions specifiques a la stack
     qui ne sont PAS couvertes par le linter. Ex pour GAS : suffixe _ pour les
     fonctions privees, retour API { success, data?, error? }, etc.
   - Remplis "Ce que Claude ne doit JAMAIS faire" avec les garde-fous pertinents
     a la stack et au domaine (minimum 3 regles concretes)
   - Laisse "Regles metier critiques" vide — on le remplira au fur et a mesure
     du developpement

3. **Adapte le skill /plan** (.claude/skills/plan/SKILL.md) :
   - Remplace la section 5 "Risques et contraintes techniques" par les
     contraintes chiffrees et concretes de la stack

4. **Adapte le skill /sync** (.claude/skills/sync/SKILL.md) :
   - Remplace "Configuration du linter" par le nom exact du fichier de config
     et les variables a verifier (ex: eslint.config.js + globals custom)

5. **Adapte doc-sync-reminder.sh** :
   - Adapte les extensions de fichiers aux types de la stack

6. **Configure le linting, le formatage et la verification** :
   - Cree un package.json avec les devDependencies appropriees
   - Cree une config linter adaptee a la stack
   - Cree un .prettierrc
   - Ajoute les scripts npm : lint, lint:fix, format, format:check, test, typecheck (si applicable)
   - Si la stack utilise des globals cross-fichiers (comme GAS), declare-les
     explicitement dans la config du linter
   - Remplis la section "Commandes de verification" du CLAUDE.md avec les commandes
     qui doivent passer avant de considerer le travail termine

7. **Adapte le README.md** :
   - Remplis les sections Stack et Prerequisites
   - Remplis les commandes d'installation et de linting
   - Laisse le reste vide — il sera rempli par /sync au fur et a mesure

8. **Initialise git** :
   - git init
   - Premier commit avec la structure template adaptee

9. **Configure les hooks stratifies** :
   Cree le fichier .claude/settings.json avec 3 couches de hooks :
   ```json
   {
     "hooks": {
       "PostToolUse": [
         {
           "matcher": "Edit|Write",
           "hooks": [
             {
               "type": "command",
               "command": "bash \"$CLAUDE_PROJECT_DIR/scripts/hooks/post-edit-format.sh\"",
               "timeout": 30
             }
           ]
         },
         {
           "matcher": "Bash",
           "hooks": [
             {
               "type": "command",
               "command": "bash \"$CLAUDE_PROJECT_DIR/scripts/doc-sync-reminder.sh\""
             }
           ]
         }
       ],
       "PreToolUse": [
         {
           "matcher": "Bash",
           "hooks": [
             {
               "type": "command",
               "command": "bash \"$CLAUDE_PROJECT_DIR/scripts/hooks/pre-bash-guard.sh\"",
               "timeout": 5
             }
           ]
         }
       ],
       "Stop": [
         {
           "matcher": "",
           "hooks": [
             {
               "type": "command",
               "command": "bash \"$CLAUDE_PROJECT_DIR/scripts/hooks/stop-verify.sh\"",
               "timeout": 300
             }
           ]
         }
       ]
     }
   }
   ```
   Puis adapte les scripts de hooks a la stack :
   - `scripts/hooks/post-edit-format.sh` — commande de formatage
   - `scripts/hooks/stop-verify.sh` — commandes de verification (lint, typecheck, tests)

10. **Resume** ce que tu as fait et liste ce qui reste a remplir.

### Principes de gestion du contexte

- **CLAUDE.md** et la **memoire auto** (MEMORY.md) sont tous les deux charges dans le
  system prompt a chaque session. Ne JAMAIS dupliquer entre les deux.
- **CLAUDE.md** = regles prescriptives (conventions, garde-fous, process)
- **Memoire auto** = connaissances acquises (pieges, etat du code, details d'implementation)
- Ne PAS mettre de description fichier par fichier dans CLAUDE.md — Claude deduit la
  structure du code et de la config du linter
- Utiliser `/clear` entre les taches non liees pour preserver le contexte

### Nouveaux dossiers a connaitre

- `docs/plans/` — specs et plans approuves (persistes entre sessions)
- `docs/active/` — contexte des features en cours (progression, verification)
- `docs/solutions/` — resolutions documentees pour reference future
- `docs/VERIFICATION_TEMPLATE.md` — template de trace d'audit

### Ce que tu ne fais PAS maintenant

- Ne remplis pas le PRD — on le fera ensemble dans une session dediee
- Ne remplis pas les scenarios E2E — ils viendront apres le PRD
- Ne cree pas de code source — on planifiera d'abord avec /plan
- Ne cree pas d'ADR — il n'y a pas encore de decisions posterieures au PRD
```

---

## Comment utiliser ce template

### Etape 1 — Copier le template

```bash
cp -r project-template/ mon-nouveau-projet/
cd mon-nouveau-projet/
rm INIT_PROMPT.md  # A supprimer apres utilisation
```

### Etape 2 — Lancer Claude Code et coller le prompt

Ouvrir Claude Code dans le dossier et coller le prompt ci-dessus (adapte).
Claude va configurer tout le tooling et adapter les fichiers a la stack.

### Etape 3 — Rediger le PRD

Dans une nouvelle session Claude Code :

```
On redige le PRD pour ce projet. Lis le CLAUDE.md et le README pour comprendre
le contexte, puis guide-moi pour remplir chaque section de docs/PRD.md.
Pose-moi des questions pour chaque section avant de la rediger.
```

### Etape 4 — Rediger les scenarios E2E

```
Le PRD est termine. Lis docs/PRD.md et genere les scenarios E2E dans
docs/E2E_SCENARIOS.md en couvrant chaque feature (un scenario par parcours
utilisateur distinct). Je validerai chaque scenario.
```

### Etape 5 — Commencer a developper

A partir de la, le workflow normal s'applique :
- `/plan [feature]` avant de coder
- ADR pour les decisions non triviales
- `/done` quand l'implementation est terminee
- `/sync` en fin de session

---

## Philosophie du template

Ce template combat la **dette cognitive** — l'erosion progressive de la comprehension
du systeme quand on travaille avec un agent IA.

**Principes** :
1. **Planifier avant de coder** — le /plan force a reflechir avant d'agir
2. **Documenter le pourquoi, pas le quoi** — le code dit le quoi, les ADR disent le pourquoi
3. **Synchroniser, pas dupliquer** — chaque info vit dans un seul fichier
4. **Rituel > discipline** — les skills et hooks rendent l'oubli impossible
5. **Minimal viable documentation** — pas de bureaucratie, juste ce qui combat la dette cognitive

**Sources** :
- Margaret-Anne Storey — concept de dette cognitive
- Nate Meyvis — bootstrapping adaptatif, context engineering
- Simon Willison — "ne rien commiter qu'on ne peut pas expliquer"
- Anthropic — context engineering, skills, memory management
- Claude Code Handbook — workflow Explore -> Plan -> Code -> Commit
