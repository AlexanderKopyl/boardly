# Implementation Checklist: refresh_token SameSite fix

- [x] Update refresh_token cookie creation and clearing to use `SameSite=None`
- [x] Update auth controller and helper tests to expect `SameSite=None`
- [x] Update stale OpenAPI descriptions and issue-folder note
