import type { AuthSession } from '../../domain/auth-session'

export interface AuthSessionStore {
  save(session: AuthSession): void
  get(): AuthSession | null
  clear(): void
}
