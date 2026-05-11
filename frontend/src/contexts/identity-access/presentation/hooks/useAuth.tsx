'use client'

import { createContext, useCallback, useContext, useMemo, useState } from 'react'

import { bootstrapSessionUseCase } from '../../application/use-cases/bootstrap-session'
import { loginUseCase } from '../../application/use-cases/login'
import { logoutUseCase } from '../../application/use-cases/logout'
import { registerUseCase } from '../../application/use-cases/register'
import type { AuthSession } from '../../domain/auth-session'
import { AuthHttpGateway } from '../../infrastructure/http/auth-http-gateway'
import { AuthMemoryStore } from '../../infrastructure/state/auth-memory-store'

const gateway = new AuthHttpGateway()
const store = new AuthMemoryStore()

export interface AuthContextValue {
  session: AuthSession | null
  isLoading: boolean
  hasBootstrapped: boolean
  login(email: string, plainPassword: string): Promise<void>
  register(email: string, plainPassword: string, name: string): Promise<void>
  logout(): Promise<void>
  bootstrap(): Promise<void>
}

const AuthContext = createContext<AuthContextValue | null>(null)

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [session, setSession] = useState<AuthSession | null>(store.get())
  const [isLoading, setIsLoading] = useState(false)
  const [hasBootstrapped, setHasBootstrapped] = useState(false)

  const login = useCallback(async (email: string, plainPassword: string): Promise<void> => {
    const result = await loginUseCase({ email, plainPassword }, { gateway, store })
    setSession(result)
  }, [])

  const register = useCallback(async (email: string, plainPassword: string, name: string): Promise<void> => {
    await registerUseCase({ email, plainPassword, name }, { gateway })
  }, [])

  const logout = useCallback(async (): Promise<void> => {
    await logoutUseCase({ gateway, store })
    setSession(null)
    setHasBootstrapped(false)
  }, [])

  const bootstrap = useCallback(async (): Promise<void> => {
    setIsLoading(true)
    try {
      const result = await bootstrapSessionUseCase({ gateway, store })
      setSession(result)
      setHasBootstrapped(true)
    } finally {
      setIsLoading(false)
    }
  }, [])

  const value = useMemo<AuthContextValue>(
    () => ({ session, isLoading, hasBootstrapped, login, register, logout, bootstrap }),
    [session, isLoading, hasBootstrapped, login, register, logout, bootstrap],
  )

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>
}

export function useAuth(): AuthContextValue {
  const value = useContext(AuthContext)

  if (value === null) {
    throw new Error('useAuth must be used within AuthProvider')
  }

  return value
}
