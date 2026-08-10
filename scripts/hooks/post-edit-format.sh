#!/usr/bin/env bash
# post-edit-format.sh
# Hook PostToolUse (Edit|Write) : formate automatiquement le fichier edite.
# Couche "legere" — rapide et NON bloquante (sort toujours 0).
#
# Stack : Prettier gere JS / SCSS / CSS / JSON / YAML. Les .php/.twig/.module
# ne sont pas formates ici (--ignore-unknown les ignore) ; ils sont controles
# par PHPCS a la verification (stop-verify.sh) et corrigibles via `composer lint:fix`.

set -uo pipefail

ROOT="${CLAUDE_PROJECT_DIR:-$(pwd)}"
PRETTIER="$ROOT/node_modules/.bin/prettier"

# Rien a faire si Prettier n'est pas installe.
[ -x "$PRETTIER" ] || exit 0

# Recuperer le chemin du fichier edite depuis le JSON du hook (stdin), via Node.
FILE=$(node -e 'let d="";process.stdin.on("data",c=>d+=c).on("end",()=>{try{const j=JSON.parse(d);const i=j.tool_input||{};process.stdout.write(i.file_path||i.path||"")}catch(e){}})' 2>/dev/null)

[ -n "$FILE" ] && [ -f "$FILE" ] || exit 0

# --ignore-unknown : Prettier saute silencieusement les extensions non gerees.
# Respecte aussi .prettierignore. Jamais bloquant.
"$PRETTIER" --write --ignore-unknown "$FILE" >/dev/null 2>&1 || true

exit 0
