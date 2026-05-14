'use client'

import { type ReactNode, useEffect } from 'react'
import { useRouter } from 'next/navigation'

import { useAuth } from '../hooks/useAuth'
import { SessionLoadingState } from '../ui/SessionLoadingState'

export interface ProtectedRouteProps {
  children: ReactNode
}

export function ProtectedRoute({ children }: ProtectedRouteProps) {
  const { session, isLoading, hasBootstrapped, bootstrap } = useAuth()
  const router = useRouter()

  // eslint-disable-next-line react-hooks/exhaustive-deps
  useEffect(() => { bootstrap() }, [])

  useEffect(() => {
    if (hasBootstrapped && !isLoading && session === null) {
      router.replace('/login')
    }
  }, [hasBootstrapped, isLoading, session, router])

  if (isLoading || (!hasBootstrapped && session === null)) {
    return <SessionLoadingState />
  }

  if (session === null) {
    return null
  }

  return <>{children}</>
}
