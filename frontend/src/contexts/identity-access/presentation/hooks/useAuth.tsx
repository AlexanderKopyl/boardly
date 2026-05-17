'use client'

import { createContext, useCallback, useContext, useMemo, useState } from 'react'

import { bootstrapSessionUseCase } from '../../application/use-cases/bootstrap-session'
import { loginUseCase } from '../../application/use-cases/login'
import { logoutUseCase } from '../../application/use-cases/logout'
import { registerUseCase } from '../../application/use-cases/register'
import type { AuthSession } from '../../domain/auth-session'
import { AuthHttpGateway } from '@/contexts/identity-access/infrastructure/http/auth-http-gateway'
import { AuthMemoryStore } from '@/contexts/identity-access/infrastructure/state/auth-memory-store'

const authGateway = new AuthHttpGateway()
const authSessionStore = new AuthMemoryStore()

let sessionHydrationInFlight: Promise<AuthSession | null> | null = null

export interface AuthContextValue {
  session: AuthSession | null
  isLoading: boolean
  hasBootstrapped: boolean
  login(email: string, plainPassword: string): Promise<void>
  register(email: string, plainPassword: string, name: string): Promise<void>
  logout(): Promise<void>
  bootstrap(): Promise<void>
  refreshAccessToken(): Promise<string | null>
}

const AuthContext = createContext<AuthContextValue | null>(null)

async function hydrateSession(): Promise<AuthSession | null> {
  if (sessionHydrationInFlight !== null) {
    return sessionHydrationInFlight
  }

  const inFlight = (async () => {
    try {
      const result = await bootstrapSessionUseCase({ gateway: authGateway, store: authSessionStore })
      return result
    } catch {
      authSessionStore.clear()
      return null
    }
  })()

  sessionHydrationInFlight = inFlight

  try {
    return await inFlight
  } finally {
    if (sessionHydrationInFlight === inFlight) {
      sessionHydrationInFlight = null
    }
  }
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [session, setSession] = useState<AuthSession | null>(authSessionStore.get())
  const [isLoading, setIsLoading] = useState(false)
  const [hasBootstrapped, setHasBootstrapped] = useState(false)

  const login = useCallback(async (email: string, plainPassword: string): Promise<void> => {
    const result = await loginUseCase({ email, plainPassword }, { gateway: authGateway, store: authSessionStore })
    setSession(result)
  }, [])

  const register = useCallback(async (email: string, plainPassword: string, name: string): Promise<void> => {
    await registerUseCase({ email, plainPassword, name }, { gateway: authGateway })
  }, [])

  const logout = useCallback(async (): Promise<void> => {
    await logoutUseCase({ gateway: authGateway, store: authSessionStore })
    setSession(null)
    setHasBootstrapped(false)
  }, [])

  const bootstrap = useCallback(async (): Promise<void> => {
    if (session !== null) {
      if (!hasBootstrapped) {
        setHasBootstrapped(true)
      }

      return
    }

    setIsLoading(true)
    try {
      const result = await hydrateSession()
      setSession(result)
    } finally {
      setHasBootstrapped(true)
      setIsLoading(false)
    }
  }, [hasBootstrapped, session])

  const refreshAccessToken = useCallback(async (): Promise<string | null> => {
    const result = await hydrateSession()
    setSession(result)

    return result?.accessToken ?? null
  }, [])

  const value = useMemo<AuthContextValue>(
    () => ({
      session,
      isLoading,
      hasBootstrapped,
      login,
      register,
      logout,
      bootstrap,
      refreshAccessToken,
    }),
    [session, isLoading, hasBootstrapped, login, register, logout, bootstrap, refreshAccessToken],
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
