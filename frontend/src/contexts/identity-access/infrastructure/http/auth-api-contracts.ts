import type { AccountStatus } from '../../domain/account-status'

export interface LoginResponse {
  accessToken: string
  tokenType: 'Bearer'
  expiresIn: number
}

export interface RegisterResponse {
  id: string
  email: string
  name: string
  status: 'pending_approval'
}

export interface MeResponse {
  id: string
  email: string
  name: string
  status: AccountStatus
}
