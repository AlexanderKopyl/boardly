## 2026-05-14 - Task: refresh_token SameSite fix

### Subagents used

- `worker` for narrow implementation ownership over the auth cookie files and tests.

### Skills used

- `task-implementation`
- `verification-evidence`

### Files changed

- `src/Boardly/IdentityAccess/Interfaces/Http/Cookie/RefreshTokenCookieResponseHelper.php`
- `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/LoginController.php`
- `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RefreshAuthenticationController.php`
- `src/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/LogoutController.php`
- `tests/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/LoginControllerTest.php`
- `tests/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/RefreshAuthenticationControllerTest.php`
- `tests/Boardly/IdentityAccess/Interfaces/Http/Controller/Auth/LogoutControllerTest.php`
- `tests/Boardly/IdentityAccess/Interfaces/Http/Exception/RefreshTokenExceptionMapperTest.php`
- `docs/tasks/issues-49/refresh-cookie-samesite-bugfix.md`
- `docs/tasks/issues-49/refresh-cookie-samesite-checklist.md`

### Summary

Changed every `refresh_token` cookie set/clear path in the IdentityAccess auth HTTP layer from `SameSite=Lax` to `SameSite=None` so the browser sends the HttpOnly refresh cookie on cross-site refresh/logout requests between the local frontend and the remote API.

### Verification

Pending test execution in `verification.md`.

### Risks / follow-up

- Cross-site cookie behavior still depends on the browser honoring `Secure` and CORS/credentials settings.
- No auth business logic, token rotation, or CSRF header behavior was changed.
