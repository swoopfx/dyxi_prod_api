#!/usr/bin/env bash

# Prevent execution errors from cascading silently
set -Eeuo pipefail

# ANSI color codes
NC='\033[0m'
BOLD='\033[1m'
GREEN='\033[0;32m'
BG_GREEN='\033[42;1;37m'
YELLOW='\033[0;33m'
RED='\033[0;31m'
CYAN='\033[0;36m'

echo -e "${BOLD}${CYAN}====================================================${NC}"
echo -e "${BOLD}${CYAN}   🔥 DYXI DATABASE DROP & RESET TOOL              ${NC}"
echo -e "${BOLD}${CYAN}====================================================${NC}"

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${PROJECT_ROOT}"

PHP_BIN="php"
DOCTRINE_BIN="vendor/bin/doctrine-module"

# Confirm action unless --force is passed
FORCE=false
for arg in "$@"; do
    if [ "$arg" == "--force" ] || [ "$arg" == "-f" ]; then
        FORCE=true
    fi
done

if [ "$FORCE" = false ]; then
    echo -e "${YELLOW}⚠️  WARNING: This will DROP ALL TABLES and RESET your database!${NC}\n"
    echo -e "To proceed, run with --force flag:"
    echo -e "  ${BOLD}./bin/reset-database.sh --force${NC} or ${BOLD}php bin/reset_database.php --force${NC}\n"
    exit 0
fi

echo -e "\n${BOLD}[1/4] Dropping database schema...${NC}"
"${PHP_BIN}" "${DOCTRINE_BIN}" orm:schema-tool:drop --force --full-database || true
echo -e "  ✔ Schema dropped."

echo -e "\n${BOLD}[2/4] Creating fresh database schema...${NC}"
"${PHP_BIN}" "${DOCTRINE_BIN}" orm:schema-tool:create
echo -e "  ✔ Schema created."

echo -e "\n${BOLD}[3/4] Hydrating preset data...${NC}"
"${PHP_BIN}" bin/hydrate_preset_data.php

echo -e "\n${BOLD}[4/5] Setting up default users and sample ward...${NC}"
"${PHP_BIN}" bin/setup_users.php
"${PHP_BIN}" bin/create_ward.php

echo -e "\n${BOLD}[5/5] Granting user access authorization in Database & Redis...${NC}"
"${PHP_BIN}" bin/authorize_user.php --email=swoopfx@gmail.com --role=1000

echo -e "\n${BOLD}Clearing & rebuilding caches...${NC}"
"${PHP_BIN}" bin/clear-config-cache.php || true
"${PHP_BIN}" "${DOCTRINE_BIN}" orm:clear-cache:metadata
"${PHP_BIN}" "${DOCTRINE_BIN}" orm:clear-cache:query
"${PHP_BIN}" "${DOCTRINE_BIN}" orm:clear-cache:result
"${PHP_BIN}" "${DOCTRINE_BIN}" orm:generate-proxies

echo -e "\n${BOLD}${BG_GREEN}  🎉 DATABASE RESET COMPLETE!  ${NC}\n"
