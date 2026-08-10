#!/usr/bin/env bash
# pre-bash-guard.sh
# Hook PreToolUse (Bash) : bloque les commandes destructrices.
# Couche "protective" — doit etre rapide (<2s) et bloquer si necessaire.
#
# Retourne un JSON {"decision":"block","reason":"..."} pour bloquer,
# ou exit 0 silencieux pour laisser passer.

set -euo pipefail

INPUT="${CLAUDE_TOOL_INPUT:-}"

if echo "$INPUT" | grep -Eqi "rm -rf|drop database|drop table|truncate table|terraform apply|terraform destroy|kubectl delete|git push.*--force|git reset --hard"; then
  echo '{"decision":"block","reason":"Commande destructrice bloquee. Demander confirmation explicite a l utilisateur."}'
  exit 0
fi

exit 0
