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

function getIconTileClasses(status: string): string {
  if (status === 'active') {
    return 'border-primary/15 bg-primary/10 text-primary'
  }

  if (status === 'archived') {
    return 'border-border/40 bg-secondary-container text-secondary'
  }

  if (status === 'deleted') {
    return 'border-destructive/20 bg-destructive/10 text-destructive'
  }

  return 'border-border/40 bg-muted text-muted-foreground'
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
  return (
    <svg aria-hidden="true" viewBox="0 0 24 24" className="size-5" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M5 12h14" />
      <path d="M12 5v14" />
    </svg>
  )
}

function ChevronIcon(): ReactNode {
  return (
    <svg aria-hidden="true" viewBox="0 0 24 24" className="size-4">
      <path fill="currentColor" d="m9.25 18.75-.75-.75 6-6-6-6 .75-.75 6.75 6.75-6.75 6.75Z" />
    </svg>
  )
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
    <div className="flex items-center gap-1 rounded-md border border-[#c5c5d5] bg-[#e8eeff] px-2 py-1">
      <span className="text-[11px] font-bold uppercase tracking-wider text-[#5e656d]">
        {label}
      </span>
      <select className="cursor-pointer border-none bg-transparent p-0 pr-6 text-sm font-bold text-[#141c2a] focus:ring-0">
        <option>{value}</option>
      </select>
    </div>
  )
}

function CreateProjectAction({ onClick }: { readonly onClick: () => void }) {
  return (
    <Button
      variant="primary"
      onClick={onClick}
      className="flex h-[40px] items-center gap-2 rounded-lg bg-primary px-6 py-2 font-bold text-primary-foreground shadow-sm transition-all hover:opacity-90 active:scale-95"
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
          ? 'rounded-lg border border-border bg-destructive/5 px-4 py-4'
          : 'rounded-lg border border-border bg-card px-4 py-4 shadow-sm'
      }
    >
      <div className="flex flex-col items-start gap-3 md:flex-row md:items-center md:justify-between">
        <div className="flex min-w-0 items-start gap-4">
          <div className="flex size-12 shrink-0 items-center justify-center rounded-[10px] border border-border/40 bg-muted text-xs font-semibold uppercase tracking-[0.18em] text-muted-foreground">
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
      <div className="rounded-xl border border-border/40 bg-card p-4 shadow-sm sm:p-5">
        <div className="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
          <div className="flex flex-wrap items-center gap-2">
            <Skeleton className="h-10 w-48 rounded-[14px]" />
            <Skeleton className="h-10 w-52 rounded-[14px]" />
          </div>
          <Skeleton className="h-4 w-40 rounded-full lg:ml-auto" />
        </div>
      </div>

      <div className="space-y-2">
        {Array.from({ length: 4 }).map((_, index) => (
          <article
            key={`project-skeleton-${index}`}
            className="rounded-xl border border-border/40 bg-card p-4 shadow-sm sm:p-5"
          >
            <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
              <div className="flex min-w-0 items-center gap-6">
                <Skeleton className="size-12 shrink-0 rounded-lg" />
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
              </div>
            </div>
          </article>
        ))}
      </div>

      <div className="grid gap-6 lg:grid-cols-[1.65fr_1fr]">
        <div className="rounded-xl bg-primary p-6 text-primary-foreground shadow-sm">
          <Skeleton className="h-3 w-28 rounded-full bg-white/25" />
          <Skeleton className="mt-2 h-6 w-40 rounded-md bg-white/20" />
          <div className="mt-6 grid grid-cols-3 gap-4">
            <div className="space-y-1.5">
              <Skeleton className="h-3 w-20 rounded-full bg-white/20" />
              <Skeleton className="h-8 w-12 rounded-md bg-white/20" />
            </div>
            <div className="space-y-1.5">
              <Skeleton className="h-3 w-20 rounded-full bg-white/20" />
              <Skeleton className="h-8 w-12 rounded-md bg-white/20" />
            </div>
            <div className="space-y-1.5">
              <Skeleton className="h-3 w-20 rounded-full bg-white/20" />
              <Skeleton className="h-8 w-12 rounded-md bg-white/20" />
            </div>
          </div>
        </div>

        <div className="rounded-xl border border-border/40 bg-card p-6 shadow-sm">
          <Skeleton className="h-3 w-32 rounded-full" />
          <Skeleton className="mt-2 h-6 w-40 rounded-md" />
          <Skeleton className="mt-3 h-12 w-full rounded-md" />
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
    <article className="group rounded-xl border border-[#c5c5d5] bg-white p-4 transition-all hover:border-[#1d389d] hover:bg-[#f0f3ff] lg:min-h-[84px]">
      <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div className="flex min-w-0 items-center gap-6">
          <div
            className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-lg border-none text-[13px] font-bold tracking-wider ${getIconTileClasses(
              project.status,
            )}`}
          >
            <span className="max-w-full truncate px-1">{getProjectGlyph(project.iconKey)}</span>
          </div>

          <div className="min-w-0">
            <Link
              href={`/app/projects/${project.id}`}
              className="block max-w-full truncate text-[17px] font-bold text-foreground transition-colors hover:text-primary hover:underline focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background"
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

        <div className="flex min-w-0 items-center gap-4 lg:justify-end">
          <div className="flex items-center gap-2 opacity-0 transition-opacity group-hover:opacity-100 group-focus-within:opacity-100">
            {project.status === 'active' ? (
              <Button
                variant="outline"
                size="sm"
                disabled={rowActionDisabled}
                className="h-8 rounded-md border-border/40 px-3 text-xs font-semibold"
                onClick={onArchive}
              >
                Archive
              </Button>
            ) : null}

            <Button
              variant="ghost"
              size="sm"
              disabled={rowActionDisabled}
              className="h-8 rounded-md px-3 text-xs font-semibold text-muted-foreground hover:text-foreground"
              onClick={onDelete}
            >
              Delete
            </Button>
          </div>

          <div className="flex items-center gap-8">
            <span className="rounded-full border border-green-200 bg-green-100 px-2 py-1 text-[11px] font-bold uppercase tracking-wider text-green-800">
              {getStatusLabel(project.status)}
            </span>

            <Link
              href={`/app/projects/${project.id}`}
              className="group/link flex items-center gap-1 text-[13px] font-bold text-primary transition-all hover:underline"
            >
              View Details
              {ChevronIcon()}
            </Link>
          </div>
        </div>
      </div>

      {activeAction !== null ? (
        <div className="mt-4 rounded-[12px] border border-border/40 bg-muted/35 p-4">
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
        <Alert variant="destructive" className="mt-4 rounded-lg border border-destructive/15 bg-destructive/5">
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
    <section className="mt-8 grid gap-6 lg:grid-cols-3">
      <div className="rounded-xl bg-[#1d389d] p-6 text-white lg:col-span-2 flex flex-col justify-between min-h-[160px]">
        <div className="space-y-1">
          <p className="text-[11px] font-bold uppercase tracking-[0.12em] text-white/80">
            Quick Stats
          </p>
          <h3 className="text-[22px] font-bold tracking-tight">Portfolio Overview</h3>
        </div>

        <div className="mt-6 grid grid-cols-3 gap-4">
          <div>
            <span className="block text-xs font-semibold uppercase tracking-wider text-white/60">
              Total Projects
            </span>
            <span className="mt-1 block text-3xl font-extrabold leading-none tracking-tight">{totalCount}</span>
          </div>
          <div>
            <span className="block text-xs font-semibold uppercase tracking-wider text-white/60">
              Active
            </span>
            <span className="mt-1 block text-3xl font-extrabold leading-none tracking-tight">{activeCount}</span>
          </div>
          <div>
            <span className="block text-xs font-semibold uppercase tracking-wider text-white/60">
              Archived
            </span>
            <span className="mt-1 block text-3xl font-extrabold leading-none tracking-tight">{archivedCount}</span>
          </div>
        </div>
      </div>

      <div className="rounded-xl border border-[#c5c5d5] bg-[#f0f3ff] p-6 relative overflow-hidden group flex flex-col justify-between min-h-[160px]">
        <div className="relative z-10 space-y-1">
          <p className="text-[11.5px] font-bold uppercase tracking-[0.12em] text-[#00207e]">
            Workspace Tip
          </p>
          <h3 className="text-[17px] font-bold text-[#141c2a]">Efficient Archiving</h3>
        </div>
        <p className="mt-4 max-w-sm text-sm leading-7 text-[#585f67]">
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

  const readyProjects = viewState.status === 'ready' ? viewState.projects : []
  const activeProjectCount = readyProjects.filter((project) => project.status === 'active').length
  const archivedProjectCount = readyProjects.filter((project) => project.status === 'archived').length

  return (
    <div className="w-full">
      <section className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between mb-8">
        <div className="space-y-1">
          <h1 className="text-3xl font-bold tracking-tight text-[#141c2a] sm:text-[32px]">
            Projects
          </h1>
          <p className="max-w-2xl text-sm leading-6 text-[#585f67]">
            Manage and track your active workspace initiatives.
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-3">
          <CreateProjectAction onClick={handleCreateProject} />
        </div>
      </section>

      {viewState.status === 'loading' ? <ProjectsListSkeleton /> : null}

      {viewState.status !== 'loading' ? (
        <section
          aria-label="Project filters"
          className="mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-[#c5c5d5] bg-white p-4"
        >
          <div className="flex flex-wrap items-center gap-2">
            <FilterChip label="Status:" value="All Projects" />
            <FilterChip label="Scope:" value="Workspace" />
          </div>

          <div className="text-sm font-semibold text-[#585f67]">
            Showing <strong className="text-[#141c2a]">{activeProjectCount}</strong> active projects
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
          <section aria-label="Project list" className="space-y-2">
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
