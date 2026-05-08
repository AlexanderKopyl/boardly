'use client'

import { useState } from 'react'

import { bootstrapSessionUseCase } from '../../application/use-cases/bootstrap-session'
import { loginUseCase } from '../../application/use-cases/login'
import { logoutUseCase } from '../../application/use-cases/logout'
import { registerUseCase } from '../../application/use-cases/register'
import type { AuthSession } from '../../domain/auth-session'
import { AuthHttpGateway } from '../../infrastructure/http/auth-http-gateway'
import { AuthMemoryStore } from '../../infrastructure/state/auth-memory-store'

const gateway = new AuthHttpGateway()
const store = new AuthMemoryStore()

export interface UseAuthResult {
  session: AuthSession | null
  isLoading: boolean
  login(email: string, plainPassword: string): Promise<void>
  register(email: string, plainPassword: string, name: string): Promise<void>
  logout(): Promise<void>
  bootstrap(): Promise<void>
}

export function useAuth(): UseAuthResult {
  const [session, setSession] = useState<AuthSession | null>(store.get())
  const [isLoading, setIsLoading] = useState(false)

  async function login(email: string, plainPassword: string): Promise<void> {
    const result = await loginUseCase({ email, plainPassword }, { gateway, store })
    setSession(result)
  }

  async function register(email: string, plainPassword: string, name: string): Promise<void> {
    await registerUseCase({ email, plainPassword, name }, { gateway })
  }

  async function logout(): Promise<void> {
    await logoutUseCase({ gateway, store })
    setSession(null)
  }

  async function bootstrap(): Promise<void> {
    setIsLoading(true)
    try {
      const result = await bootstrapSessionUseCase({ gateway, store })
      setSession(result)
    } finally {
      setIsLoading(false)
    }
  }

  return { session, isLoading, login, register, logout, bootstrap }
}
