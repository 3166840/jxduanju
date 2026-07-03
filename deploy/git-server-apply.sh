#!/usr/bin/env bash
set -euo pipefail

SITE_DIR="/www/wwwroot/duanju"
cd "$SITE_DIR"

mkdir -p runtime/data/backups log

if [[ -d public/assets ]]; then
  rm -rf assets
  cp -R public/assets assets
fi

if id www >/dev/null 2>&1; then
  chown -R www:www "$SITE_DIR"
fi

chmod 750 config 2>/dev/null || true
chmod 640 config/mysql.local.env config/database.php 2>/dev/null || true
chmod -R 755 app config public route runtime log assets 2>/dev/null || true

PHP_BIN="$(command -v php || true)"
if [[ -z "$PHP_BIN" ]]; then
  PHP_BIN="$(ls /www/server/php/*/bin/php 2>/dev/null | sort -V | tail -n 1 || true)"
fi

if [[ -z "$PHP_BIN" ]]; then
  echo "没有找到 PHP，请先在宝塔安装 PHP 8.1+。"
  exit 1
fi

find app config public route -name '*.php' -print0 | xargs -0 -n1 "$PHP_BIN" -l >/tmp/jxduanju-php-lint.log

echo "代码整理完成：$SITE_DIR"
