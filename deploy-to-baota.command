#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT_DIR"

SERVER="root@183.134.19.154"
SSH_PORT="22"
REMOTE_DIR="/www/wwwroot/duanju"
KEY_FILE="$ROOT_DIR/runtime/deploy/ssh/jxduanju_deploy_key"
PACKAGE="$ROOT_DIR/runtime/deploy/jxduanju-code.tar.gz"

clear 2>/dev/null || true
echo "精秀短剧宝塔部署"
echo

if [[ ! -f "$KEY_FILE" ]]; then
  echo "找不到 SSH 私钥：$KEY_FILE"
  read -r -p "按回车关闭窗口..."
  exit 1
fi

mkdir -p "$ROOT_DIR/runtime/deploy"
echo "正在打包本地代码..."
tar -czf "$PACKAGE" \
  --exclude='*.DS_Store' \
  --exclude='config/mysql.local.env' \
  app config public route composer.json README.md index.php .htaccess

echo "正在连接服务器并上传..."
scp -i "$KEY_FILE" -P "$SSH_PORT" \
  -o StrictHostKeyChecking=no \
  -o UserKnownHostsFile="$ROOT_DIR/runtime/deploy/ssh/known_hosts" \
  "$PACKAGE" "$SERVER:/tmp/jxduanju-code.tar.gz"

REMOTE_SCRIPT="$ROOT_DIR/runtime/deploy/remote-deploy.sh"
cat > "$REMOTE_SCRIPT" <<'REMOTE'
set -euo pipefail

REMOTE_DIR="/www/wwwroot/duanju"
mkdir -p "$REMOTE_DIR"
cd "$REMOTE_DIR"

rm -rf app public route assets index.php .htaccess composer.json README.md
mkdir -p config
tar -xzf /tmp/jxduanju-code.tar.gz -C "$REMOTE_DIR"

rm -rf assets
cp -R public/assets assets

mkdir -p runtime/data/backups log
if id www >/dev/null 2>&1; then
  chown -R www:www "$REMOTE_DIR"
fi
chmod 750 config 2>/dev/null || true
chmod 640 config/mysql.local.env 2>/dev/null || true
chmod -R 755 public assets app route runtime log 2>/dev/null || true

PHP_BIN="$(command -v php || true)"
if [[ -z "$PHP_BIN" ]]; then
  PHP_BIN="$(ls /www/server/php/*/bin/php 2>/dev/null | sort -V | tail -n 1 || true)"
fi
if [[ -z "$PHP_BIN" ]]; then
  echo "服务器没有找到 PHP，请在宝塔安装 PHP 8.1+。"
  exit 1
fi

echo "服务器 PHP：$($PHP_BIN -v | head -n 1)"
find app config public route -name '*.php' -print0 | xargs -0 -n1 "$PHP_BIN" -l >/tmp/jxduanju-php-lint.log
echo "PHP 语法检查通过"

rm -f /tmp/jxduanju-code.tar.gz /tmp/jxduanju-remote-deploy.sh
echo "部署目录：$REMOTE_DIR"
REMOTE

scp -i "$KEY_FILE" -P "$SSH_PORT" \
  -o StrictHostKeyChecking=no \
  -o UserKnownHostsFile="$ROOT_DIR/runtime/deploy/ssh/known_hosts" \
  "$REMOTE_SCRIPT" "$SERVER:/tmp/jxduanju-remote-deploy.sh"

echo "正在服务器解压和检查..."
ssh -tt -i "$KEY_FILE" -p "$SSH_PORT" \
  -o StrictHostKeyChecking=no \
  -o UserKnownHostsFile="$ROOT_DIR/runtime/deploy/ssh/known_hosts" \
  "$SERVER" "bash /tmp/jxduanju-remote-deploy.sh"

echo
echo "部署完成。正在验证域名响应..."
curl -I --max-time 15 "http://www.jztpay.cn/" || true
echo
echo "完成。"
read -r -p "按回车关闭窗口..."
