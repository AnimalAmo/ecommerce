#!/usr/bin/env bash
# Usage:
#   t.sh [phpunit args...]          test in Docker (Sail, PHP 8.3)
#   t.sh --native [phpunit args...] test con il PHP 8.3 di WSL, senza container
#   t.sh --pint [pint args...]      Laravel Pint in Docker
#   t.sh --shell                    shell dentro il container
#   t.sh --no-sync ...              salta l'rsync (riusa il mirror com'è)
#
# Il repo di lavoro sta su Windows (D:); qui dentro ne teniamo una copia sul
# filesystem ext4 di WSL. Docker bind-monta QUESTA copia, mai /mnt/d: montare
# l'SSD di Windows nel container lo renderebbe lentissimo.
set -e

SRC=/mnt/d/Code/algomera/animal_amo/ecommerce/
DST=$HOME/aa/

SYNC=1
MODE=test
while [[ $# -gt 0 ]]; do
  case "$1" in
    --native)  MODE=native; shift ;;
    --pint)    MODE=pint;   shift ;;
    --shell)   MODE=shell;  shift ;;
    --no-sync) SYNC=0;      shift ;;
    *) break ;;
  esac
done

if [[ $SYNC -eq 1 ]]; then
  # .env è escluso: la copia WSL ha la sua (DB_HOST=mysql per il container).
  # vendor/ è escluso perché installato qui dentro, per l'architettura giusta.
  rsync -a --delete \
    --exclude node_modules --exclude .git --exclude vendor --exclude .env \
    --exclude 'storage/logs' --exclude 'storage/framework' --exclude 'storage/app' \
    --exclude 'storage/*.docx' --exclude 'storage/articoli' --exclude public/hot \
    "$SRC" "$DST"
fi

cd "$DST"
mkdir -p storage/framework/{cache,sessions,views,testing} storage/logs

export WWWUSER="${WWWUSER:-$(id -u)}"
export WWWGROUP="${WWWGROUP:-$(id -g)}"

strip() { sed 's/\x1b\[[0-9;]*m//g'; }

case "$MODE" in
  native) php artisan test "$@" 2>&1 | strip ;;
  pint)   docker compose run --rm laravel.test vendor/bin/pint "$@" 2>&1 | strip ;;
  shell)  docker compose run --rm laravel.test bash ;;
  test)   docker compose run --rm laravel.test php artisan test "$@" 2>&1 | strip ;;
esac
