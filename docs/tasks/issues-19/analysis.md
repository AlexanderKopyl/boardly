# Issue 19 Analysis: Evaluate Symfony 8 Upgrade

## Issue Goal

Evaluate whether Boardly can be safely upgraded from Symfony 7 to Symfony 8 without performing the upgrade in this step.

This analysis is limited to Composer dependency state, Symfony configuration, installed platform requirements, and the current Messenger bus configuration.

## Current Dependency State

The project is currently pinned to Symfony 7 through both root package constraints and Symfony Flex.

Root `require` Symfony packages constrained to `^7.0`:

- `symfony/amqp-messenger`
- `symfony/cache`
- `symfony/console`
- `symfony/doctrine-messenger`
- `symfony/dotenv`
- `symfony/framework-bundle`
- `symfony/runtime`
- `symfony/security-bundle`
- `symfony/serializer`
- `symfony/uid`
- `symfony/validator`
- `symfony/yaml`

Root `require` Symfony packages not constrained to `^7.0`:

- `symfony/flex`: `^2.4`
- `symfony/monolog-bundle`: `^3.0`

Root `require-dev` Symfony packages constrained to `^7.0`:

- `symfony/browser-kit`
- `symfony/css-selector`
- `symfony/phpunit-bridge`

Root `require-dev` Symfony packages not constrained to `^7.0`:

- `symfony/maker-bundle`: `^1.60`

Current Symfony Flex constraint:

```text
extra.symfony.require ^7.0
```

Installed relevant package versions from `composer.lock`:

- `symfony/framework-bundle`: `v7.4.9`
- `symfony/console`: `v7.4.9`
- `symfony/security-bundle`: `v7.4.8`
- `symfony/serializer`: `v7.4.8`
- `symfony/validator`: `v7.4.9`
- `symfony/messenger`: `v7.4.9`
- `symfony/runtime`: `v7.4.8`
- `symfony/amqp-messenger`: `v7.4.9`
- `symfony/doctrine-messenger`: `v7.4.6`
- `symfony/monolog-bundle`: `v3.11.2`
- `doctrine/doctrine-bundle`: `2.18.2`
- `doctrine/dbal`: `4.4.3`
- `doctrine/orm`: `3.6.3`

## PHP and Runtime Compatibility

Local PHP:

```text
PHP 8.5.3 (cli) (built: Feb 10 2026 18:25:51) (NTS)
with Xdebug v3.5.0
with Zend OPcache v8.5.3
```

Project PHP constraint:

```text
php: ^8.3
```

Symfony 8 package metadata checked through Composer shows PHP `>=8.4` for the evaluated Symfony 8 packages:

- `symfony/framework-bundle 8.0.0`: `php >=8.4`
- `symfony/console 8.0.0`: `php >=8.4`
- `symfony/security-bundle 8.0.0`: `php >=8.4`
- `symfony/serializer 8.0.0`: `php >=8.4`
- `symfony/validator 8.0.0`: `php >=8.4`
- `symfony/messenger 8.0.0`: `php >=8.4`
- `symfony/runtime 8.0.0`: `php >=8.4`

The local CLI runtime is compatible because PHP 8.5.3 satisfies Symfony 8's PHP requirement. The project Composer constraint `^8.3` also allows PHP 8.4 and 8.5, but deployment/runtime environments must be confirmed to run PHP 8.4 or newer before the upgrade.

`composer check-platform-reqs` result:

```text
composer-plugin-api  2.9.0      success
composer-runtime-api 2.2.2      success
ext-amqp             2.2.0      success
ext-ctype            *          success provided by symfony/polyfill-ctype
ext-dom              20031129   success
ext-filter           8.5.3      success
ext-hash             8.5.3      success
ext-iconv            8.5.3      success
ext-json             8.5.3      success
ext-libxml           8.5.3      success
ext-mbstring         *          success provided by symfony/polyfill-mbstring
ext-phar             8.5.3      success
ext-tokenizer        8.5.3      success
ext-xml              8.5.3      success
ext-xmlwriter        8.5.3      success
php                  8.5.3      success
```

## Composer Validation

`composer validate --strict` exits with code 2 because publish metadata is missing:

```text
./composer.json is valid for simple usage with Composer but has
strict errors that make it unable to be published as a package
# Publish errors
- name : The property name is required
- description : The property description is required
```

This is not a Symfony 8 dependency blocker, but it is a validation failure that should be noted in the PR.

## Composer Why-Not Results

`composer why-not symfony/framework-bundle 8.0`:

```text
__root__                 dev-main requires symfony/framework-bundle (^7.0)
doctrine/doctrine-bundle 2.18.2   requires symfony/framework-bundle (^6.4 || ^7.0)
```

`composer why-not symfony/console 8.0`:

```text
__root__                 dev-main requires symfony/console (^7.0)
doctrine/doctrine-bundle 2.18.2   requires symfony/console (^6.4 || ^7.0)
```

`composer why-not symfony/security-bundle 8.0`:

```text
__root__ dev-main requires symfony/security-bundle (^7.0)
```

`composer why-not symfony/serializer 8.0`:

```text
__root__          dev-main requires  symfony/serializer (^7.0)
symfony/messenger v7.4.9   conflicts symfony/serializer (<6.4.32|>=7.3,<7.3.10|>=7.4,<7.4.4|>=8.0,<8.0.4)
```

The Messenger conflict is version-specific: Symfony Messenger 7.4.9 rejects early Serializer 8.0 versions below 8.0.4. A coordinated Symfony update should avoid mixing Messenger 7.4.9 with Serializer 8.0.0.

`composer why-not symfony/validator 8.0`:

```text
__root__ dev-main requires symfony/validator (^7.0)
```

`composer why-not symfony/messenger 8.0`:

```text
There is no installed package depending on "symfony/messenger" in versions not matching 8.0
```

`composer why-not symfony/runtime 8.0`:

```text
__root__ dev-main requires symfony/runtime (^7.0)
```

## Dry-Run Result

Command:

```text
composer update "symfony/*" --dry-run
```

Result:

```text
Restricting packages listed in "symfony/symfony" to "^7.0"
Updating dependencies
Nothing to modify in lock file
Installing dependencies from lock file (including require-dev)
Nothing to install, update or remove
No security vulnerability advisories found.
```

The dry-run did not evaluate a Symfony 8 upgrade because `extra.symfony.require` still restricts Symfony packages to `^7.0`. It did not modify `composer.lock`.

## Symfony Package List to Upgrade

When compatibility blockers are addressed, the upgrade should include these root Symfony constraints:

- `symfony/amqp-messenger`
- `symfony/cache`
- `symfony/console`
- `symfony/doctrine-messenger`
- `symfony/dotenv`
- `symfony/framework-bundle`
- `symfony/runtime`
- `symfony/security-bundle`
- `symfony/serializer`
- `symfony/uid`
- `symfony/validator`
- `symfony/yaml`
- `symfony/browser-kit`
- `symfony/css-selector`
- `symfony/phpunit-bridge`
- `extra.symfony.require`

Review, but do not blindly change unless Composer requires it:

- `symfony/flex`
- `symfony/monolog-bundle`
- `symfony/maker-bundle`

## Blockers

The upgrade is blocked in the current dependency state.

Confirmed blockers:

- Root package constraints pin Symfony packages to `^7.0`.
- `extra.symfony.require` is `^7.0`, so Flex restricts Symfony updates to Symfony 7.
- `doctrine/doctrine-bundle 2.18.2` requires `symfony/framework-bundle ^6.4 || ^7.0`.
- `doctrine/doctrine-bundle 2.18.2` requires `symfony/console ^6.4 || ^7.0`.

Additional package risk:

- `symfony/monolog-bundle v3.11.2` currently requires Symfony components `^6.4 || ^7.0`, including `symfony/http-kernel`, `symfony/config`, `symfony/dependency-injection`, and `symfony/monolog-bridge`.

## Risks

- Symfony 8 requires PHP `>=8.4`; local PHP is compatible, but all deployment/runtime environments must be confirmed.
- DoctrineBundle is a concrete Composer blocker for FrameworkBundle and Console 8. The implementation step must identify an available DoctrineBundle version compatible with Symfony 8 or stop.
- MonologBundle may block transitive Symfony 8 components unless a compatible version is available under the existing or adjusted constraint.
- The dry-run did not test Symfony 8 resolution because current Flex constraints intentionally restricted the update to Symfony 7.
- Symfony 8 may expose removed Symfony 7 deprecations in code, service definitions, security behavior, Messenger middleware behavior, or tests; that requires verification after dependency resolution.
- `composer validate --strict` currently fails on missing publish metadata, which may be unrelated to runtime behavior but affects strict validation.

## Config Compatibility Review

Reviewed files:

- `config/bundles.php`
- `config/packages/cache.yaml`
- `config/packages/doctrine.yaml`
- `config/packages/doctrine_migrations.yaml`
- `config/packages/framework.yaml`
- `config/packages/messenger.yaml`
- `config/packages/monolog.yaml`
- `config/packages/property_info.yaml`
- `config/packages/routing.yaml`
- `config/packages/security.yaml`
- `config/packages/validator.yaml`
- `config/routes.yaml`
- `config/services.yaml`
- `docs/architecture/application-bus-rules.md`

No obvious Symfony 7-only configuration was found in the reviewed YAML files.

Specific notes:

- FrameworkBundle config uses common settings: `secret`, `http_method_override: false`, `handle_all_throwables: true`, session config, and PHP error logging.
- Security config uses `password_hashers`, an in-memory provider, and a lazy main firewall. This is simple but must still be verified with `debug:config security` and container compilation after the upgrade.
- Doctrine config uses DBAL 4 and ORM 3 oriented settings. DoctrineBundle compatibility is the main risk, not the YAML shape itself.
- Validator config only disables `not_compromised_password` in test.
- Router config uses `default_uri` and prod `strict_requirements: null`.
- Messenger config defines explicit buses and no broad async routing.

## Messenger Bus Compatibility

Current Messenger config:

```yaml
framework:
    messenger:
        default_bus: command.bus
        failure_transport: failed
        buses:
            command.bus: ~
            query.bus: ~
            event.bus:
                default_middleware:
                    allow_no_handlers: true
        transports:
            async:
                dsn: '%env(MESSENGER_TRANSPORT_DSN)%'
            failed:
                dsn: 'doctrine://default?queue_name=failed'
        routing: { }
```

This remains aligned with `docs/architecture/application-bus-rules.md` because commands and queries stay synchronous unless routing is explicitly added. The empty routing map avoids accidentally sending application commands to RabbitMQ.

Upgrade verification should still run container checks and inspect `debug:config framework messenger` after Symfony 8 dependency resolution.

## Tests and Static Analysis Impact

Tests and static analysis are likely to need at least verification, and possibly small compatibility fixes, after dependency resolution.

Expected checks:

- PHPUnit test suite.
- PHPStan analysis.
- Symfony container lint/compile checks.
- Router inspection.
- Security and Messenger config inspection.

Potential impact areas:

- Removed Symfony 7 deprecations surfaced by Symfony 8.
- Test helpers from `symfony/phpunit-bridge`, `symfony/browser-kit`, and `symfony/css-selector`.
- Service autowiring or handler registration behavior around Messenger.
- Security firewall defaults and user-provider behavior.

## Non-Goals

- Do not upgrade Symfony packages in this step.
- Do not modify `composer.json`, `composer.lock`, or `symfony.lock`.
- Do not change PHP code, config, tests, Doctrine mappings, or migrations.
- Do not introduce authentication, JWT, refresh-session, firewall, API DTO, or controller implementation.
- Do not resolve application architecture questions unrelated to Symfony 8 compatibility.

## Recommendation

Recommendation: blocked for immediate upgrade from the current dependency state.

Proceed only after an implementation step updates constraints intentionally and confirms Composer can resolve Symfony 8 with compatible versions of DoctrineBundle and MonologBundle. If DoctrineBundle or another package has no Symfony 8 compatible version available under acceptable constraints, stop and document that exact package and constraint instead of partially upgrading.
