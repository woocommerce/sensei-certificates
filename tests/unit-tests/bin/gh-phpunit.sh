#!/usr/bin/env bash
set -euo pipefail

: "${GITHUB_WORKSPACE:?GITHUB_WORKSPACE must point to the checked-out repositories}"

export WP_TESTS_DIR="$GITHUB_WORKSPACE/wordpress-develop/tests/phpunit"
export SENSEI_PLUGIN_DIR="$GITHUB_WORKSPACE/sensei-lms"

cd "$GITHUB_WORKSPACE/sensei-certificates"
exec ./vendor/bin/phpunit "$@"
