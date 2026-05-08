import type { Account } from '../../domain/account'

export interface LoginResult {
  accessToken: string
  expiresIn: number
}

export interface RegisterResult {
  id: string
  email: string
  name: string
  status: 'pending_approval'
}

export interface AuthGateway {
  login(email: string, password: string): Promise<LoginResult>
  register(email: string, password: string, name: string): Promise<RegisterResult>
  refreshSession(): Promise<LoginResult>
  logout(): Promise<void>
  getMe(accessToken: string): Promise<Account>
}
