#!/bin/bash
# deploy-preprod.sh
# Deploiement manuel du code + de la configuration Drupal vers la preprod.
# Ne touche JAMAIS a la base de donnees preprod (hors backup de securite) :
# le contenu y evolue independamment du local. Voir
# .claude/decisions/039-deploiement-preprod-rsync.md pour le contexte.
#
# Usage : scripts/deploy-preprod.sh [--dry-run] [--skip-checks] [--no-backup]
#
#   --dry-run      previsualise les fichiers transferes, ne touche a rien
#   --skip-checks  saute npm run lint / format:check (urgence uniquement)
#   --no-backup    saute le dump de la base preprod avant config:import
#
# Le serveur est toujours purge des fichiers versionnes retires du depot local
# (rsync --delete, cf. plus bas) : un fichier de config supprime cote git ne
# doit jamais rester actif sur le serveur apres un deploiement.

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

for arg in "$@"; do
  case "$arg" in
    --dry-run) DRY_RUN=1 ;;
    --skip-checks) SKIP_CHECKS=1 ;;
    --no-backup) NO_BACKUP=1 ;;
    -h|--help)
      grep '^#' "$0" | sed 's/^# \{0,1\}//'
      exit 0
      ;;
    *)
      echo "Option inconnue : $arg" >&2
      echo "Usage : $0 [--dry-run] [--skip-checks] [--no-backup]" >&2
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
echo "Suppression distante des fichiers absents du local : oui"
echo ""

if [ "$DRY_RUN" -eq 0 ]; then
  read -r -p "Continuer le deploiement ? [y/N] " confirm
  if [ "$confirm" != "y" ] && [ "$confirm" != "Y" ]; then
    echo "Annule."
    exit 0
  fi
fi

echo ""
echo "=== Transfert du code (rsync, arborescence complete filtree par .gitignore) ==="

# ⚠️ Ne PAS repasser par `git ls-files --files-from=-` : rsync ne supprime
# jamais un fichier distant absent du local dans ce mode (cf. son propre
# manuel : « --delete » n'agit que sur les repertoires envoyes ENTIERS, or
# `--files-from` transmet une liste de fichiers individuels, jamais un
# repertoire entier — deja verifie empiriquement : un fichier canari pose a
# la main dans `config/sync/` sur le serveur n'etait pas supprime meme avec
# `--delete` actif). D'ou l'incident du 2026-09-10 : un bloc de config
# supprime cote git restait actif en preprod apres deploiement, invisible a
# `drush deploy` (rien a synchroniser puisque le fichier perime etait
# toujours la, identique a la config active).
#
# A la place : on envoie l'arborescence reelle du depot (recursion normale
# de rsync), filtree par les `.gitignore` (`--filter=':- .gitignore'` : merge
# non ancre, lu dans CHAQUE repertoire traverse, memes regles d'exclusion —
# et de reinclusion via `!` — que git lui-meme). Un fichier exclu du
# transfert par ce filtre est AUSSI exclu de la suppression (comportement
# documente de `--delete`, sans avoir besoin de `--delete-excluded`, qu'il ne
# faut surtout pas ajouter : cela supprimerait les fichiers ignores
# (uploads, `settings.php`, PDF prives...) au lieu de les preserver).
# `.git/` est exclu a la main : ce dossier n'est jamais dans les
# `.gitignore` du depot (il s'exclut lui-meme nativement pour git).
RSYNC_OPTS=(-av --delete --exclude=.git --filter=':- .gitignore' -e "$RSYNC_SSH")
[ "$DRY_RUN" -eq 1 ] && RSYNC_OPTS+=(--dry-run)

rsync "${RSYNC_OPTS[@]}" ./ "$SSH_TARGET:$PREPROD_PATH/"

if [ "$DRY_RUN" -eq 1 ]; then
  echo ""
  echo "Dry-run termine : rien n'a ete transfere, aucune commande distante executee."
  exit 0
fi

echo ""
echo "=== Etapes distantes ==="

echo "-- composer install --no-dev --"
run_remote "cd '$PREPROD_PATH' && composer install --no-dev --optimize-autoloader"

# Sur cet hebergement, composer ne pose pas systematiquement le bit
# executable sur les binaires vendor/bin/ (vu en pratique sur vendor/bin/drush
# et sa cible reelle vendor/drush/drush/drush, tous deux restes en 644).
# Remettre le bit +x explicitement est sans effet si tout est deja correct.
run_remote "cd '$PREPROD_PATH' && chmod +x vendor/bin/* vendor/drush/drush/drush 2>/dev/null || true"

if [ "$NO_BACKUP" -eq 0 ]; then
  for var in PREPROD_BACKUP_PATH PREPROD_DB_NAME; do
    if [ -z "${!var:-}" ]; then
      echo "Erreur : $var manquant ou vide dans $ENV_FILE (necessaire pour le backup, sinon relancer avec --no-backup)." >&2
      exit 1
    fi
  done

  echo "-- backup de la base preprod -> $PREPROD_BACKUP_PATH --"
  # mysqldump direct (invocation fournie par le sysadmin, 2026-09-10), plus
  # drush sql:dump : ce dernier resolvait les identifiants depuis
  # settings.php et echouait sur cet hebergement (mysqldump introuvable pour
  # l'utilisateur sous lequel drush s'executait). `--defaults-extra-file`
  # pointe vers des identifiants dedies, poses par le sysadmin hors de ce
  # depot ; `$PREPROD_BACKUP_PATH` est aussi hors de l'arborescence Drupal
  # ($PREPROD_PATH), pour ne jamais etre efface par le `rsync --delete`
  # (desormais systematique) qui synchronise cette arborescence.
  # Nettoyage prealable des dumps precedents (nommes par horodatage, jamais
  # ecrases) : sans ca, chaque deploiement en ajoute un de plus et le disque
  # accumule indefiniment d'anciennes sauvegardes.
  run_remote "mkdir -p '$PREPROD_BACKUP_PATH' && rm -f '$PREPROD_BACKUP_PATH'/dump_*.sql.gz && mysqldump --defaults-extra-file='$PREPROD_BACKUP_PATH/.my.cnf' --single-transaction '$PREPROD_DB_NAME' | gzip > '$PREPROD_BACKUP_PATH'/dump_\$(date +%Y%m%d_%H%M%S).sql.gz"
fi

echo "-- drush deploy (updb + config:import + cache-rebuild) --"
run_remote "cd '$PREPROD_PATH' && vendor/bin/drush deploy -y"

echo "-- statut --"
run_remote "cd '$PREPROD_PATH' && vendor/bin/drush status"

echo ""
echo "=== Deploiement termine ==="
