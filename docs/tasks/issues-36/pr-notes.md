# Issue #36 PR Notes: Complete Authentication Flow

## Known Security Follow-Up

Login rate limiting is intentionally deferred from issue #36.

This PR does not fully satisfy ADR-0005's login-rate-limiting requirement. ADR-0005 and the authentication API strategy require login attempts to be rate-limited by IP plus normalized email, with an initial policy of 5 failed attempts per 5 minutes per IP+email.

This is a security-required follow-up before public production deployment.

Recommended follow-up scope:

```text
- Add LoginRateLimiterInterface application port.
- Rate limit by IP + normalized email.
- Use initial policy: 5 failed attempts per 5 minutes per IP+email.
- Add TooManyLoginAttempts exception.
- Map TooManyLoginAttempts to HTTP 429:
  {
    "error": {
      "code": "too_many_login_attempts",
      "message": "Too many login attempts. Please try again later."
    }
  }
- Keep the application layer independent from Redis and Symfony RateLimiter.
- Implement the infrastructure adapter with Symfony RateLimiter or Redis-backed storage.
- Add application and HTTP tests.
```

## Known Design Follow-Up

Login/session creation outbox events are intentionally deferred from issue #36.

Successful login creates and persists `RefreshSession` transactionally, but this PR does not publish or store an outbox event for login/session creation. Do not introduce `AccountAuthenticated`, `LoginSucceeded`, or `RefreshSessionCreated` in this PR.

ADR-0003 requires outbox storage for domain events that need reliable async side effects. Issue #36 does not define authentication audit, security, integration consumers, or an event schema for login/session creation, so adding this event now would expand the authentication baseline scope.

If audit, security, or integration visibility is required, a follow-up must explicitly define:

```text
- event name and owner
- payload and PII policy
- outbox serializer/mapper
- consumer/idempotency strategy
- retention/monitoring expectations
```

This is a known design follow-up, not a current blocker for issue #36.

## Logout / Session Revocation Audit Follow-Up

Logout and refresh-session revocation audit/outbox events are intentionally deferred from issue #36.

This PR revokes the current `RefreshSession` synchronously in the logout application transaction, but it does not introduce `RefreshSessionRevoked`, `LogoutSucceeded`, auth/session audit events, or outbox storage for logout/session revocation.

Authentication and session audit events require a separate explicit design before implementation. That design must cover:

```text
- event name and owner
- payload and PII policy
- outbox serializer/mapper
- consumers and idempotency strategy
- retention expectations
- monitoring expectations
```

This is a known design follow-up, not a current blocker for issue #36.
