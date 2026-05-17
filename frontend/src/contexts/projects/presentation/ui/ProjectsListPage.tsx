'use client'

import Link from 'next/link'
import { useEffect, useRef, useState } from 'react'

import { archiveProjectUseCase } from '../../application/use-cases/archive-project'
import { deleteProjectUseCase } from '../../application/use-cases/delete-project'
import { listProjectsUseCase } from '../../application/use-cases/list-projects'
import { ProjectsHttpGateway } from '../../infrastructure/http/projects-http-gateway'
import { ProjectsError } from '../../domain/project-errors'

import { Badge } from '@/shared/ui/Badge'
import { Alert } from '@/shared/ui/Alert'
import { Button } from '@/shared/ui/Button'
import { Card } from '@/shared/ui/Card'
import { EmptyState } from '@/shared/ui/EmptyState'
import { PageHeader } from '@/shared/ui/PageHeader'
import { Skeleton } from '@/shared/ui/Skeleton'

const gateway = new ProjectsHttpGateway()

type ProjectAction = 'archive' | 'delete'

type ProjectActionState = {
  readonly projectId: string
  readonly action: ProjectAction
}

type ProjectsViewState =
  | { status: 'loading' }
  | { status: 'ready'; projects: Awaited<ReturnType<typeof listProjectsUseCase>>['projects'] }
  | { status: 'empty' }
  | { status: 'error'; message: string }

type ProjectActionError = ProjectActionState & {
  readonly message: string
}

function getActionTitle(action: ProjectAction): string {
  return action === 'archive' ? 'Archive project' : 'Delete project'
}

function getActionDescription(action: ProjectAction): string {
  if (action === 'archive') {
    return 'Archived projects stay in the workspace but are no longer active.'
  }

  return 'Deleting a project removes it from the workspace and cannot be undone.'
}

function getActionConfirmLabel(action: ProjectAction): string {
  return action === 'archive' ? 'Archive project' : 'Delete project'
}

function formatCreatedAt(createdAt: string): string {
  const date = new Date(createdAt)

  if (Number.isNaN(date.getTime())) {
    return createdAt
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
      return 'You do not have access to this projects list.'
    }

    if (error.code === 'project_not_found') {
      return 'Projects could not be found.'
    }

    if (error.code === 'validation_failed') {
      return 'The server rejected the projects request.'
    }
  }

  return error instanceof Error ? error.message : 'We could not load projects right now.'
}

function getActionErrorMessage(error: unknown): string {
  if (error instanceof ProjectsError) {
    if (error.code === 'unauthorized') {
      return 'Your session is not available. Sign in again to continue.'
    }

    if (error.code === 'forbidden') {
      return 'You do not have permission to change this project.'
    }

    if (error.code === 'project_not_found') {
      return 'The project no longer exists.'
    }
  }

  return error instanceof Error ? error.message : 'We could not update the project right now.'
}

function ProjectsListSkeleton() {
  return (
    <div className="space-y-4">
      {Array.from({ length: 3 }).map((_, index) => (
        <Card key={`project-skeleton-${index}`} className="space-y-4 p-5">
          <div className="flex items-start justify-between gap-4">
            <div className="space-y-3">
              <Skeleton className="h-4 w-24" />
              <Skeleton className="h-7 w-48" />
            </div>
            <Skeleton className="h-6 w-20" />
          </div>
          <Skeleton className="h-4 w-36" />
        </Card>
      ))}
    </div>
  )
}

export function ProjectsListPage() {
  const isMountedRef = useRef(true)
  const [viewState, setViewState] = useState<ProjectsViewState>({ status: 'loading' })
  const [reloadToken, setReloadToken] = useState(0)
  const [activeAction, setActiveAction] = useState<ProjectActionState | null>(null)
  const [pendingAction, setPendingAction] = useState<ProjectActionState | null>(null)
  const [actionError, setActionError] = useState<ProjectActionError | null>(null)

  useEffect(() => {
    return () => {
      isMountedRef.current = false
    }
  }, [])

  useEffect(() => {
    let isActive = true

    void listProjectsUseCase({ gateway })
      .then((result) => {
        if (!isActive) {
          return
        }

        if (result.projects.length === 0) {
          setViewState({ status: 'empty' })
          return
        }

        setViewState({ status: 'ready', projects: result.projects })
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
  }, [reloadToken])

  async function handleProjectAction(projectAction: ProjectActionState) {
    setActionError(null)
    setPendingAction(projectAction)
    setActiveAction(null)

    try {
      if (projectAction.action === 'archive') {
        await archiveProjectUseCase({ gateway, projectId: projectAction.projectId })
      } else {
        await deleteProjectUseCase({ gateway, projectId: projectAction.projectId })
      }

      if (!isMountedRef.current) {
        return
      }

      setReloadToken((value) => value + 1)
    } catch (error: unknown) {
      if (!isMountedRef.current) {
        return
      }

      setActionError({
        ...projectAction,
        message: getActionErrorMessage(error),
      })
    } finally {
      if (isMountedRef.current) {
        setPendingAction(null)
      }
    }
  }

  const headerActions =
    viewState.status === 'ready' ? (
      <Badge variant="neutral">{viewState.projects.length} projects</Badge>
    ) : (
      <Badge variant="neutral">Workspace projects</Badge>
    )

  return (
    <div className="space-y-8">
      <PageHeader
        eyebrow="Workspace"
        title="Projects"
        description="Browse the projects owned by the authenticated workspace."
        actions={headerActions}
      />

      {viewState.status === 'loading' ? <ProjectsListSkeleton /> : null}

      {viewState.status === 'error' ? (
        <EmptyState
          title="Unable to load projects"
          description={viewState.message}
          actions={
            <Button
              onClick={() => {
                setViewState({ status: 'loading' })
                setReloadToken((value) => value + 1)
              }}
              variant="secondary"
            >
              Retry
            </Button>
          }
        />
      ) : null}

      {viewState.status === 'empty' ? (
        <EmptyState
          title="No projects yet"
          description="This workspace does not have any projects yet."
        />
      ) : null}

      {viewState.status === 'ready' ? (
        <section aria-label="Project list" className="space-y-4">
          {viewState.projects.map((project) => (
            <Card key={project.id} className="space-y-4 p-5">
              <div className="flex flex-wrap items-start justify-between gap-4">
                <div className="space-y-2">
                  <div className="flex flex-wrap items-center gap-3">
                    <Badge variant="neutral">{project.iconKey}</Badge>
                    <Link
                      href={`/app/projects/${project.id}`}
                      className="text-lg font-semibold text-foreground transition-colors hover:text-primary hover:underline"
                    >
                      {project.name}
                    </Link>
                  </div>
                  <p className="text-sm text-muted-foreground">Created {formatCreatedAt(project.createdAt)}</p>
                </div>
                <Badge variant={getStatusVariant(project.status)}>{getStatusLabel(project.status)}</Badge>
              </div>

              <div className="space-y-3">
                <div className="flex flex-wrap items-center gap-2">
                  {project.status === 'active' ? (
                    <Button
                      variant="outline"
                      size="sm"
                      disabled={pendingAction !== null}
                      onClick={() => {
                        setActionError(null)
                        setActiveAction({ projectId: project.id, action: 'archive' })
                      }}
                    >
                      Archive
                    </Button>
                  ) : null}
                  <Button
                    variant="destructive"
                    size="sm"
                    disabled={pendingAction !== null}
                    onClick={() => {
                      setActionError(null)
                      setActiveAction({ projectId: project.id, action: 'delete' })
                    }}
                  >
                    Delete
                  </Button>
                </div>

                {activeAction !== null && activeAction.projectId === project.id ? (
                  <Card className="space-y-3 border border-border/70 bg-muted/40 p-4">
                    <div className="space-y-1">
                      <p className="text-sm font-semibold text-foreground">{getActionTitle(activeAction.action)}</p>
                      <p className="text-sm text-muted-foreground">{getActionDescription(activeAction.action)}</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                      <Button
                        variant={activeAction.action === 'delete' ? 'destructive' : 'secondary'}
                        size="sm"
                        isLoading={
                          pendingAction?.projectId === project.id && pendingAction.action === activeAction.action
                        }
                        disabled={pendingAction !== null}
                        onClick={() => {
                          void handleProjectAction(activeAction)
                        }}
                      >
                        {getActionConfirmLabel(activeAction.action)}
                      </Button>
                      <Button
                        variant="ghost"
                        size="sm"
                        disabled={pendingAction !== null}
                        onClick={() => {
                          setActiveAction(null)
                        }}
                      >
                        Cancel
                      </Button>
                    </div>
                  </Card>
                ) : null}

                {actionError !== null && actionError.projectId === project.id ? (
                  <Alert variant="destructive">
                    {actionError.action === 'archive' ? 'Archive project failed.' : 'Delete project failed.'}{' '}
                    {actionError.message}
                  </Alert>
                ) : null}
              </div>
            </Card>
          ))}
        </section>
      ) : null}
    </div>
  )
}
