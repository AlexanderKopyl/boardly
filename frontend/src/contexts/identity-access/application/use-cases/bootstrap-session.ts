import { AuthError } from '../../domain/auth-error'
import type { AuthSession } from '../../domain/auth-session'
import { refreshSessionUseCase } from './refresh-session'
import type { RefreshSessionDependencies } from './refresh-session'

export async function bootstrapSessionUseCase(
  deps: RefreshSessionDependencies,
): Promise<AuthSession | null> {
  try {
    return await refreshSessionUseCase(deps)
  } catch (error) {
    if (error instanceof AuthError && error.code === 'invalid_refresh_token') {
      deps.store.clear()
      return null
    }
    throw error
  }
}
