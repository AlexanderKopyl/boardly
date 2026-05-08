import type { AuthSession } from '../../domain/auth-session'
import type { AuthSessionStore } from '../../application/ports/auth-session-store'

let session: AuthSession | null = null

export class AuthMemoryStore implements AuthSessionStore {
  save(nextSession: AuthSession): void {
    session = nextSession
  }

  get(): AuthSession | null {
    return session
  }

  clear(): void {
    session = null
  }
}
