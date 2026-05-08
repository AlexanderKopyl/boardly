import type { AuthGateway } from '../ports/auth-gateway'
import type { AuthSessionStore } from '../ports/auth-session-store'

export interface LogoutDependencies {
  gateway: AuthGateway
  store: AuthSessionStore
}

export async function logoutUseCase(deps: LogoutDependencies): Promise<void> {
  await deps.gateway.logout()
  deps.store.clear()
}
