'use client'

import { useMemo } from 'react'

import { useAuth } from '@/contexts/identity-access/presentation/hooks/useAuth'

import { ProjectsHttpGateway } from '../../infrastructure/http/projects-http-gateway'

export function useProjectsHttpGateway(): ProjectsHttpGateway {
  const { session, refreshAccessToken } = useAuth()

  return useMemo(
    () =>
      new ProjectsHttpGateway({
        getAccessToken: () => session?.accessToken ?? null,
        refreshAccessToken,
      }),
    [refreshAccessToken, session?.accessToken],
  )
}
