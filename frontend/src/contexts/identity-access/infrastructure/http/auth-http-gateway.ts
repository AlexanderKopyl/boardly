import { httpRequest } from '@/shared/api/http-client'
import { ApiError } from '@/shared/api/api-error'

import type {
  AuthGateway,
  LoginResult,
  RefreshSessionResult,
  RegisterResult,
} from '../../application/ports/auth-gateway'
import { AuthError } from '../../domain/auth-error'
import type { AccessTokenResponse, LoginResponse, RegisterResponse } from './auth-api-contracts'

function toAuthError(error: unknown): Error {
  if (error instanceof ApiError && error.statusCode === 401) {
    return new AuthError('invalid_refresh_token')
  }

  return error instanceof Error ? error : new Error('Unexpected authentication error')
}

export class AuthHttpGateway implements AuthGateway {
  async login(email: string, plainPassword: string): Promise<LoginResult> {
    const data = await httpRequest<LoginResponse>('/api/auth/login', {
      method: 'POST',
      body: { email, plainPassword },
    })
    return {
      accessToken: data.accessToken,
      expiresIn: data.expiresIn,
      account: {
        id: data.account.id,
        email: data.account.email,
        name: data.account.name,
        status: data.account.status,
      },
    }
  }

  async register(email: string, plainPassword: string, name: string): Promise<RegisterResult> {
    const data = await httpRequest<RegisterResponse>('/api/auth/register', {
      method: 'POST',
      body: { email, plainPassword, name },
    })
    return {
      accountId: data.accountId,
      status: data.status,
    }
  }

  async refreshSession(): Promise<RefreshSessionResult> {
    let data: AccessTokenResponse
    try {
      data = await httpRequest<AccessTokenResponse>('/api/auth/refresh', {
        method: 'POST',
        headers: { 'X-CSRF-Intent': 'auth-refresh' },
      })
    } catch (error) {
      throw toAuthError(error)
    }

    return {
      accessToken: data.accessToken,
      expiresIn: data.expiresIn,
    }
  }

  async logout(): Promise<void> {
    await httpRequest<void>('/api/auth/logout', {
      method: 'POST',
      headers: { 'X-CSRF-Intent': 'auth-refresh' },
    })
  }
}
