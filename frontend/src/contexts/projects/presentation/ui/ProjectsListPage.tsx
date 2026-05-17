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
    return 'border-border/70 bg-secondary-container text-secondary'
  }

  if (status === 'deleted') {
    return 'border-destructive/20 bg-destructive/10 text-destructive'
  }

  return 'border-border/70 bg-muted text-muted-foreground'
}

function getProjectGlyph(iconKey: string): string {
  const normalized = iconKey.replace(/[^a-z0-9]/gi, '')
  return (normalized.slice(0, 2) || 'PR').toUpperCase()
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

function PlusIcon(): ReactNode {
  return <span aria-hidden="true" className="text-[20px] leading-none">+</span>
}

function ChevronIcon(): ReactNode {
  return <span aria-hidden="true" className="text-base leading-none">›</span>
}

function CalendarIcon(): ReactNode {
  return (
    <svg aria-hidden="true" viewBox="0 0 20 20" className="size-3.5">
      <path
        fill="currentColor"
        d="M6 1.75a.75.75 0 0 1 .75.75V3h6.5v-.5a.75.75 0 0 1 1.5 0V3h1.5A1.75 1.75 0 0 1 18 4.75v11.5A1.75 1.75 0 0 1 16.25 18H3.75A1.75 1.75 0 0 1 2 16.25V4.75A1.75 1.75 0 0 1 3.75 3h1.5v-.5a.75.75 0 0 1 .75-.75ZM16.5 8H3.5v8.25c0 .138.112.25.25.25h12.5a.25.25 0 0 0 .25-.25V8Zm-1.5-3.5h-11c-.138 0-.25.112-.25.25V6.5h11.5V4.75a.25.25 0 0 0-.25-.25Z"
      />
    </svg>
  )
}

function FilterChip({
  label,
  value,
}: {
  readonly label: string
  readonly value: string
}) {
  return (
    <div className="flex items-center gap-2 rounded-[12px] border border-border/60 bg-primary/10 px-3 py-2 shadow-none">
      <span className="text-[11px] font-semibold uppercase tracking-[0.18em] text-secondary">
        {label}
      </span>
      <span className="text-sm font-semibold text-foreground">{value}</span>
      <span aria-hidden="true" className="text-secondary">
        ▾
      </span>
    </div>
  )
}

function CreateProjectAction({ onClick }: { readonly onClick: () => void }) {
  return (
    <Button
      variant="primary"
      size="md"
      onClick={onClick}
      className="h-10 rounded-[12px] px-5 text-sm font-semibold shadow-sm !bg-[var(--navy-700)] !text-white hover:!bg-[var(--navy-800)]"
    >
      {PlusIcon()}
      New Project
    </Button>
  )
}

function ProjectsListNotice({
  title,
  description,
  action,
  icon,
  tone = 'neutral',
}: {
  readonly title: string
  readonly description: string
  readonly action?: ReactNode
  readonly icon: string
  readonly tone?: 'neutral' | 'destructive'
}) {
  return (
    <section
      aria-label={title}
      className={
        tone === 'destructive'
          ? 'rounded-[12px] border border-destructive/20 bg-destructive/5 px-5 py-6'
          : 'rounded-[12px] border border-border/60 bg-card px-5 py-6 shadow-sm'
      }
    >
      <div className="flex flex-col items-start gap-4 md:flex-row md:items-center md:justify-between">
        <div className="flex min-w-0 items-start gap-4">
          <div className="flex size-12 shrink-0 items-center justify-center rounded-[10px] border border-border/60 bg-muted text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
            {icon}
          </div>

          <div className="space-y-1">
            <h2 className="text-base font-semibold tracking-tight text-foreground">{title}</h2>
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
    <section aria-label="Projects loading" className="space-y-6">
      <div className="rounded-[12px] border border-border/60 bg-card p-4 shadow-sm sm:p-5">
        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div className="flex flex-wrap items-center gap-2">
            <Skeleton className="h-10 w-48 rounded-[14px]" />
            <Skeleton className="h-10 w-52 rounded-[14px]" />
          </div>
          <Skeleton className="h-5 w-52 rounded-full lg:ml-auto" />
        </div>
      </div>

      <div className="space-y-3">
        {Array.from({ length: 4 }).map((_, index) => (
          <article
            key={`project-skeleton-${index}`}
            className="rounded-[12px] border border-border/60 bg-card px-4 py-4 shadow-sm sm:px-5"
          >
            <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
              <div className="flex min-w-0 items-center gap-4">
                <Skeleton className="size-11 shrink-0 rounded-[10px]" />
                <div className="min-w-0 space-y-2">
                  <Skeleton className="h-5 w-60 max-w-full" />
                  <div className="flex flex-wrap items-center gap-3">
                    <Skeleton className="h-4 w-36 max-w-full" />
                  </div>
                </div>
              </div>

              <div className="flex min-w-0 flex-col items-start gap-3 lg:items-end">
                <div className="flex items-center gap-3">
                  <Skeleton className="h-7 w-20 rounded-full" />
                  <Skeleton className="h-5 w-24" />
                </div>
                <div className="flex items-center gap-2">
                  <Skeleton className="h-8 w-24 rounded-md" />
                  <Skeleton className="h-8 w-20 rounded-md" />
                </div>
              </div>
            </div>
          </article>
        ))}
      </div>

      <div className="grid gap-4 lg:grid-cols-[1.65fr_1fr]">
        <div className="rounded-[16px] bg-primary p-6 text-primary-foreground shadow-sm">
          <Skeleton className="h-3 w-28 rounded-full bg-white/25" />
          <Skeleton className="mt-3 h-8 w-52 rounded-md bg-white/20" />
          <div className="mt-8 grid grid-cols-3 gap-4">
            <div className="space-y-2">
              <Skeleton className="h-3 w-20 rounded-full bg-white/20" />
              <Skeleton className="h-10 w-16 rounded-md bg-white/20" />
            </div>
            <div className="space-y-2">
              <Skeleton className="h-3 w-20 rounded-full bg-white/20" />
              <Skeleton className="h-10 w-16 rounded-md bg-white/20" />
            </div>
            <div className="space-y-2">
              <Skeleton className="h-3 w-20 rounded-full bg-white/20" />
              <Skeleton className="h-10 w-16 rounded-md bg-white/20" />
            </div>
          </div>
        </div>

        <div className="rounded-[16px] border border-border/60 bg-card p-6 shadow-sm">
          <Skeleton className="h-3 w-32 rounded-full" />
          <Skeleton className="mt-3 h-6 w-40 rounded-md" />
          <Skeleton className="mt-4 h-16 w-full rounded-md" />
        </div>
      </div>
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
    <article className="group rounded-[12px] border border-border/60 bg-card px-4 py-4 shadow-[0_1px_2px_rgba(15,23,42,0.03)] transition-colors hover:border-primary/20 hover:bg-surface-container-lowest sm:px-5">
      <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
        <div className="flex min-w-0 items-center gap-4">
          <div
            className={`flex size-11 shrink-0 items-center justify-center rounded-[10px] border text-[11px] font-semibold uppercase tracking-[0.22em] ${getIconTileClasses(
              project.status,
            )}`}
          >
            <span className="max-w-full truncate px-1">{getProjectGlyph(project.iconKey)}</span>
          </div>

          <div className="min-w-0">
            <Link
              href={`/app/projects/${project.id}`}
              className="block max-w-full truncate text-[16px] font-semibold tracking-tight text-foreground transition-colors hover:text-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
            >
              {project.name}
            </Link>

            <div className="mt-1 flex flex-wrap items-center gap-x-3 gap-y-2 text-[13px] text-muted-foreground">
              <span className="inline-flex items-center gap-1.5">
                {CalendarIcon()}
                Created {formatCreatedAt(project.createdAt)}
              </span>
            </div>
          </div>
        </div>

        <div className="flex min-w-0 flex-col items-start gap-3 lg:items-end">
          <div className="flex items-center gap-3">
            <Badge variant={getStatusVariant(project.status)} className="rounded-full px-2.5 py-1 text-[10px] tracking-[0.18em]">
              {getStatusLabel(project.status)}
            </Badge>

            <Link
              href={`/app/projects/${project.id}`}
              className="inline-flex items-center gap-1.5 text-[15px] font-semibold text-primary transition-colors hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
            >
              View Details
              {ChevronIcon()}
            </Link>
          </div>

          <div className="flex items-center gap-2 opacity-0 transition-opacity group-hover:opacity-100 group-focus-within:opacity-100">
            {project.status === 'active' ? (
              <Button
                variant="outline"
                size="sm"
                disabled={rowActionDisabled}
                className="h-8 rounded-[10px] border-border/60 px-3 text-xs font-semibold"
                onClick={onArchive}
              >
                Archive
              </Button>
            ) : null}

            <Button
              variant="ghost"
              size="sm"
              disabled={rowActionDisabled}
              className="h-8 rounded-[10px] px-3 text-xs font-semibold text-muted-foreground hover:text-foreground"
              onClick={onDelete}
            >
              Delete
            </Button>
          </div>
        </div>
      </div>

      {activeAction !== null ? (
        <div className="mt-4 rounded-[12px] border border-border/60 bg-muted/35 p-4">
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
        <Alert variant="destructive" className="mt-4 rounded-[12px] border border-destructive/15 bg-destructive/5">
          {actionError.action === 'archive' ? 'Archive project failed.' : 'Delete project failed.'}{' '}
          {actionError.message}
        </Alert>
      ) : null}
    </article>
  )
}

function ProjectsSummary({
  totalCount,
  activeCount,
  archivedCount,
}: {
  readonly totalCount: number
  readonly activeCount: number
  readonly archivedCount: number
}) {
  return (
    <section className="grid gap-4 lg:grid-cols-[1.65fr_1fr]">
      <div className="rounded-[16px] bg-[var(--navy-700)] p-6 text-white shadow-sm">
        <div className="space-y-1">
          <p className="text-[11px] font-semibold uppercase tracking-[0.22em] text-white/75">
            Quick Stats
          </p>
          <h3 className="text-[22px] font-medium tracking-tight">Portfolio Overview</h3>
        </div>

        <div className="mt-8 grid grid-cols-3 gap-4">
          <div>
            <span className="block text-[11px] font-semibold uppercase tracking-[0.18em] text-white/70">
              Total Projects
            </span>
            <span className="mt-1 block text-[40px] font-extrabold leading-none">{totalCount}</span>
          </div>
          <div>
            <span className="block text-[11px] font-semibold uppercase tracking-[0.18em] text-white/70">
              Active
            </span>
            <span className="mt-1 block text-[40px] font-extrabold leading-none">{activeCount}</span>
          </div>
          <div>
            <span className="block text-[11px] font-semibold uppercase tracking-[0.18em] text-white/70">
              Archived
            </span>
            <span className="mt-1 block text-[40px] font-extrabold leading-none">{archivedCount}</span>
          </div>
        </div>
      </div>

        <div className="rounded-[16px] border border-border/60 bg-card p-6 shadow-sm">
          <div className="space-y-1">
            <p className="text-[11px] font-semibold uppercase tracking-[0.22em] text-primary">
              Workspace Tip
            </p>
            <h3 className="text-[22px] font-medium tracking-tight text-foreground">Efficient Archiving</h3>
        </div>
        <p className="mt-4 max-w-sm text-sm leading-7 text-muted-foreground">
          Archiving projects keeps your list clean while preserving history for future audits.
        </p>
      </div>
    </section>
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

  const projectCount =
    viewState.status === 'ready'
      ? viewState.projects.length
      : viewState.status === 'empty'
        ? 0
        : null
  const showProjectCount = projectCount !== null
  const readyProjects = viewState.status === 'ready' ? viewState.projects : []
  const activeProjectCount = readyProjects.filter((project) => project.status === 'active').length
  const archivedProjectCount = readyProjects.filter((project) => project.status === 'archived').length

  return (
    <div className="w-full max-w-[1240px] space-y-6 lg:space-y-8">
      <section className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div className="space-y-2">
          <p className="text-[11px] font-semibold uppercase tracking-[0.22em] text-muted-foreground">
            Workspace
          </p>
          <div className="space-y-2">
            <h1 className="text-[36px] font-semibold tracking-tight text-foreground sm:text-[40px]">
              Projects
            </h1>
            <p className="max-w-2xl text-sm leading-6 text-muted-foreground sm:text-base">
              Manage and track your active workspace initiatives.
            </p>
          </div>
        </div>

        <div className="flex flex-wrap items-center gap-3 lg:justify-end">
          {showProjectCount ? (
            <Badge variant="neutral" className="rounded-full px-3 py-1.5 text-[11px] font-semibold">
              {projectCount} PROJECTS
            </Badge>
          ) : null}
          <CreateProjectAction onClick={handleCreateProject} />
        </div>
      </section>

      {viewState.status === 'loading' ? <ProjectsListSkeleton /> : null}

      {viewState.status !== 'loading' ? (
        <section
          aria-label="Project filters"
          className="flex flex-col gap-4 rounded-[12px] border border-border/60 bg-card px-4 py-4 shadow-sm lg:flex-row lg:items-center lg:justify-between sm:px-5"
        >
          <div className="flex flex-wrap items-center gap-2">
            <FilterChip label="Status:" value="All Projects" />
            <FilterChip label="Scope:" value="Workspace" />
          </div>

          <div className="text-sm text-muted-foreground">
            Showing <strong className="font-semibold text-foreground">{projectCount ?? 0}</strong> active
            projects
          </div>
        </section>
      ) : null}

      {viewState.status === 'error' ? (
        <ProjectsListNotice
          title="Unable to load projects"
          description={viewState.message}
          icon="!"
          tone="destructive"
          action={
            <Button
              onClick={() => {
                setViewState({ status: 'loading' })
                setReloadToken((value) => value + 1)
              }}
              variant="secondary"
              className="h-11 rounded-[14px] px-5 text-sm font-semibold"
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
        <>
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

          <ProjectsSummary
            totalCount={readyProjects.length}
            activeCount={activeProjectCount}
            archivedCount={archivedProjectCount}
          />
        </>
      ) : null}
    </div>
  )
}
