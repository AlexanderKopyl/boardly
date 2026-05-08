import { httpRequest } from '@/shared/api/http-client'

import type { AuthGateway, LoginResult, RegisterResult } from '../../application/ports/auth-gateway'
import type { Account } from '../../domain/account'
import type { LoginResponse, MeResponse, RegisterResponse } from './auth-api-contracts'

export class AuthHttpGateway implements AuthGateway {
  async login(email: string, plainPassword: string): Promise<LoginResult> {
    const data = await httpRequest<LoginResponse>('/api/auth/login', {
      method: 'POST',
      body: { email, plainPassword },
    })
    return {
      accessToken: data.accessToken,
      expiresIn: data.expiresIn,
    }
  }

  async register(email: string, plainPassword: string, name: string): Promise<RegisterResult> {
    const data = await httpRequest<RegisterResponse>('/api/auth/register', {
      method: 'POST',
      body: { email, plainPassword, name },
    })
    return {
      id: data.id,
      email: data.email,
      name: data.name,
      status: data.status,
    }
  }

  async refreshSession(): Promise<LoginResult> {
    const data = await httpRequest<LoginResponse>('/api/auth/refresh', {
      method: 'POST',
      headers: { 'X-CSRF-Intent': 'auth-refresh' },
    })
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

  async getMe(accessToken: string): Promise<Account> {
    const data = await httpRequest<MeResponse>('/api/auth/me', {
      accessToken,
    })
    return {
      id: data.id,
      email: data.email,
      name: data.name,
      status: data.status,
    }
  }
}
