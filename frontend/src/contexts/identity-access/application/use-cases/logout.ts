import type { AuthGateway } from '../ports/auth-gateway'
import type { AuthSessionStore } from '../ports/auth-session-store'

export interface LogoutDependencies {
  gateway: AuthGateway
  store: AuthSessionStore
}

export async function logoutUseCase(deps: LogoutDependencies): Promise<void> {
  try {
    await deps.gateway.logout()
  } catch {
    // Logout is local-session-ending even when the best-effort backend call fails.
  } finally {
    deps.store.clear()
  }
}
