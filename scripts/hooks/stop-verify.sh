#!/usr/bin/env bash
# stop-verify.sh
# Hook Stop : verification complete quand Claude termine son travail.
# Couche "lourde" et BLOQUANTE — si une commande echoue, Claude recoit
# l'erreur et doit corriger avant de rendre la main.
#
# Miroir de la section "Commandes de verification" du CLAUDE.md.
# Chaque outil n'est lance que s'il est installe (npm install / composer install).

set -uo pipefail

ROOT="${CLAUDE_PROJECT_DIR:-$(pwd)}"
cd "$ROOT" || exit 0

BIN="$ROOT/node_modules/.bin"
fail=0

run() { # run <label> <cmd...>
  echo "--- $1 ---"
  shift
  if ! "$@"; then
    echo "ECHEC: $1"
    fail=1
  fi
}

# Format (Prettier)
[ -x "$BIN/prettier" ] && run "format:check" "$BIN/prettier" --check .

# Lint JS (ESLint)
[ -x "$BIN/eslint" ] && run "lint:js" "$BIN/eslint" .

# Lint SCSS (Stylelint)
[ -x "$BIN/stylelint" ] && run "lint:css" "$BIN/stylelint" "**/*.scss" --allow-empty-input

# Lint PHP / Twig (PHPCS - standards Drupal)
[ -x "$ROOT/vendor/bin/phpcs" ] && run "lint:php" "$ROOT/vendor/bin/phpcs"

if [ "$fail" -ne 0 ]; then
  echo ""
  echo "Verification echouee — corriger avant de terminer (npm run lint:fix / npm run format peuvent aider)."
  exit 2
fi

exit 0
