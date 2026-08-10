---
name: sync
description: Synchroniser la documentation du projet en fin de session de developpement. Ce skill doit etre utilise quand l'utilisateur demande de synchroniser la doc, de mettre a jour la theorie du systeme, ou en fin de session apres des modifications de code.
---

# Synchronisation de la theorie du systeme

Verifier et synchroniser chaque couche documentaire apres une session de developpement. Ne modifier que ce qui a reellement evolue — pas de changements cosmetiques.

## Checklist de synchronisation

### 1. docs/PRD.md (specifications fonctionnelles)

- Une feature a-t-elle ete implementee differemment de ce que le PRD specifie ? Mettre a jour les criteres d'acceptation.
- Un nouveau cas limite ou mode de defaillance a-t-il ete decouvert ? L'ajouter dans la feature concernee.
- Un algorithme ou un flux a-t-il evolue ? Mettre a jour la description comportementale.

### 2. README.md (documentation technique)

- L'architecture, les IDs, les commandes, les flux de donnees refletent-ils les changements ?
- De nouveaux points d'attention a documenter ?

### 3. Configuration du linter (globals cross-fichiers)

- `eslint.config.mjs` : de nouveaux globals Drupal utilises au runtime (ex. `CKEDITOR`, `Backbone`, `_`, `Cookies`) sont-ils declares dans le bloc `globals` ? Toute nouvelle regle a ajuster ?
- `phpcs.xml.dist` : de nouveaux chemins de code custom a inclure/exclure, ou une exception de sniff a documenter ?
- `.stylelintrc.json` : une regle Stylelint a ajouter/desactiver (avec justification) ?
- `package.json` : les scripts de verification (`lint`, `format:check`, `test`) reflètent-ils toujours la realite du projet ?

### 4. docs/E2E_SCENARIOS.md (scenarios de non-regression)

- Un nouveau parcours utilisateur a-t-il ete introduit ? Ajouter le scenario.
- Un comportement attendu a-t-il change ? Modifier le scenario existant.
- Tracer dans le tableau "Historique des modifications".

### 5. CLAUDE.md (instructions agent)

- De nouvelles regles metier ou conventions decouvertes pendant la session ?
- De nouveaux garde-fous a ajouter ?

### 6. .claude/decisions/ (ADR)

- Une decision architecturale a-t-elle ete prise pendant cette session sans etre documentee ? Si oui, creer l'ADR via le template `.claude/decisions/TEMPLATE.md` et mettre a jour l'INDEX.

### 7. Memoire auto

- Un apprentissage, un piege, un pattern a retenir pour les futures sessions ? Sauvegarder dans la memoire auto.
- **Attention a la duplication** : la memoire auto est chargee en parallele du CLAUDE.md a chaque session. Ne PAS y repeter des regles deja dans CLAUDE.md. La memoire auto = connaissances acquises (pieges decouverts, etat du code, details d'implementation). CLAUDE.md = regles prescriptives.
- Si un ADR a ete cree, ajouter une reference courte (pas un resume) dans la memoire auto.

### 8. docs/active/, docs/plans/ et docs/solutions/

- Des fichiers de progression dans `docs/active/` sont-ils devenus obsoletes (feature mergee) ? Les archiver ou supprimer.
- Un plan dans `docs/plans/` doit-il etre mis a jour apres l'implementation ?
- Un fichier `verification.md` doit-il etre complete ?
- Une resolution notable merite-t-elle d'etre documentee dans `docs/solutions/` pour reference future ?

## Resume de fin de session

Produire 3-5 bullet points resumant ce qui a change dans la comprehension du systeme.
