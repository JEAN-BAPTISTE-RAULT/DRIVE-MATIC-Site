---
name: done
description: Checklist de cloture avant commit ou PR. Utiliser quand l'implementation est consideree terminee pour verifier que tout est en ordre.
---

# Definition of Done

Executer cette checklist avant de considerer le travail termine.

## 1. Verification technique

Executer les commandes de verification listees dans la section "Commandes de verification" du CLAUDE.md. **Ne pas continuer si une commande echoue.**

## 2. Documentation

- Si modification d'API ou interface publique : mettre a jour la doc (README, config linter)
- Si nouveau parcours utilisateur : verifier qu'un scenario E2E existe dans `docs/E2E_SCENARIOS.md`

## 3. Self-review

Repondre aux 3 questions :
1. Quelle a ete la decision la plus difficile ?
2. Quelles alternatives ai-je rejetees et pourquoi ?
3. De quoi suis-je le moins confiant ?

## 4. Trace de verification

Si la tache le justifie (feature, bug complexe), copier `docs/VERIFICATION_TEMPLATE.md` dans `docs/active/<feature>/verification.md` et le remplir.

## 5. Commit

Proposer un message de commit descriptif (Conventional Commits si le projet les utilise) et un titre de PR si pertinent.

**Ne PAS marquer "termine" si une etape echoue.**
