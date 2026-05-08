import type { AuthSession } from '../../domain/auth-session'
import type { AuthGateway } from '../ports/auth-gateway'
import type { AuthSessionStore } from '../ports/auth-session-store'

export interface RefreshSessionDependencies {
  gateway: AuthGateway
  store: AuthSessionStore
  now?: () => Date
}

export async function refreshSessionUseCase(
  deps: RefreshSessionDependencies,
): Promise<AuthSession> {
  const now = deps.now ?? (() => new Date())

  const refreshResult = await deps.gateway.refreshSession()
  const account = await deps.gateway.getMe(refreshResult.accessToken)

  const session: AuthSession = {
    accessToken: refreshResult.accessToken,
    expiresAt: new Date(now().getTime() + refreshResult.expiresIn * 1000),
    account,
  }

  deps.store.save(session)
  return session
}
