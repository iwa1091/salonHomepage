#!/usr/bin/env bash
set -euo pipefail

ROOT="${1:-$(pwd)}"

echo "== Lash-Brow-Ohana Doctor =="
echo "Project: ${ROOT}"
echo ""

# ---- WSL 判定 ----
if grep -qi microsoft /proc/version 2>/dev/null; then
  echo "[OK] WSL detected: ${WSL_DISTRO_NAME:-unknown}"
else
  echo "[WARN] Not running in WSL (this is OK, but this doctor assumes WSL usage)."
fi

# ---- docker CLI 判定 ----
if ! command -v docker >/dev/null 2>&1; then
  echo "[NG] docker command not found."
  echo "     -> WSL側に docker CLI が入っていない可能性があります。"
  exit 1
fi
echo "[OK] docker CLI: $(docker --version)"

# ---- Docker Desktop(=daemon) 起動判定 ----
if ! docker info >/dev/null 2>&1; then
  echo "[NG] Docker daemon not reachable from here."
  echo "     -> Docker Desktop が起動していない / WSL Integration がOFF の可能性があります。"
  exit 1
fi
echo "[OK] Docker daemon reachable."

# ---- docker compose plugin 判定 ----
if ! docker compose version >/dev/null 2>&1; then
  echo "[NG] docker compose plugin not available."
  echo "     -> 'docker compose' が使えません（compose plugin 未導入の可能性）。"
  exit 1
fi
echo "[OK] docker compose: $(docker compose version --short 2>/dev/null || docker compose version | head -n 1)"

# ---- Docker Desktop っぽさ確認（目安）----
OS="$(docker info --format '{{.OperatingSystem}}' 2>/dev/null || true)"
if [[ "${OS}" == *"Docker Desktop"* ]]; then
  echo "[OK] Docker Desktop detected: ${OS}"
else
  echo "[INFO] OperatingSystem='${OS}' (Docker Desktop 以外の可能性もあります)"
fi

# ---- WSL統合の目安：docker.sock のリンク先 ----
SOCK="$(readlink -f /var/run/docker.sock 2>/dev/null || true)"
if [[ -n "${SOCK}" ]]; then
  if [[ "${SOCK}" == *"docker-desktop"* || "${SOCK}" == /mnt/wsl/* ]]; then
    echo "[OK] WSL integration likely (docker.sock -> ${SOCK})"
  else
    echo "[INFO] docker.sock -> ${SOCK} (WSL内に Docker Engine を入れている構成かもしれません)"
  fi
else
  echo "[WARN] /var/run/docker.sock not found (環境次第ではあり得ます)"
fi

echo ""

# ---- プロジェクト構成チェック ----
if [[ ! -f "${ROOT}/docker-compose.yml" ]]; then
  echo "[NG] docker-compose.yml not found at: ${ROOT}/docker-compose.yml"
  exit 1
fi

# compose の構文/解決チェック（ここが通れば「統合はほぼOK」）
docker compose -f "${ROOT}/docker-compose.yml" config >/dev/null
echo "[OK] docker-compose.yml config OK"

# 必須サービスの存在チェック（不足しても WARN に留める）
SERVICES="$(docker compose -f "${ROOT}/docker-compose.yml" config --services)"
for s in nginx php mysql node mailhog phpmyadmin ngrok; do
  if echo "${SERVICES}" | grep -qx "${s}"; then
    echo "[OK] service exists: ${s}"
  else
    echo "[WARN] service missing: ${s}"
  fi
done

echo ""
echo "== docker compose ps (current status) =="
docker compose -f "${ROOT}/docker-compose.yml" ps || true

echo ""
echo "✅ Doctor check passed."
