import { refreshSessionUseCase } from '@/contexts/identity-access/application/use-cases/refresh-session'
import { AuthHttpGateway } from '@/contexts/identity-access/infrastructure/http/auth-http-gateway'
import { AuthMemoryStore } from '@/contexts/identity-access/infrastructure/state/auth-memory-store'

const authGateway = new AuthHttpGateway()
const authSessionStore = new AuthMemoryStore()

export { authGateway, authSessionStore }

export async function refreshAuthAccessToken(): Promise<string | null> {
  try {
    const session = await refreshSessionUseCase({
      gateway: authGateway,
      store: authSessionStore,
    })

    return session.accessToken
  } catch {
    authSessionStore.clear()
    return null
  }
}
