import type { AuthSession } from '../../domain/auth-session'
import type { AuthGateway } from '../ports/auth-gateway'
import type { AuthSessionStore } from '../ports/auth-session-store'

export interface LoginInput {
  email: string
  password: string
}

export interface LoginDependencies {
  gateway: AuthGateway
  store: AuthSessionStore
  now?: () => Date
}

export async function loginUseCase(
  input: LoginInput,
  deps: LoginDependencies,
): Promise<AuthSession> {
  const now = deps.now ?? (() => new Date())

  const loginResult = await deps.gateway.login(input.email, input.password)
  const account = await deps.gateway.getMe(loginResult.accessToken)

  const session: AuthSession = {
    accessToken: loginResult.accessToken,
    expiresAt: new Date(now().getTime() + loginResult.expiresIn * 1000),
    account,
  }

  deps.store.save(session)
  return session
}
