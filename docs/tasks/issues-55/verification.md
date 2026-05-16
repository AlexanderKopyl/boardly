# Verification: GitHub issue #55: Start core Product domain with Projects MVP

## Changed area

- Final validation for the Projects MVP task, specifically the checklist item:
  - `php bin/phpunit` passes for all new tests.

## Commands run

| Command | Result | Notes |
| --- | --- | --- |
| `/opt/homebrew/bin/php ./vendor/bin/phpunit tests/Boardly/Projects` | Passed | `OK (37 tests, 150 assertions)` |
| `/opt/homebrew/bin/php bin/phpunit` | Failed | `Tests: 467, Assertions: 1735, Errors: 73` |

## Manual checks

- Confirmed the new Projects test suite passes in isolation.
- Confirmed the full PHPUnit run fails outside the Projects suite.
- The failing errors are database cleanup/reuse failures in existing IdentityAccess tests, caused by the new `projects_projects_owner_account_fk` foreign key blocking deletion of accounts referenced by Projects rows.

## Failures

- Full-suite PHPUnit failure:
  - First failing test observed: `App\Tests\Boardly\IdentityAccess\Infrastructure\Persistence\Doctrine\DoctrineRefreshSessionRepositoryTest::testUnknownTokenHashReturnsNull`
  - Additional failing tests include the rest of `DoctrineRefreshSessionRepositoryTest` and `App\Tests\Boardly\IdentityAccess\Interfaces\Http\Security\ApiAuthenticationTest`
  - Root cause: `SQLSTATE[23001]: Restrict violation` on `projects_projects_owner_account_fk`

## Not run

| Command | Reason |
| --- | --- |
| `php bin/phpunit` via bare `php` | The shell environment here does not resolve `php` reliably, so the equivalent absolute binary path was used instead |

## Final verification status

PARTIALLY_VERIFIED

## Remaining risks

- The checklist item cannot be marked done yet because the broad PHPUnit suite does not pass.
- The new `projects` foreign key appears to interfere with existing IdentityAccess test cleanup and needs isolation/fix before the final validation can be accepted.
