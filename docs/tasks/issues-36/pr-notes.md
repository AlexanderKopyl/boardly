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
