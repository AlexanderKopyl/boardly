'use client'

import { type ReactNode, useEffect } from 'react'
import { useRouter } from 'next/navigation'

import { useAuth } from '../hooks/useAuth'

export interface ProtectedRouteProps {
  children: ReactNode
}

export function ProtectedRoute({ children }: ProtectedRouteProps) {
  const { session, isLoading, bootstrap } = useAuth()
  const router = useRouter()

  // eslint-disable-next-line react-hooks/exhaustive-deps
  useEffect(() => { bootstrap() }, [])

  if (isLoading) {
    return <p>Loading…</p>
  }

  if (session === null) {
    router.replace('/login')
    return null
  }

  return <>{children}</>
}
