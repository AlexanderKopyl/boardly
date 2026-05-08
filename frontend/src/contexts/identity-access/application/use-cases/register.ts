import type { RegisterResult } from '../ports/auth-gateway'
import type { AuthGateway } from '../ports/auth-gateway'

export interface RegisterInput {
  email: string
  password: string
  name: string
}

export interface RegisterDependencies {
  gateway: AuthGateway
}

export async function registerUseCase(
  input: RegisterInput,
  deps: RegisterDependencies,
): Promise<RegisterResult> {
  return deps.gateway.register(input.email, input.password, input.name)
}
