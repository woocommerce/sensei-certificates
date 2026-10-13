# PHP integration tests

The PHPUnit setup follows Sensei Pro's extension test layout. Tests load the real
Sensei LMS and Certificates plugins into the WordPress test framework.

## Local environment

Use Node from `.nvmrc`, Docker, PHP 7.4 or newer, and Composer:

```sh
nvm use
npm ci
composer install
```

A sibling `../sensei` checkout with its Composer dependencies installed is required.
The WordPress development and test environments run in Docker, independently of
your existing local WordPress site. They use ports 8884 and 8885. The default
configuration tests the minimum supported WordPress 6.9 and PHP 7.4 versions.

```sh
make test-php
make test-php-filter FILTER='WooThemes_Sensei_Certificates_Bootstrap_Test'
make down
```

`make test-php` starts wp-env before running the suite. Like Sensei Pro, the
command passes `SENSEI_PLUGIN_DIR` into `tests-cli`; wp-env provides `WP_TESTS_DIR`
and a separate test database. PHPUnit recreates tables in that test database.

To test another configuration, create a git-ignored `.wp-env.override.json`, for
example:

```json
{
  "core": null,
  "phpVersion": "8.3"
}
```

Then run `npx wp-env start --update` before `make test-php`. Remove the override
and run `npx wp-env start --update` to return to the default configuration.

## Existing WordPress test environment

The same suite can also run without Docker when WordPress's PHPUnit library and
a dedicated, disposable test database are already configured:

```sh
WP_TESTS_DIR=/path/to/wordpress-develop/tests/phpunit \
  SENSEI_PLUGIN_DIR=/path/to/sensei composer test
```

Never point WordPress's test configuration at a development or production database.

## Continuous integration

`.github/workflows/php-test.yml` runs the suite against the minimum WordPress/PHP
versions with the latest stable Sensei, and the latest stable WordPress with PHP
8.3 and Sensei trunk. Its preparation scripts use separate WordPress and Sensei
checkouts, following Sensei Pro's CI pattern without its WooCommerce dependencies.
