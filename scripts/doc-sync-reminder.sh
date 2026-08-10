#!/bin/bash
# doc-sync-reminder.sh
# Hook post-commit : affiche un rappel des docs potentiellement impactees
# par les fichiers commites. Le rappel est visible par Claude Code,
# qui peut alors proposer de lancer /sync.

files=$(git diff --name-only HEAD~1 HEAD 2>/dev/null)
[ -z "$files" ] && exit 0

reminders=""

# Code source modifie → verifier architecture et interfaces
if echo "$files" | grep -qE '\.(php|module|inc|install|profile|theme|twig|js|scss|css|ya?ml)$'; then
  reminders="$reminders\n  - README.md (architecture, commandes ?)"
  reminders="$reminders\n  - Config linter (eslint.config.mjs / phpcs.xml.dist : globals, chemins ?)"
  reminders="$reminders\n  - CLAUDE.md (conventions, garde-fous ?)"
fi

# Code comportemental → verifier specs et tests
if echo "$files" | grep -qE '\.(php|module|inc|install|profile|theme|twig|js)$'; then
  reminders="$reminders\n  - docs/PRD.md (comportement, features ?)"
  reminders="$reminders\n  - docs/E2E_SCENARIOS.md (parcours utilisateur ?)"
fi

# Config ou doc modifiee → verifier coherence
if echo "$files" | grep -qiE '(config|\.json|\.ya?ml|CLAUDE\.md|\.claude/)'; then
  reminders="$reminders\n  - CLAUDE.md (conventions, garde-fous ?)"
fi

# Deduplication des reminders
reminders=$(echo -e "$reminders" | sort -u)

if [ -n "$reminders" ]; then
  echo ""
  echo "--- Doc sync reminder ---"
  echo "Fichiers commites :"
  echo "$files" | sed 's/^/  /'
  echo ""
  echo "Docs potentiellement impactees :"
  echo "$reminders"
  echo ""
  echo "Lancer /sync en fin de session."
  echo "-------------------------"
fi
