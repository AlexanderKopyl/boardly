import type { Account } from './account'

export interface AuthSession {
  accessToken: string
  expiresAt: Date
  account: Account | null
}
