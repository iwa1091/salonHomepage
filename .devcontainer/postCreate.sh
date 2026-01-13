#!/usr/bin/env bash
set -euo pipefail

cd /workspaces/lash-brow-ohana/src

# PHP 依存
if [ -f composer.json ] && [ ! -d vendor ]; then
  composer install
fi

# Node 依存（Vite用）
if [ -f package.json ] && [ ! -d node_modules ]; then
  npm install
fi

# Laravel キャッシュ系（安全に）
if [ -f artisan ]; then
  php artisan config:clear >/dev/null 2>&1 || true
  php artisan cache:clear  >/dev/null 2>&1 || true
fi

echo "✅ DevContainer ready (cwd: $(pwd))"
