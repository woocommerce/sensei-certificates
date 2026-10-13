#!/usr/bin/env bash
set -euo pipefail

: "${GITHUB_WORKSPACE:?GITHUB_WORKSPACE must point to the checked-out repositories}"

export MYSQL_PWD=root
mysql --host=127.0.0.1 --user=root -e 'CREATE DATABASE sensei_certificates_tests;'

cd "$GITHUB_WORKSPACE/wordpress-develop"
cp wp-tests-config-sample.php wp-tests-config.php
sed -i \
    -e 's/youremptytestdbnamehere/sensei_certificates_tests/' \
    -e 's/yourusernamehere/root/' \
    -e 's/yourpasswordhere/root/' \
    -e "s/'localhost'/'127.0.0.1'/" \
    wp-tests-config.php

cd "$GITHUB_WORKSPACE/sensei-lms"
# Generate Sensei's prefixed libraries before removing its development tooling.
composer install --prefer-dist --no-interaction --no-progress
composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader --no-interaction --no-progress

cd "$GITHUB_WORKSPACE/sensei-certificates"
composer install --prefer-dist --no-interaction --no-progress
