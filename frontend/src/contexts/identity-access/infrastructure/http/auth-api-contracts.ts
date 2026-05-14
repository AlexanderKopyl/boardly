import type { AccountStatus } from '../../domain/account-status'

export interface AccountResponse {
  id: string
  email: string
  name: string
  status: AccountStatus
}

export interface CurrentAccountResponse {
  id: string
  email: string
  name: string
  status: AccountStatus
}

export interface LoginResponse {
  accessToken: string
  tokenType: 'Bearer'
  expiresIn: number
  account: AccountResponse
}

export interface AccessTokenResponse {
  accessToken: string
  tokenType: 'Bearer'
  expiresIn: number
}

export interface RegisterResponse {
  accountId: string
  status: 'pending_approval'
}
