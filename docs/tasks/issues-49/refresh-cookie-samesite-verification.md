# Verification: refresh_token SameSite fix

## Changed area

IdentityAccess auth HTTP cookie creation and clearing for `refresh_token`, plus related controller/helper tests and OpenAPI descriptions.

## Commands run

| Command | Result | Notes |
| --- | --- | --- |
| `rg -n "SameSite=Lax|SAMESITE_LAX|samesite=lax" src/Boardly/IdentityAccess/Interfaces/Http tests/Boardly/IdentityAccess/Interfaces/Http` | Passed | No remaining `SameSite=Lax` mentions in the scoped auth HTTP files/tests. |
| `php ./vendor/bin/phpunit --filter testSuccessfulLoginSetsRefreshCookie tests/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/LoginControllerTest.php` | Passed | 1 test, 17 assertions. |
| `php ./vendor/bin/phpunit --filter testValidCookieSetsNewRefreshCookieWithExpectedAttributes tests/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RefreshAuthenticationControllerTest.php` | Passed | 1 test, 20 assertions. |
| `php ./vendor/bin/phpunit --filter testClearCookieUsesHttpOnlySecureAndSameSiteLax tests/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/LogoutControllerTest.php` | Passed | 1 test, 8 assertions. |
| `php ./vendor/bin/phpunit tests/Boardly/IdentityAccess/Interfaces/Http/Exception/RefreshTokenExceptionMapperTest.php` | Passed | 1 test, 10 assertions. |
| `php ./vendor/bin/phpunit tests/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth tests/Boardly/IdentityAccess/Interfaces/Http/Exception/RefreshTokenExceptionMapperTest.php` | Failed | One unrelated failure remained in `RefreshAuthenticationControllerTest::testReplacedTokenReuseReturns401ClearsCookieAndRevokesFamily`. |
| `php /opt/homebrew/bin/composer test` | Failed | Same unrelated auth test failure as above. |
| `php /opt/homebrew/bin/composer phpstan` | Failed | Existing PHPStan error in `tests/Boardly/IdentityAccess/Interfaces/Http/Exception/IdentityAccessApiExceptionSubscriberTest.php:37` (`nullsafe.neverNull`). |
| `/opt/homebrew/bin/git diff --check` | Passed | No patch formatting issues. |

## Manual checks

- Pending browser verification: login should set `refresh_token=<opaque>; HttpOnly; Secure; SameSite=None; Path=/api/auth`.
- Pending browser verification: `POST https://broadly.com.ua/api/auth/refresh` with `credentials: include` and `X-CSRF-Intent: auth-refresh` should send the `refresh_token` cookie.

## Failures

- None so far.

## Not run

| Command | Reason |
| --- | --- |
| Narrow auth PHPUnit tests | Not started yet at the time this verification note was written. |
| Broader backend checks | Deferred until the narrow auth tests pass. |

## Final verification status

PARTIALLY_VERIFIED

## Remaining risks

- Browser cookie behavior still needs an end-to-end manual confirmation against the real frontend/API split.
- One unrelated auth test still fails in the broader suite.
- PHPStan still reports one unrelated pre-existing error in an auth exception-subscriber test.
