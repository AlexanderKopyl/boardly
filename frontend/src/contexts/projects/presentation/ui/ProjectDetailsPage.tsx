'use client'

import { useEffect, useState } from 'react'
import { useParams, useRouter } from 'next/navigation'

import { ProjectsError } from '../../domain/project-errors'
import { getProjectUseCase } from '../../application/use-cases/get-project'
import { useProjectsHttpGateway } from '../hooks/useProjectsHttpGateway'

import { Badge } from '@/shared/ui/Badge'
import { Button } from '@/shared/ui/Button'
import { Card } from '@/shared/ui/Card'
import { EmptyState } from '@/shared/ui/EmptyState'
import { PageHeader } from '@/shared/ui/PageHeader'
import { Skeleton } from '@/shared/ui/Skeleton'

type ProjectDetailsViewState =
  | { status: 'loading' }
  | { status: 'ready'; project: Awaited<ReturnType<typeof getProjectUseCase>>['project'] }
  | { status: 'error'; message: string }

function formatDate(value: string): string {
  const date = new Date(value)

  if (Number.isNaN(date.getTime())) {
    return value
  }

  return new Intl.DateTimeFormat(undefined, {
    dateStyle: 'medium',
    timeStyle: 'short',
  }).format(date)
}

function getStatusLabel(status: string): string {
  if (status === 'active') {
    return 'Active'
  }

  if (status === 'archived') {
    return 'Archived'
  }

  if (status === 'deleted') {
    return 'Deleted'
  }

  return status
}

function getStatusVariant(status: string) {
  if (status === 'active') {
    return 'success' as const
  }

  if (status === 'archived') {
    return 'warning' as const
  }

  if (status === 'deleted') {
    return 'destructive' as const
  }

  return 'neutral' as const
}

function getErrorMessage(error: unknown): string {
  if (error instanceof ProjectsError) {
    if (error.code === 'unauthorized') {
      return 'Your session is not available. Sign in again to continue.'
    }

    if (error.code === 'forbidden') {
      return 'You do not have access to this project.'
    }

    if (error.code === 'project_not_found') {
      return 'This project could not be found.'
    }

    if (error.code === 'validation_failed') {
      return 'The server rejected the project details request.'
    }
  }

  return error instanceof Error ? error.message : 'We could not load the project right now.'
}

function ProjectDetailsSkeleton() {
  return (
    <Card className="space-y-6 p-6">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div className="space-y-3">
          <Skeleton className="h-4 w-24" />
          <Skeleton className="h-8 w-64" />
          <Skeleton className="h-4 w-40" />
        </div>
        <Skeleton className="h-6 w-20" />
      </div>

      <div className="grid gap-4 sm:grid-cols-2">
        {Array.from({ length: 4 }).map((_, index) => (
          <div key={`project-detail-skeleton-${index}`} className="space-y-2">
            <Skeleton className="h-4 w-20" />
            <Skeleton className="h-5 w-full" />
          </div>
        ))}
      </div>
    </Card>
  )
}

function getProjectIdParam(value: string | string[] | undefined): string | null {
  if (Array.isArray(value)) {
    return value[0] ?? null
  }

  return value ?? null
}

export function ProjectDetailsPage() {
  const gateway = useProjectsHttpGateway()
  const router = useRouter()
  const params = useParams<{ projectId?: string | string[] }>()
  const projectId = getProjectIdParam(params.projectId)
  const [viewState, setViewState] = useState<ProjectDetailsViewState>({ status: 'loading' })
  const [reloadToken, setReloadToken] = useState(0)

  useEffect(() => {
    if (projectId === null) {
      return
    }

    let isActive = true

    void getProjectUseCase({ gateway, projectId })
      .then((result) => {
        if (!isActive) {
          return
        }

        setViewState({ status: 'ready', project: result.project })
      })
      .catch((error: unknown) => {
        if (!isActive) {
          return
        }

        setViewState({ status: 'error', message: getErrorMessage(error) })
      })

    return () => {
      isActive = false
    }
  }, [gateway, projectId, reloadToken])

  return (
    <div className="space-y-8">
      <PageHeader
        eyebrow="Workspace"
        title="Project details"
        description="Inspect the current backend state for a single project."
        actions={
          <Button variant="secondary" onClick={() => router.push('/app/projects')}>
            Back to projects
          </Button>
        }
      />

      {projectId === null ? (
        <EmptyState
          title="Unable to load project"
          description="The project URL is missing an identifier."
          actions={
            <Button variant="secondary" onClick={() => router.push('/app/projects')}>
              Back to projects
            </Button>
          }
        />
      ) : null}

      {projectId !== null && viewState.status === 'loading' ? <ProjectDetailsSkeleton /> : null}

      {projectId !== null && viewState.status === 'error' ? (
        <EmptyState
          title="Unable to load project"
          description={viewState.message}
          actions={
            <div className="flex flex-wrap gap-3">
              <Button
                onClick={() => {
                  setViewState({ status: 'loading' })
                  setReloadToken((value) => value + 1)
                }}
                variant="secondary"
              >
                Retry
              </Button>
              <Button variant="outline" onClick={() => router.push('/app/projects')}>
                Back to projects
              </Button>
            </div>
          }
        />
      ) : null}

      {projectId !== null && viewState.status === 'ready' ? (
        <Card className="space-y-6 p-6">
          <div className="flex flex-wrap items-start justify-between gap-4">
            <div className="space-y-2">
              <div className="flex flex-wrap items-center gap-3">
                <Badge variant="neutral">{viewState.project.iconKey}</Badge>
                <h2 className="text-2xl font-semibold text-foreground">{viewState.project.name}</h2>
              </div>
              <p className="text-sm text-muted-foreground">Project ID {viewState.project.id}</p>
            </div>
            <Badge variant={getStatusVariant(viewState.project.status)}>
              {getStatusLabel(viewState.project.status)}
            </Badge>
          </div>

          <dl className="grid gap-4 sm:grid-cols-2">
            <div className="space-y-1">
              <dt className="text-xs font-medium uppercase tracking-[0.2em] text-muted-foreground">
                Created
              </dt>
              <dd className="text-sm text-foreground">{formatDate(viewState.project.createdAt)}</dd>
            </div>
            <div className="space-y-1">
              <dt className="text-xs font-medium uppercase tracking-[0.2em] text-muted-foreground">
                Updated
              </dt>
              <dd className="text-sm text-foreground">{formatDate(viewState.project.updatedAt)}</dd>
            </div>
            <div className="space-y-1">
              <dt className="text-xs font-medium uppercase tracking-[0.2em] text-muted-foreground">
                Archived
              </dt>
              <dd className="text-sm text-foreground">
                {viewState.project.archivedAt ? formatDate(viewState.project.archivedAt) : 'Not archived'}
              </dd>
            </div>
            <div className="space-y-1">
              <dt className="text-xs font-medium uppercase tracking-[0.2em] text-muted-foreground">
                Icon key
              </dt>
              <dd className="text-sm text-foreground">{viewState.project.iconKey}</dd>
            </div>
          </dl>

          <div className="rounded-2xl border border-border bg-muted/30 px-4 py-3 text-sm text-muted-foreground">
            Project permissions, editing, and lifecycle changes remain backend-owned and will be
            exposed through dedicated actions in a later slice.
          </div>
        </Card>
      ) : null}
    </div>
  )
}
