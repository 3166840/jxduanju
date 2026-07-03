#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
LOCAL_ENV_FILE="$ROOT_DIR/config/mysql.local.env"

if [[ -f "$LOCAL_ENV_FILE" ]]; then
  set -a
  # shellcheck disable=SC1090
  source "$LOCAL_ENV_FILE"
  set +a
fi

export JX_DB_DRIVER="${JX_DB_DRIVER:-mysql}"
export JX_DB_HOST="${JX_DB_HOST:-183.134.19.154}"
export JX_DB_PORT="${JX_DB_PORT:-3306}"
export JX_DB_DATABASE="${JX_DB_DATABASE:-jxduanju}"
export JX_DB_USERNAME="${JX_DB_USERNAME:-jxduanju}"
export JX_DB_CHARSET="${JX_DB_CHARSET:-utf8mb4}"

if [[ -z "${JX_DB_PASSWORD:-}" ]]; then
  read -r -s -p "MySQL password: " JX_DB_PASSWORD
  echo
  export JX_DB_PASSWORD
fi

cd "$ROOT_DIR"
echo "本地网站已准备启动："
echo "  http://${JX_HOST:-127.0.0.1}:${JX_PORT:-8001}/"
echo
echo "请保持这个窗口打开。"
echo
exec /opt/homebrew/bin/php -S "${JX_HOST:-127.0.0.1}:${JX_PORT:-8001}" -t public public/index.php
