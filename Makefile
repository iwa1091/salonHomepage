SHELL := /bin/bash

COMPOSE      := docker compose
PHP_SERVICE  := php
NODE_SERVICE := node
MYSQL_SERVICE := mysql
APP_DIR      := /var/www

ENV_FILE     := src/.env
ENV_EXAMPLE  := src/.env.example

UID := $(shell id -u)
GID := $(shell id -g)
USER_NAME := $(shell id -un)

# UID/GID 実行時に HOME が不定になりやすいので /tmp に固定（composer/npm の権限詰まり防止）
PHP_EXEC_USER  := $(COMPOSE) exec -e HOME=/tmp -e COMPOSER_HOME=/tmp -u $(UID):$(GID) $(PHP_SERVICE)
NODE_EXEC_USER := $(COMPOSE) exec -e HOME=/tmp -e npm_config_cache=/tmp/.npm -u $(UID):$(GID) $(NODE_SERVICE)
PHP_EXEC_ROOT  := $(COMPOSE) exec -u 0:0 $(PHP_SERVICE)

.DEFAULT_GOAL := help

.PHONY: help
help:
	@echo ""
	@echo "Commands:"
	@echo "  make doctor         Health check (Docker Desktop/WSL/compose/config)"
	@echo "  make doctor-up      doctor -> up -> ps"
	@echo ""
	@echo "  make up             Start containers (build if needed)"
	@echo "  make down           Stop containers"
	@echo "  make restart        Restart containers"
	@echo "  make logs           Follow logs"
	@echo "  make ps             Show containers"
	@echo "  make config         Validate compose config"
	@echo ""
	@echo "  make init           First time setup (doctor/up/env/perms/install/key/wait-mysql/migrate/link/clear)"
	@echo "  make install        composer install + npm(ci|install)"
	@echo "  make wait-mysql     Wait for MySQL to be ready"
	@echo "  make key            php artisan key:generate --force"
	@echo "  make migrate        php artisan migrate (wait-mysql first)"
	@echo "  make fresh          php artisan migrate:fresh --seed (wait-mysql first)"
	@echo ""
	@echo "  make php            Enter php container"
	@echo "  make node           Enter node container"
	@echo "  make artisan c='...'  Run artisan command"
	@echo "  make composer c='...' Run composer command"
	@echo "  make npm c='...'      Run npm command"
	@echo "  make dev            Run Vite dev server (host=0.0.0.0)"
	@echo ""
	@echo "  make fix-perms      Ensure storage/bootstrap/cache perms (root)"
	@echo "  make chown          sudo chown -R (fallback)"
	@echo ""

# -----------------------
# Health check (WSL外操作前提)
# -----------------------
.PHONY: doctor doctor-up config
doctor:
	@echo "== Doctor =="
	@command -v docker >/dev/null 2>&1 || { echo "[NG] docker command not found (WSL側に docker CLI がありません)"; exit 1; }
	@docker info >/dev/null 2>&1 || { echo "[NG] Docker daemon not reachable. Docker Desktop起動/WSL統合を確認してください"; exit 1; }
	@docker compose version >/dev/null 2>&1 || { echo "[NG] docker compose plugin not available"; exit 1; }
	@echo "[OK] docker: $$(docker --version)"
	@echo "[OK] compose: $$(docker compose version --short 2>/dev/null || docker compose version | head -n 1)"
	@echo "[OK] daemon reachable"
	@echo ""
	@echo "== Compose config check =="
	@$(COMPOSE) config >/dev/null
	@echo "[OK] docker-compose.yml config OK"
	@echo ""

doctor-up: doctor up ps ## doctor -> up -> ps

config:
	@$(COMPOSE) config

# -----------------------
# Docker lifecycle
# -----------------------
.PHONY: up down restart logs ps
up:
	$(COMPOSE) up -d --build

down:
	$(COMPOSE) down

restart: down up

logs:
	$(COMPOSE) logs -f --tail=200

ps:
	$(COMPOSE) ps

# -----------------------
# Shell access
# -----------------------
.PHONY: php node
php:
	$(COMPOSE) exec $(PHP_SERVICE) bash

node:
	$(COMPOSE) exec $(NODE_SERVICE) bash

# -----------------------
# Generic runners
# -----------------------
.PHONY: artisan composer npm
artisan:
	@if [ -z "$(c)" ]; then echo "Usage: make artisan c='migrate'"; exit 1; fi
	$(PHP_EXEC_USER) bash -lc "cd $(APP_DIR) && php artisan $(c)"

composer:
	@if [ -z "$(c)" ]; then echo "Usage: make composer c='install'"; exit 1; fi
	$(PHP_EXEC_USER) bash -lc "cd $(APP_DIR) && composer $(c)"

npm:
	@if [ -z "$(c)" ]; then echo "Usage: make npm c='run dev'"; exit 1; fi
	$(NODE_EXEC_USER) bash -lc "cd $(APP_DIR) && npm $(c)"

# -----------------------
# Setup / Laravel helpers
# -----------------------
.PHONY: env install npm-install key migrate seed fresh storage-link clear optimize fix-perms wait-mysql

env:
	@if [ ! -f "$(ENV_FILE)" ]; then \
		if [ -f "$(ENV_EXAMPLE)" ]; then \
			cp "$(ENV_EXAMPLE)" "$(ENV_FILE)"; \
			echo "✅ created: $(ENV_FILE) from $(ENV_EXAMPLE)"; \
		else \
			echo "❌ $(ENV_EXAMPLE) not found. Please prepare it."; \
			exit 1; \
		fi \
	else \
		echo "ℹ️  $(ENV_FILE) already exists"; \
	fi

npm-install:
	@$(NODE_EXEC_USER) bash -lc 'cd $(APP_DIR) && if [ -f package-lock.json ]; then npm ci; else npm install; fi'

install:
	@$(MAKE) composer c="install"
	@$(MAKE) npm-install

key:
	@$(PHP_EXEC_USER) bash -lc "cd $(APP_DIR) && php artisan key:generate --force"

# MySQL が ready になるまで待つ（init/migrate の安定化）
wait-mysql:
	@echo "== Wait MySQL =="
	@for i in $$(seq 1 60); do \
		if $(COMPOSE) exec -T $(MYSQL_SERVICE) bash -lc "mysqladmin ping -h 127.0.0.1 -uroot -proot --silent" >/dev/null 2>&1; then \
			echo "✅ MySQL is ready"; \
			exit 0; \
		fi; \
		echo "… waiting MySQL ($$i/60)"; \
		sleep 1; \
	done; \
	echo "❌ MySQL did not become ready in time"; \
	exit 1

migrate: wait-mysql
	@$(PHP_EXEC_USER) bash -lc "cd $(APP_DIR) && php artisan migrate"

seed:
	@$(PHP_EXEC_USER) bash -lc "cd $(APP_DIR) && php artisan db:seed"

fresh: wait-mysql
	@$(PHP_EXEC_USER) bash -lc "cd $(APP_DIR) && php artisan migrate:fresh --seed"

storage-link:
	@$(PHP_EXEC_USER) bash -lc "cd $(APP_DIR) && php artisan storage:link || true"

clear:
	@$(PHP_EXEC_USER) bash -lc "cd $(APP_DIR) && php artisan optimize:clear"

optimize:
	@$(PHP_EXEC_USER) bash -lc "cd $(APP_DIR) && php artisan optimize"

fix-perms:
	@$(PHP_EXEC_ROOT) bash -lc "cd $(APP_DIR) && \
		set -e; \
		mkdir -p storage/framework/{views,cache,sessions} storage/logs bootstrap/cache; \
		GROUP=$$(id -gn www-data 2>/dev/null || echo $(GID)); \
		chown -R $(UID):$$GROUP storage bootstrap/cache; \
		chmod -R ug+rwX storage bootstrap/cache; \
		find storage bootstrap/cache -type d -exec chmod 2775 {} \; ; \
		touch storage/logs/laravel.log >/dev/null 2>&1 || true"

# -----------------------
# Frontend (Vite)
# -----------------------
.PHONY: dev build
dev:
	@$(NODE_EXEC_USER) bash -lc "cd $(APP_DIR) && npm run dev -- --host 0.0.0.0 --port 5173"

build:
	@$(NODE_EXEC_USER) bash -lc "cd $(APP_DIR) && npm run build"

# -----------------------
# First time setup
# -----------------------
.PHONY: init
init: doctor up env fix-perms install key wait-mysql migrate storage-link clear
	@echo ""
	@echo "🎉 Setup completed!"
	@echo "Next (dev):"
	@echo "  make dev"
	@echo ""

# -----------------------
# Fallback chown (host)
# -----------------------
.PHONY: chown
chown:
	sudo chown -R $(USER_NAME):$(USER_NAME) ./src
