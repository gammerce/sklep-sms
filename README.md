# [Sklep SMS](https://sklep-sms.pl) [![Build Status](https://img.shields.io/github/actions/workflow/status/gammerce/sklep-sms/ci-workflow.yml?branch=master)](https://github.com/gammerce/sklep-sms/actions?query=workflow%3A%22CI+workflow%22) [![Release](https://img.shields.io/github/v/release/gammerce/sklep-sms)](https://github.com/gammerce/sklep-sms/releases/latest) [![Coverage Status](https://coveralls.io/repos/github/gammerce/sklep-sms/badge.svg)](https://coveralls.io/github/gammerce/sklep-sms) [![License](https://img.shields.io/github/license/gammerce/sklep-sms)](https://github.com/gammerce/sklep-sms/blob/master/LICENSE) ![PHP 8.0](https://img.shields.io/badge/PHP-8.0-blue.svg) ![PHP 8.1](https://img.shields.io/badge/PHP-8.1-blue.svg) ![PHP 8.2](https://img.shields.io/badge/PHP-8.2-blue.svg) ![PHP 8.3](https://img.shields.io/badge/PHP-8.3-blue.svg) ![PHP 8.4](https://img.shields.io/badge/PHP-8.4-blue.svg) ![PHP 8.5](https://img.shields.io/badge/PHP-8.5-blue.svg) ![PHP 8.6](https://img.shields.io/badge/PHP-8.6-blue.svg)


Sklep SMS makes it easier to earn money on your game servers.

## They trusted us
Check who uses the Sklep SMS: https://sklep-sms.pl/places

## Live demo
Test Sklep SMS for free: https://demo.sklep-sms.cloud

## License
License can be purchased here: https://sklep.sklep-sms.pl

## Installation
How to install the Sklep SMS step by step: https://sklep-sms.pl/config

## Development

### Setup

```bash
# Start services
docker-compose up -d

# Install dependencies (uses composer-v8.0.json for PHP 8.0 compatibility)
docker-compose exec -T app bash -c "COMPOSER=composer-v8.0.json composer install --no-interaction --no-plugins"

# Install production dependencies only (no dev)
docker-compose exec -T app bash -c "COMPOSER=composer-v8.0.json composer install --no-dev --no-interaction --no-plugins"

# Set up test database
docker-compose exec -T app php artisan test:setup
```

### Running tests

```bash
# Run all tests
docker-compose exec -T app php vendor/bin/phpunit

# Run a specific test
docker-compose exec -T app php vendor/bin/phpunit --filter TestName
```

## Contact
* [#wsparcie](https://discord.gg/fz47ngSzGy) channel
* [Issues](https://github.com/gammerce/sklep-sms/issues) tab
* email seek@sklep-sms.pl
