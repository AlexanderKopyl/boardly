# Issue 49 Bugfix Note: refresh_token SameSite

The auth cookie flow needs cross-site browser support between `http://localhost:3000` and `https://broadly.com.ua`.

This bugfix changes every `refresh_token` `Set-Cookie` and clear-cookie response in the IdentityAccess HTTP auth flow to:

- `HttpOnly: true`
- `Secure: true`
- `SameSite: None`
- `Path: /api/auth`
- `Domain: null` / omitted

Scope is limited to cookie creation, cookie clearing, related controller tests, and stale OpenAPI text that still referred to `SameSite=Lax`.
