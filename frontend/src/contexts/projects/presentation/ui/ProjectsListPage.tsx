'use client'

import Link from 'next/link'
import { useRouter } from 'next/navigation'
import { useEffect, useRef, useState } from 'react'
import type { ReactNode } from 'react'

import { archiveProjectUseCase } from '../../application/use-cases/archive-project'
import { deleteProjectUseCase } from '../../application/use-cases/delete-project'
import { listProjectsUseCase } from '../../application/use-cases/list-projects'
import { ProjectsError } from '../../domain/project-errors'
import { useProjectsHttpGateway } from '../hooks/useProjectsHttpGateway'

import { Alert } from '@/shared/ui/Alert'
import { Badge } from '@/shared/ui/Badge'
import { Button } from '@/shared/ui/Button'
import { Skeleton } from '@/shared/ui/Skeleton'

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

  return new Intl.DateTimeFormat('en-US', {
    timeZone: 'UTC',
    dateStyle: 'medium',
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

function getIconTileClasses(status: string): string {
  if (status === 'active') {
    return 'border-primary/15 bg-primary/10 text-primary'
  }

  if (status === 'archived') {
    return 'border-border/70 bg-muted text-muted-foreground'
  }

  if (status === 'deleted') {
    return 'border-destructive/20 bg-destructive/10 text-destructive'
  }

  return 'border-border/70 bg-muted text-muted-foreground'
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

type CreateProjectActionProps = {
  readonly onClick: () => void
}

function CreateProjectAction({ onClick }: CreateProjectActionProps) {
  return (
    <Button
      variant="primary"
      size="md"
      onClick={onClick}
      className="h-11 rounded-xl px-5 text-sm font-semibold shadow-sm"
    >
      New project
    </Button>
  )
}

function ProjectsListNotice({
  title,
  description,
  action,
  icon,
}: {
  readonly title: string
  readonly description: string
  readonly action?: ReactNode
  readonly icon: string
}) {
  return (
    <section
      aria-label={title}
      className="rounded-2xl border border-border/70 bg-card px-5 py-6 shadow-sm sm:px-6 sm:py-7"
    >
      <div className="flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div className="flex min-w-0 items-start gap-4">
          <div className="flex size-12 shrink-0 items-center justify-center rounded-xl border border-border/70 bg-muted text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
            {icon}
          </div>

          <div className="space-y-1">
            <h2 className="text-base font-semibold tracking-tight text-foreground sm:text-lg">{title}</h2>
            <p className="max-w-2xl text-sm leading-6 text-muted-foreground">{description}</p>
          </div>
        </div>

        {action ? <div className="shrink-0">{action}</div> : null}
      </div>
    </section>
  )
}

function ProjectsListSkeleton() {
  return (
    <section aria-label="Projects loading" className="space-y-3">
      <div className="rounded-2xl border border-border/70 bg-card px-4 py-4 shadow-sm sm:px-5">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <div className="space-y-2">
            <Skeleton className="h-5 w-40" />
            <Skeleton className="h-4 w-72 max-w-full" />
          </div>
          <div className="flex items-center gap-3">
            <Skeleton className="h-7 w-24 rounded-full" />
            <Skeleton className="h-11 w-36 rounded-xl" />
          </div>
        </div>
      </div>

      {Array.from({ length: 4 }).map((_, index) => (
        <article
          key={`project-skeleton-${index}`}
          className="grid gap-4 rounded-2xl border border-border/70 bg-card px-4 py-4 shadow-sm md:grid-cols-[minmax(0,1fr)_auto] md:items-start md:px-5"
        >
          <div className="flex min-w-0 items-start gap-4">
            <Skeleton className="size-12 shrink-0 rounded-xl" />

            <div className="min-w-0 flex-1 space-y-2">
              <Skeleton className="h-5 w-56 max-w-full" />
              <div className="flex flex-wrap items-center gap-3">
                <Skeleton className="h-4 w-40 max-w-full" />
                <Skeleton className="h-6 w-20 rounded-full" />
              </div>
            </div>
          </div>

          <div className="flex min-w-0 flex-col gap-3 md:items-end">
            <div className="flex flex-wrap items-center gap-3 md:justify-end">
              <Skeleton className="h-6 w-20 rounded-full" />
              <Skeleton className="h-5 w-24" />
            </div>

            <div className="flex flex-wrap gap-2 md:justify-end">
              <Skeleton className="h-8 w-24 rounded-md" />
              <Skeleton className="h-8 w-20 rounded-md" />
            </div>
          </div>
        </article>
      ))}
    </section>
  )
}

function ProjectRow({
  project,
  pendingAction,
  activeAction,
  actionError,
  rowActionDisabled,
  onArchive,
  onDelete,
  onConfirmAction,
  onCancelAction,
}: {
  readonly project: Awaited<ReturnType<typeof listProjectsUseCase>>['projects'][number]
  readonly pendingAction: ProjectActionState | null
  readonly activeAction: ProjectActionState | null
  readonly actionError: ProjectActionError | null
  readonly rowActionDisabled: boolean
  readonly onArchive: () => void
  readonly onDelete: () => void
  readonly onConfirmAction: () => void
  readonly onCancelAction: () => void
}) {
  const isProjectActionPending = pendingAction?.projectId === project.id
  const isSelectedActionPending = isProjectActionPending && pendingAction?.action === activeAction?.action

  return (
    <article className="grid gap-4 rounded-2xl border border-border/70 bg-card px-4 py-4 shadow-sm transition-colors md:grid-cols-[minmax(0,1fr)_auto] md:items-start md:px-5">
      <div className="flex min-w-0 items-start gap-4">
        <div
          className={`flex size-12 shrink-0 items-center justify-center rounded-xl border text-xs font-semibold uppercase tracking-[0.18em] ${getIconTileClasses(
            project.status,
          )}`}
        >
          <span className="max-w-full truncate px-1">{project.iconKey}</span>
        </div>

        <div className="min-w-0 space-y-1.5">
          <Link
            href={`/app/projects/${project.id}`}
            className="block max-w-full truncate text-[15px] font-semibold tracking-tight text-foreground transition-colors hover:text-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background sm:text-base"
          >
            {project.name}
          </Link>

          <div className="flex flex-wrap items-center gap-x-3 gap-y-2 text-sm text-muted-foreground">
            <span>Created {formatCreatedAt(project.createdAt)}</span>
            <Badge variant={getStatusVariant(project.status)} className="rounded-full px-2.5 py-1">
              {getStatusLabel(project.status)}
            </Badge>
          </div>
        </div>
      </div>

      <div className="flex min-w-0 flex-col gap-3 md:items-end">
        <div className="flex flex-wrap items-center gap-3 md:justify-end">
          <Link
            href={`/app/projects/${project.id}`}
            className="inline-flex items-center gap-1 text-sm font-semibold text-primary transition-colors hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
          >
            View details
            <span aria-hidden="true" className="text-base leading-none">
              ›
            </span>
          </Link>

          {project.status === 'active' ? (
            <Button
              variant="outline"
              size="sm"
              disabled={rowActionDisabled}
              className="h-8 rounded-md px-3 text-xs font-semibold"
              onClick={onArchive}
            >
              Archive
            </Button>
          ) : null}

          <Button
            variant="destructive"
            size="sm"
            disabled={rowActionDisabled}
            className="h-8 rounded-md px-3 text-xs font-semibold"
            onClick={onDelete}
          >
            Delete
          </Button>
        </div>

        {activeAction !== null ? (
          <div className="w-full rounded-2xl border border-border/70 bg-muted/35 p-4 md:max-w-md">
            <div className="space-y-1.5">
              <p className="text-sm font-semibold tracking-tight text-foreground">
                {getActionTitle(activeAction.action)}
              </p>
              <p className="text-sm leading-6 text-muted-foreground">
                {getActionDescription(activeAction.action)}
              </p>
            </div>

            <div className="mt-4 flex flex-wrap items-center gap-2">
              <Button
                variant={activeAction.action === 'delete' ? 'destructive' : 'secondary'}
                size="sm"
                isLoading={isSelectedActionPending}
                disabled={pendingAction !== null}
                className="h-8 rounded-md px-3 text-xs font-semibold"
                onClick={onConfirmAction}
              >
                {getActionConfirmLabel(activeAction.action)}
              </Button>

              <Button
                variant="ghost"
                size="sm"
                disabled={pendingAction !== null}
                className="h-8 rounded-md px-3 text-xs font-semibold"
                onClick={onCancelAction}
              >
                Cancel
              </Button>
            </div>
          </div>
        ) : null}

        {actionError !== null && actionError.projectId === project.id ? (
          <Alert variant="destructive" className="w-full md:max-w-md">
            {actionError.action === 'archive' ? 'Archive project failed.' : 'Delete project failed.'}{' '}
            {actionError.message}
          </Alert>
        ) : null}
      </div>
    </article>
  )
}

export function ProjectsListPage() {
  const gateway = useProjectsHttpGateway()
  const router = useRouter()
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
  }, [gateway, reloadToken])

  function handleCreateProject() {
    router.push('/app/projects/new')
  }

  async function handleProjectAction(projectAction: ProjectActionState) {
    setActionError(null)
    setPendingAction(projectAction)

    try {
      if (projectAction.action === 'archive') {
        await archiveProjectUseCase({ gateway, projectId: projectAction.projectId })
      } else {
        await deleteProjectUseCase({ gateway, projectId: projectAction.projectId })
      }

      if (!isMountedRef.current) {
        return
      }

      setActiveAction(null)
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

  const projectCount = viewState.status === 'ready' ? viewState.projects.length : null
  const showProjectCount = projectCount !== null

  return (
    <div className="space-y-6 pb-8">
      <section className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div className="space-y-2">
          <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">
            Workspace
          </p>
          <div className="space-y-2">
            <h1 className="text-balance text-3xl font-semibold tracking-tight text-foreground sm:text-4xl">
              Projects
            </h1>
            <p className="max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base">
              Manage and track your active workspace initiatives.
            </p>
          </div>
        </div>

        <div className="flex flex-wrap items-center gap-2 md:justify-end">
          {showProjectCount ? (
            <Badge variant="neutral" className="rounded-full px-3 py-1 text-[11px] font-semibold">
              {projectCount} projects
            </Badge>
          ) : null}
          <CreateProjectAction onClick={handleCreateProject} />
        </div>
      </section>

      {viewState.status === 'loading' ? <ProjectsListSkeleton /> : null}

      {viewState.status === 'error' ? (
        <ProjectsListNotice
          title="Unable to load projects"
          description={viewState.message}
          icon="!"
          action={
            <Button
              onClick={() => {
                setViewState({ status: 'loading' })
                setReloadToken((value) => value + 1)
              }}
              variant="secondary"
              className="h-11 rounded-xl px-5 text-sm font-semibold"
            >
              Retry
            </Button>
          }
        />
      ) : null}

      {viewState.status === 'empty' ? (
        <ProjectsListNotice
          title="No projects yet"
          description="This workspace does not have any projects yet."
          icon="0"
          action={<CreateProjectAction onClick={handleCreateProject} />}
        />
      ) : null}

      {viewState.status === 'ready' ? (
        <section aria-label="Project list" className="space-y-3">
          {viewState.projects.map((project) => {
            const actionState = activeAction?.projectId === project.id ? activeAction : null
            const rowActionDisabled = pendingAction !== null

            return (
              <ProjectRow
                key={project.id}
                project={project}
                pendingAction={pendingAction}
                activeAction={actionState}
                actionError={actionError}
                rowActionDisabled={rowActionDisabled}
                onArchive={() => {
                  setActionError(null)
                  setActiveAction({ projectId: project.id, action: 'archive' })
                }}
                onDelete={() => {
                  setActionError(null)
                  setActiveAction({ projectId: project.id, action: 'delete' })
                }}
                onConfirmAction={() => {
                  if (actionState === null) {
                    return
                  }

                  void handleProjectAction(actionState)
                }}
                onCancelAction={() => {
                  setActiveAction(null)
                }}
              />
            )
          })}
        </section>
      ) : null}
    </div>
  )
}
