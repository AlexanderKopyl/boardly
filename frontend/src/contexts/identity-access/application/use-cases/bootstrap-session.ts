import { AuthError } from '../../domain/auth-error'
import type { AuthSession } from '../../domain/auth-session'
import type { AuthGateway } from '../ports/auth-gateway'
import type { AuthSessionStore } from '../ports/auth-session-store'

export interface BootstrapSessionDependencies {
  gateway: AuthGateway
  store: AuthSessionStore
  now?: () => Date
}

export async function bootstrapSessionUseCase(
  deps: BootstrapSessionDependencies,
): Promise<AuthSession | null> {
  const now = deps.now ?? (() => new Date())

  try {
    const refreshResult = await deps.gateway.refreshSession()
    const account = await deps.gateway.getCurrentAccount(refreshResult.accessToken)

    const session: AuthSession = {
      accessToken: refreshResult.accessToken,
      expiresAt: new Date(now().getTime() + refreshResult.expiresIn * 1000),
      account,
    }

    deps.store.save(session)
    return session
  } catch (error) {
    if (
      error instanceof AuthError &&
      (error.code === 'invalid_refresh_token' || error.code === 'unauthenticated')
    ) {
      deps.store.clear()
      return null
    }
    throw error
  }
}
