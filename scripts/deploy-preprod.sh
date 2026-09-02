#!/bin/bash
# deploy-preprod.sh
# Deploiement manuel du code + de la configuration Drupal vers la preprod.
# Ne touche JAMAIS a la base de donnees preprod (hors backup de securite) :
# le contenu y evolue independamment du local. Voir
# .claude/decisions/039-deploiement-preprod-rsync.md pour le contexte.
#
# Usage : scripts/deploy-preprod.sh [--dry-run] [--skip-checks] [--no-backup] [--prune]
#
#   --dry-run      previsualise les fichiers transferes, ne touche a rien
#   --skip-checks  saute npm run lint / format:check (urgence uniquement)
#   --no-backup    saute le dump de la base preprod avant config:import
#   --prune        supprime aussi, sur le serveur, les fichiers absents du
#                  git local (rsync --delete) ; desactive par defaut

set -euo pipefail
cd "$(dirname "$0")/.."

ENV_FILE=".env.deploy"
if [ ! -f "$ENV_FILE" ]; then
  echo "Erreur : $ENV_FILE introuvable." >&2
  echo "Copier .env.deploy.example vers .env.deploy et renseigner les valeurs." >&2
  exit 1
fi
# shellcheck disable=SC1090
source "$ENV_FILE"

for var in PREPROD_HOST PREPROD_USER PREPROD_PORT PREPROD_PATH; do
  if [ -z "${!var:-}" ]; then
    echo "Erreur : $var manquant ou vide dans $ENV_FILE." >&2
    exit 1
  fi
done

DRY_RUN=0
SKIP_CHECKS=0
NO_BACKUP=0
PRUNE=0

for arg in "$@"; do
  case "$arg" in
    --dry-run) DRY_RUN=1 ;;
    --skip-checks) SKIP_CHECKS=1 ;;
    --no-backup) NO_BACKUP=1 ;;
    --prune) PRUNE=1 ;;
    -h|--help)
      grep '^#' "$0" | sed 's/^# \{0,1\}//'
      exit 0
      ;;
    *)
      echo "Option inconnue : $arg" >&2
      echo "Usage : $0 [--dry-run] [--skip-checks] [--no-backup] [--prune]" >&2
      exit 1
      ;;
  esac
done

SSH_TARGET="$PREPROD_USER@$PREPROD_HOST"
RSYNC_SSH="ssh -p $PREPROD_PORT"

run_remote() {
  ssh -p "$PREPROD_PORT" "$SSH_TARGET" "$1"
}

echo "=== Garde-fous locaux ==="

branch=$(git rev-parse --abbrev-ref HEAD)
if [ "$branch" != "main" ]; then
  echo "Erreur : branche courante '$branch', attendu 'main' (une seule branche sur ce depot)." >&2
  exit 1
fi

if [ -n "$(git status --porcelain)" ]; then
  echo "Erreur : working tree non propre, rien ne doit etre deploye qui ne soit committe :" >&2
  git status --short >&2
  exit 1
fi

if [ "$SKIP_CHECKS" -eq 0 ]; then
  echo "-- npm run lint --"
  npm run lint
  echo "-- npm run format:check --"
  npm run format:check
else
  echo "Verifications lint/format ignorees (--skip-checks)."
fi

echo ""
echo "=== Recapitulatif ==="
echo "Cible     : $SSH_TARGET:$PREPROD_PORT:$PREPROD_PATH"
echo "Commit    : $(git rev-parse --short HEAD) ($(git log -1 --format=%s))"
if [ "$NO_BACKUP" -eq 1 ]; then
  echo "Backup DB : non (--no-backup)"
else
  echo "Backup DB : oui, avant config:import"
fi
if [ "$PRUNE" -eq 1 ]; then
  echo "Suppression distante des fichiers absents du local : oui (--prune)"
else
  echo "Suppression distante des fichiers absents du local : non"
fi
echo ""

if [ "$DRY_RUN" -eq 0 ]; then
  read -r -p "Continuer le deploiement ? [y/N] " confirm
  if [ "$confirm" != "y" ] && [ "$confirm" != "Y" ]; then
    echo "Annule."
    exit 0
  fi
fi

echo ""
echo "=== Transfert du code (rsync, uniquement les fichiers suivis par git) ==="

RSYNC_OPTS=(-av --files-from=- --from0 -e "$RSYNC_SSH")
[ "$DRY_RUN" -eq 1 ] && RSYNC_OPTS+=(--dry-run)
[ "$PRUNE" -eq 1 ] && RSYNC_OPTS+=(--delete)

git ls-files -z | rsync "${RSYNC_OPTS[@]}" ./ "$SSH_TARGET:$PREPROD_PATH/"

if [ "$DRY_RUN" -eq 1 ]; then
  echo ""
  echo "Dry-run termine : rien n'a ete transfere, aucune commande distante executee."
  exit 0
fi

echo ""
echo "=== Etapes distantes ==="

echo "-- composer install --no-dev --"
run_remote "cd '$PREPROD_PATH' && composer install --no-dev --optimize-autoloader"

if [ "$NO_BACKUP" -eq 0 ]; then
  echo "-- backup de la base preprod --"
  run_remote "cd '$PREPROD_PATH' && mkdir -p backups && php vendor/bin/drush sql:dump --gzip --result-file=backups/preprod-\$(date +%Y%m%d-%H%M%S).sql"
fi

echo "-- drush deploy (updb + config:import + cache-rebuild) --"
run_remote "cd '$PREPROD_PATH' && php vendor/bin/drush deploy -y"

echo "-- statut --"
run_remote "cd '$PREPROD_PATH' && php vendor/bin/drush status"

echo ""
echo "=== Deploiement termine ==="
