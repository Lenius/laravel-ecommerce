#!/usr/bin/env bash

set -Eeuo pipefail

RED='\033[1;31m'
YELLOW='\033[1;33m'
GREEN='\033[1;32m'
NC='\033[0m'

PROJECT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
PHP_VERSION="${PHP_VERSION:-8.5}"

if [[ "${1:-}" == "--php" ]]; then
    if [[ -z "${2:-}" ]]; then
        echo "Missing version after --php" >&2
        exit 1
    fi

    PHP_VERSION="$2"
    shift 2
fi

IMAGE="laravel-ecommerce-dev:php-${PHP_VERSION}"
COMPOSER_CACHE_HOST="${COMPOSER_CACHE_HOST:-${HOME}/.cache/composer}"

function displayHelp() {
    local filename
    filename="$(basename "$0")"

    printf "${GREEN}Laravel Ecommerce container shell ${YELLOW}(PHP %s)${NC}\n" "$PHP_VERSION"
    echo "-------------------------------------------------------------------------------"
    echo -e "${YELLOW}Usage:${NC}"
    printf "  %s [--php version] [command] [arguments]\n" "$filename"
    echo ""
    echo -e "${YELLOW}Commands:${NC}"
    printf "  %-34s %s\n" "build [--force]" "Build the local PHP tool image"
    printf "  %-34s %s\n" "check" "Run Composer validation, tests and PHPStan"
    printf "  %-34s %s\n" "test [arguments]" "Run PHPUnit"
    printf "  %-34s %s\n" "stan [arguments]" "Run PHPStan"
    printf "  %-34s %s\n" "php-cs-fixer [arguments]" "Run PHP CS Fixer and fix code"
    printf "  %-34s %s\n" "composer-install [arguments]" "Run composer install"
    printf "  %-34s %s\n" "composer-update [arguments]" "Run composer update"
    printf "  %-34s %s\n" "composer-validate [arguments]" "Run composer validate --strict"
    printf "  %-34s %s\n" "composer-outdated [arguments]" "List outdated Composer dependencies"
    printf "  %-34s %s\n" "composer [arguments]" "Run any Composer command"
    printf "  %-34s %s\n" "php [arguments]" "Run any PHP command"
    printf "  %-34s %s\n" "php-shell" "Open an interactive shell"
    printf "  %-34s %s\n" "help" "Display this help screen"
    echo ""
    echo -e "${YELLOW}Examples:${NC}"
    printf "  %-34s %s\n" "./app test --filter CartTest" "Run one test class"
    printf "  %-34s %s\n" "./app stan" "Run static analysis"
    printf "  %-34s %s\n" "./app composer require vendor/package" "Run Composer directly"
    printf "  %-34s %s\n" "./app --php 8.4 test" "Run with another PHP image"
}

function buildImage() {
    local build_args=(
        --build-arg "PHP_VERSION=${PHP_VERSION}"
        --file "${PROJECT_DIR}/docker/Dockerfile"
        --tag "$IMAGE"
    )

    if [[ "${1:-}" == "--force" ]]; then
        build_args+=(--no-cache --pull)
    elif [[ -n "${1:-}" ]]; then
        echo "Unknown build option: $1" >&2
        exit 1
    fi

    echo -e "${GREEN}Building ${IMAGE}${NC}"
    docker build "${build_args[@]}" "${PROJECT_DIR}/docker"
}

function ensureImage() {
    if ! docker image inspect "$IMAGE" >/dev/null 2>&1; then
        buildImage
    fi
}

function runContainer() {
    local tty_args=()

    ensureImage
    mkdir -p "$COMPOSER_CACHE_HOST"

    if [[ -t 0 && -t 1 ]]; then
        tty_args=(-it)
    fi

    docker run "${tty_args[@]}" --rm \
        --user "$(id -u):$(id -g)" \
        --env HOME=/tmp \
        --env COMPOSER_HOME=/tmp/.composer \
        --env COMPOSER_CACHE_DIR=/.composer/cache \
        --volume "${PROJECT_DIR}:/app" \
        --volume "${COMPOSER_CACHE_HOST}:/.composer/cache" \
        --workdir /app \
        "$IMAGE" \
        "$@"
}

function ensureDependencies() {
    if [[ ! -f "${PROJECT_DIR}/vendor/autoload.php" ]]; then
        echo -e "${YELLOW}vendor/autoload.php is missing; running composer install first.${NC}"
        runContainer composer install --no-interaction --prefer-dist --no-progress
    fi
}

function runTests() {
    ensureDependencies
    runContainer php vendor/bin/phpunit "$@"
}

function runStan() {
    ensureDependencies
    runContainer php vendor/bin/phpstan analyse --no-progress --memory-limit=512M "$@"
}

function runCodeStyleFixer() {
    ensureDependencies
    runContainer php vendor/bin/php-cs-fixer fix --allow-risky=yes "$@"
}

function runChecks() {
    runContainer composer validate --strict
    runTests
    runStan
}

command="${1:-help}"
shift || true

case "$command" in
    build)
        buildImage "$@"
        ;;
    check)
        runChecks
        ;;
    test)
        runTests "$@"
        ;;
    stan)
        runStan "$@"
        ;;
    php-cs-fixer)
        runCodeStyleFixer "$@"
        ;;
    composer-install)
        runContainer composer install --no-interaction --prefer-dist "$@"
        ;;
    composer-update)
        runContainer composer update --with-all-dependencies "$@"
        ;;
    composer-validate)
        runContainer composer validate --strict "$@"
        ;;
    composer-outdated)
        runContainer composer outdated "$@"
        ;;
    composer)
        runContainer composer "$@"
        ;;
    php)
        runContainer php "$@"
        ;;
    php-shell)
        ensureImage
        mkdir -p "$COMPOSER_CACHE_HOST"
        docker run -it --rm \
            --user "$(id -u):$(id -g)" \
            --env HOME=/tmp \
            --env COMPOSER_HOME=/tmp/.composer \
            --env COMPOSER_CACHE_DIR=/.composer/cache \
            --volume "${PROJECT_DIR}:/app" \
            --volume "${COMPOSER_CACHE_HOST}:/.composer/cache" \
            --workdir /app \
            "$IMAGE" bash
        ;;
    help|-h|--help)
        displayHelp
        ;;
    *)
        echo -e "${RED}Unknown command: ${command}${NC}" >&2
        echo "" >&2
        displayHelp >&2
        exit 1
        ;;
esac
