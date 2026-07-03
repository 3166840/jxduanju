#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$ROOT_DIR"

clear
echo "精秀短剧本地网站启动器"
echo
echo "正在检查 PHP 和 MySQL 配置..."

if [[ ! -x /opt/homebrew/bin/php ]]; then
  echo "没有找到 PHP：/opt/homebrew/bin/php"
  echo "请先安装 PHP，或联系我继续处理。"
  echo
  read -r -p "按回车关闭窗口..."
  exit 1
fi

if [[ ! -f "$ROOT_DIR/config/mysql.local.env" ]]; then
  echo "没有找到数据库配置：config/mysql.local.env"
  echo "请先写入 MySQL 配置。"
  echo
  read -r -p "按回车关闭窗口..."
  exit 1
fi

echo "准备启动：http://127.0.0.1:8001/"
echo
echo "启动成功后不要关闭这个窗口；关闭窗口网站也会停止。"
echo

"$ROOT_DIR/tools/start-mysql-server.sh"

echo
echo "服务已停止。"
read -r -p "按回车关闭窗口..."
