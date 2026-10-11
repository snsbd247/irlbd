#!/bin/bash
# Daily backup for the IR Limited website on cPanel (run from cron, no root needed).
#
#   bash ~/irlbd-api/scripts/backup.sh
#
# Creates in $BACKUP_DIR (default ~/irlbd-backups/daily):
#   db-YYYY-MM-DD.sql.gz      MySQL dump of the website database (from .env)
#   files-YYYY-MM-DD.tar.gz   uploaded media (public uploads) + applicant CVs (private)
# and deletes backups older than $KEEP_DAYS (default 14). Never touches other sites.
set -euo pipefail
umask 077

APP_DIR="${APP_DIR:-$(cd "$(dirname "$0")/.." && pwd)}"
BACKUP_DIR="${BACKUP_DIR:-$HOME/irlbd-backups/daily}"
KEEP_DAYS="${KEEP_DAYS:-14}"
STAMP="$(date +%F)"

env_value() {
  # Reads KEY=value from .env (handles optional quotes); no shell evaluation of the file.
  grep -E "^$1=" "$APP_DIR/.env" | tail -n1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
}

DB_NAME="$(env_value DB_DATABASE)"
DB_USER="$(env_value DB_USERNAME)"
DB_PASS="$(env_value DB_PASSWORD)"
DB_HOST="$(env_value DB_HOST)"; DB_HOST="${DB_HOST:-localhost}"
UPLOADS="$(env_value UPLOADS_ROOT)"; UPLOADS="${UPLOADS:-$APP_DIR/public/uploads}"

mkdir -p "$BACKUP_DIR"

# Credentials go in a private temp file, never on the command line (visible in `ps`).
CNF="$(mktemp)"
trap 'rm -f "$CNF"' EXIT
printf '[client]\nuser=%s\npassword=%s\nhost=%s\n' "$DB_USER" "$DB_PASS" "$DB_HOST" > "$CNF"

mysqldump --defaults-extra-file="$CNF" --single-transaction --quick --routines --no-tablespaces "$DB_NAME" \
  | gzip -9 > "$BACKUP_DIR/db-$STAMP.sql.gz.part"
mv "$BACKUP_DIR/db-$STAMP.sql.gz.part" "$BACKUP_DIR/db-$STAMP.sql.gz"

PATHS=()
[ -d "$UPLOADS" ] && PATHS+=("$UPLOADS")
[ -d "$APP_DIR/storage/app/private" ] && PATHS+=("$APP_DIR/storage/app/private")
if [ ${#PATHS[@]} -gt 0 ]; then
  tar -czf "$BACKUP_DIR/files-$STAMP.tar.gz.part" "${PATHS[@]}" 2>/dev/null
  mv "$BACKUP_DIR/files-$STAMP.tar.gz.part" "$BACKUP_DIR/files-$STAMP.tar.gz"
fi

find "$BACKUP_DIR" -maxdepth 1 -type f \( -name 'db-*.sql.gz' -o -name 'files-*.tar.gz' -o -name '*.part' \) -mtime +"$KEEP_DAYS" -delete

echo "Backup done: $BACKUP_DIR ($(du -sh "$BACKUP_DIR" | cut -f1) total)"
