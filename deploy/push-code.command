#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"
cd "$ROOT_DIR"

clear 2>/dev/null || true
echo "精秀短剧 Git 同步"
echo

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
  echo "当前目录还没有初始化 Git。请先按我给你的步骤绑定 Gitee/GitHub 仓库。"
  read -r -p "按回车关闭窗口..."
  exit 1
fi

if ! git remote get-url origin >/dev/null 2>&1; then
  echo "还没有绑定远程仓库 origin。请先添加 Gitee/GitHub 仓库地址。"
  read -r -p "按回车关闭窗口..."
  exit 1
fi

git status --short
echo
read -r -p "请输入本次更新说明，直接回车使用默认说明：" MESSAGE
MESSAGE="${MESSAGE:-更新代码}"

git add .
if git diff --cached --quiet; then
  echo "没有发现需要提交的新改动。"
else
  git commit -m "$MESSAGE"
fi

git push origin main
echo
echo "已推送到 Git 仓库。服务器如果配置了自动拉取，会自动更新。"
read -r -p "按回车关闭窗口..."
