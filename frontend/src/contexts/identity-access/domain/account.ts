import type { AccountStatus } from './account-status'

export interface Account {
  id: string
  email: string
  name: string
  status: AccountStatus
}
