# Lash-Brow-Ohana 開発手順（2台統一 / WSL外操作）

> ✅ **Dev Container を “Reopen in Container” する前に、必ず `make doctor` を実行して Docker Desktop / WSL 統合 / compose 設定が正常か確認してください。**

このリポジトリは **Windows + WSL2(Ubuntu) + Docker Desktop** を前提に、  
**Docker操作は WSL(外側) から統一**して行います。  
Dev Container は「VS Code の編集環境」として使い、**Dev Container 内で docker を叩きません**。

---

## 前提
- Windows 11
- WSL2 + Ubuntu（プロジェクトは **WSL の home 配下**に置く）
- Docker Desktop（WSL2 backend / Ubuntu Integration ON）
- VS Code（Dev Containers 拡張）
- WSL(Ubuntu) に `make` が入っていること  
  - `sudo apt update && sudo apt install -y make`

---

## ディレクトリ
- リポジトリ直下: `~/lash-brow-ohana`
- Laravel 本体: `~/lash-brow-ohana/src`
- docker-compose: `~/lash-brow-ohana/docker-compose.yml`

---

## 0. Reopen 前チェック（必須）
```bash
cd ~/lash-brow-ohana
make doctor
