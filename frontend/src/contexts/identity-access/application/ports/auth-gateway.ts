import type { Account } from '../../domain/account'

export interface LoginResult {
  accessToken: string
  expiresIn: number
  account: Account
}

export interface RegisterResult {
  accountId: string
  status: 'pending_approval'
}

export interface RefreshSessionResult {
  accessToken: string
  expiresIn: number
}

export interface AuthGateway {
  login(email: string, plainPassword: string): Promise<LoginResult>
  register(email: string, plainPassword: string, name: string): Promise<RegisterResult>
  refreshSession(): Promise<RefreshSessionResult>
  getCurrentAccount(accessToken: string): Promise<Account>
  logout(): Promise<void>
}
