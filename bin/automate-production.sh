#!/usr/bin/env bash

# Prevent execution errors from cascading silently
set -Eeuo pipefail

# ANSI color codes for premium terminal UI
NC='\033[0m'
BOLD='\033[1m'
GREEN='\033[0;32m'
BG_GREEN='\033[42;1;37m'
BLUE='\033[0;34m'
YELLOW='\033[0;33m'
RED='\033[0;31m'
BG_RED='\033[41;1;37m'
CYAN='\033[0;36m'

echo -e "${BOLD}${CYAN}====================================================${NC}"
echo -e "${BOLD}${CYAN}   🚀 DYXI PRODUCTION DEPLOYMENT & AUTOMATION      ${NC}"
echo -e "${BOLD}${CYAN}====================================================${NC}"

# Navigate to the project root directory (one level up from bin/)
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "${PROJECT_ROOT}"
echo -e "📂 Working Directory: ${BOLD}${BLUE}${PROJECT_ROOT}${NC}\n"

# Step 1: Detect PHP binary
echo -e "${BOLD}[1/5] Detecting PHP Environment...${NC}"
if command -v php &>/dev/null; then
    PHP_BIN="php"
    echo -e "  ✔ Using global PHP: ${BOLD}$(${PHP_BIN} -v | head -n 1)${NC}"
else
    echo -e "  ❌ ${RED}Error: PHP binary ('php') not found globally in PATH.${NC}" >&2
    exit 1
fi

# Step 2: Detect Composer
echo -e "\n${BOLD}[2/5] Detecting Composer...${NC}"
if command -v composer &>/dev/null; then
    COMPOSER_BIN="composer"
    echo -e "  ✔ Using global Composer: ${BOLD}$(${COMPOSER_BIN} --version | head -n 1)${NC}"
else
    echo -e "  ❌ ${RED}Error: Composer binary ('composer') not found globally in PATH.${NC}" >&2
    exit 1
fi

# Step 3: Run Composer Update
echo -e "\n${BOLD}[3/5] Updating Composer Dependencies...${NC}"
echo -e "  ⏳ Running composer update (optimizing autoloader)..."
if ${COMPOSER_BIN} update --no-interaction --prefer-dist --optimize-autoloader; then
    echo -e "  ✔ ${GREEN}Dependencies updated successfully!${NC}"
else
    echo -e "  ❌ ${RED}Composer update failed. Check logs.${NC}" >&2
    exit 1
fi

# Step 4: Run Database Migrations or Schema Updates
echo -e "\n${BOLD}[4/5] Running Database Updates...${NC}"
# Determine if we run migration:migrate or schema-tool:update
DOCTRINE_BIN="vendor/bin/doctrine-module"

if [ ! -f "${DOCTRINE_BIN}" ]; then
    echo -e "  ❌ ${RED}Error: ${DOCTRINE_BIN} not found. Ensure doctrine-orm-module is installed.${NC}" >&2
    exit 1
fi

# Check if migrations:migrate is an available command
if "${PHP_BIN}" "${DOCTRINE_BIN}" list | grep -q "migrations:migrate"; then
    echo -e "  🚀 Running migrations:migrate..."
    if "${PHP_BIN}" "${DOCTRINE_BIN}" migrations:migrate --no-interaction; then
        echo -e "  ✔ ${GREEN}Migrations executed successfully!${NC}"
    else
        echo -e "  ❌ ${RED}Database migration failed!${NC}" >&2
        exit 1
    fi
else
    echo -e "  ⚠️  ${YELLOW}Doctrine migrations module not registered. Executing schema-tool:update instead...${NC}"
    echo -e "  🚀 Running schema-tool:update --force --complete..."
    if "${PHP_BIN}" "${DOCTRINE_BIN}" orm:schema-tool:update --force --complete; then
        echo -e "  ✔ ${GREEN}Schema updated successfully!${NC}"
    else
        echo -e "  ❌ ${RED}Database schema update failed!${NC}" >&2
        exit 1
    fi
fi

# Step 5: Cache Clearing & Rebuilding
echo -e "\n${BOLD}[5/5] Clearing & Rebuilding Caches...${NC}"

# Clear previous Laminas configuration cache
if [ -f "bin/clear-config-cache.php" ]; then
    echo -e "  🧹 Clearing previous Laminas config cache..."
    "${PHP_BIN}" bin/clear-config-cache.php || echo -e "  ⚠️  ${YELLOW}No previous configuration cache to clear.${NC}"
fi

# Clean old runtime cache files in data/cache
echo -e "  🧹 Cleaning data/cache directory..."
find data/cache -type f -not -name '.gitkeep' -delete

# Repackage / build new configuration cache
if [ -f "bin/build-config-cache.php" ]; then
    echo -e "  ⚙️  Building and packaging new configuration cache..."
    if "${PHP_BIN}" bin/build-config-cache.php; then
        echo -e "  ✔ ${GREEN}Configuration cache repackaged successfully!${NC}"
    else
        echo -e "  ⚠️  ${YELLOW}Could not build configuration cache file.${NC}"
    fi
fi

# Clear Doctrine metadata/query/result caches
echo -e "  🧹 Clearing Doctrine caches..."
"${PHP_BIN}" "${DOCTRINE_BIN}" orm:clear-cache:metadata
"${PHP_BIN}" "${DOCTRINE_BIN}" orm:clear-cache:query
"${PHP_BIN}" "${DOCTRINE_BIN}" orm:clear-cache:result

# Generate Doctrine proxy classes
echo -e "  ⚙️  Generating Doctrine proxy classes..."
if "${PHP_BIN}" "${DOCTRINE_BIN}" orm:generate-proxies; then
    echo -e "  ✔ ${GREEN}Proxies generated successfully!${NC}"
else
    echo -e "  ❌ ${RED}Failed to generate Doctrine proxies!${NC}" >&2
    exit 1
fi


echo -e "\n${BOLD}${BG_GREEN}  🎉 DEPLOYMENT COMPLETE!  ${NC}\n"
