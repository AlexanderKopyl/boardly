# Issue 19 Plan: Symfony 8 Upgrade

## Scope

This plan describes the future implementation steps for upgrading Boardly from Symfony 7 to Symfony 8. It does not perform the upgrade.

## Rollback and Stop Rules

- If Symfony 8 is blocked, do not partially upgrade `composer.json`.
- If PHP is too low in any target runtime, stop and document the required PHP/runtime upgrade to PHP 8.4 or newer.
- If a package blocks the upgrade, list the exact package and constraint from Composer output.
- If the dry-run fails, do not update the lock file.
- If Composer modifies files during analysis or a failed attempt, revert those Composer changes before continuing.
- Do not keep a mixed Symfony 7/Symfony 8 dependency set unless Composer explicitly resolves it as a valid temporary state and the PR scope accepts it.

## Step 1: Update Composer Constraints Only If Compatible

Before editing constraints, confirm target environments run PHP 8.4 or newer.

If compatible, update only the Symfony-related Composer constraints needed for resolution:

- Change root Symfony component constraints from `^7.0` to the selected Symfony 8 constraint.
- Change `require-dev` Symfony component constraints from `^7.0` to the selected Symfony 8 constraint.
- Change `extra.symfony.require` from `^7.0` to the selected Symfony 8 constraint.
- Review `symfony/monolog-bundle`, `symfony/maker-bundle`, and `symfony/flex`; change them only if Composer requires it.
- Review `doctrine/doctrine-bundle`; if the installed or allowed version does not support Symfony 8, identify the compatible version or stop.

Do not update `composer.lock` in this step.

## Step 2: Run Composer Update With Dependencies

Run:

```bash
composer update "symfony/*" --with-all-dependencies
```

If DoctrineBundle, MonologBundle, or another package blocks the update, stop and document the exact Composer error. Do not hand-edit the lock file.

If needed and justified by Composer output, expand the update command to include the exact blocking package, for example:

```bash
composer update "symfony/*" doctrine/doctrine-bundle symfony/monolog-bundle --with-all-dependencies
```

Only expand the package list based on evidence from Composer resolution.

## Step 3: Review Lock File Changes

Review:

- `composer.lock`
- `symfony.lock`

Confirm:

- Symfony packages moved to Symfony 8 where expected.
- No unrelated package families changed without a dependency reason.
- DoctrineBundle and MonologBundle are on Symfony 8 compatible versions.
- PHP platform requirements remain compatible with target runtime.
- No package was downgraded unexpectedly.

## Step 4: Inspect Flex Recipe and Config Changes

Inspect any changes to:

- `config/bundles.php`
- `config/packages/*.yaml`
- `config/routes.yaml`
- `config/services.yaml`
- `symfony.lock`

Accept only recipe/config changes required for Symfony 8 compatibility. Keep existing Boardly architecture and Messenger bus rules intact.

## Step 5: Run Container and Router Checks

Run Symfony checks after dependency resolution:

```bash
php bin/console lint:container
php bin/console debug:container --env=test
php bin/console debug:router
php bin/console debug:config framework messenger
php bin/console debug:config security
```

Verify:

- Container compiles.
- Routes still load from configured attribute route paths.
- Messenger has `command.bus`, `query.bus`, and `event.bus`.
- Messenger routing does not accidentally make application commands asynchronous.
- Security config still compiles with the current minimal firewall setup.

## Step 6: Run Tests and Static Analysis

Run:

```bash
composer test
composer phpstan
```

If available in the branch workflow, also run:

```bash
composer qa
```

Record failures before fixing them. Separate dependency-resolution failures from application compatibility failures.

## Step 7: Fix Only Symfony 8 Compatibility Issues

Fix only issues directly caused by Symfony 8 compatibility.

Allowed fix categories:

- Removed Symfony APIs or changed method signatures.
- Service definition or autowiring changes required by Symfony 8.
- Messenger configuration or middleware changes required by Symfony 8.
- Security configuration changes required by Symfony 8.
- Test helper changes required by Symfony 8 packages.

Do not use the upgrade PR to introduce unrelated features, architecture changes, authentication implementation, Doctrine mapping redesigns, migrations, or formatting-only refactors.

## Step 8: Final Verification and PR Summary

Run final verification:

```bash
composer validate --strict
composer check-platform-reqs
composer test
composer phpstan
php bin/console lint:container
php bin/console debug:router
```

PR summary should include:

- Final PHP requirement.
- Symfony packages upgraded.
- DoctrineBundle/MonologBundle compatibility outcome.
- Any Flex recipe/config changes.
- Tests and static analysis results.
- Any remaining risks or follow-up tasks.

If `composer validate --strict` still fails only because project metadata lacks `name` and `description`, document that separately from Symfony 8 compatibility.
