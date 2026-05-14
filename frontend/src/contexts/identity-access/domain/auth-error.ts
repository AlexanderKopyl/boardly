export type AuthErrorCode =
  | 'invalid_credentials'
  | 'account_not_active'
  | 'too_many_login_attempts'
  | 'invalid_refresh_token'
  | 'unauthenticated'

export class AuthError extends Error {
  readonly code: AuthErrorCode

  constructor(code: AuthErrorCode, message?: string) {
    super(message ?? code)
    this.name = 'AuthError'
    this.code = code
  }
}
