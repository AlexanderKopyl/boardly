'use client'

import Link from 'next/link'
import { usePathname } from 'next/navigation'
import type { ReactElement, ReactNode } from 'react'

import { ProtectedRoute } from '@/contexts/identity-access/presentation/guards/ProtectedRoute'
import { AppShell } from '@/shared/ui/AppShell'
import { SidebarNav, type SidebarNavSection } from '@/shared/ui/SidebarNav'

function isProjectsRoute(pathname: string): boolean {
  return pathname === '/app/projects' || pathname.startsWith('/app/projects/')
}

function ProjectsIcon(): ReactElement {
  return (
    <svg aria-hidden="true" viewBox="0 0 24 24" className="h-4 w-4">
      <path
        fill="currentColor"
        d="M4 4h6v6H4V4Zm10 0h6v6h-6V4ZM4 14h6v6H4v-6Zm10 0h6v6h-6v-6Z"
      />
    </svg>
  )
}

function SettingsIcon(): ReactElement {
  return (
    <svg aria-hidden="true" viewBox="0 0 24 24" className="h-4 w-4">
      <path
        fill="currentColor"
        d="m19.14 12.94.04-.94-.04-.94 2.06-1.61-1.96-3.4-2.49.5a7.6 7.6 0 0 0-1.64-.95l-.38-2.53H9.27l-.38 2.53c-.57.22-1.12.53-1.64.95l-2.49-.5-1.96 3.4 2.06 1.61-.04.94.04.94-2.06 1.61 1.96 3.4 2.49-.5c.52.42 1.07.73 1.64.95l.38 2.53h4.63l.38-2.53c.57-.22 1.12-.53 1.64-.95l2.49.5 1.96-3.4-2.06-1.61ZM12 15.5A3.5 3.5 0 1 1 12 8a3.5 3.5 0 0 1 0 7.5Z"
      />
    </svg>
  )
}

function SearchIcon(): ReactElement {
  return (
    <svg aria-hidden="true" viewBox="0 0 24 24" className="h-4 w-4">
      <path
        fill="currentColor"
        d="M10.5 4a6.5 6.5 0 1 0 4.15 11.52l4.16 4.16 1.41-1.41-4.16-4.16A6.5 6.5 0 0 0 10.5 4Zm0 2a4.5 4.5 0 1 1 0 9 4.5 4.5 0 0 1 0-9Z"
      />
    </svg>
  )
}

function BellIcon(): ReactElement {
  return (
    <svg aria-hidden="true" viewBox="0 0 24 24" className="h-4 w-4">
      <path
        fill="currentColor"
        d="M12 22a2.5 2.5 0 0 0 2.45-2h-4.9A2.5 2.5 0 0 0 12 22Zm7-6V11a7 7 0 1 0-14 0v5L3 18v1h18v-1l-2-2Z"
      />
    </svg>
  )
}

function HelpIcon(): ReactElement {
  return (
    <svg aria-hidden="true" viewBox="0 0 24 24" className="h-4 w-4">
      <path
        fill="currentColor"
        d="M12 2a10 10 0 1 0 10 10A10 10 0 0 0 12 2Zm0 17a1.25 1.25 0 1 1 1.25-1.25A1.25 1.25 0 0 1 12 19Zm1.25-5.55v.55h-2.5v-1a3 3 0 0 1 1.62-2.66c.74-.42 1.13-.78 1.13-1.54a1.5 1.5 0 0 0-3 0H8a4 4 0 0 1 8 0c0 1.81-1.1 2.73-2.75 3.65-.55.31-.75.56-.75 1Z"
      />
    </svg>
  )
}

function WorkspaceTopbar(): ReactElement {
  return (
    <header className="flex min-h-16 items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
      <div className="flex min-w-0 items-center gap-4">
        <div className="min-w-0">
          <div className="flex items-center gap-3">
            <span className="text-[17px] font-semibold tracking-tight text-primary">Projects</span>
            <nav aria-label="Workspace breadcrumb" className="hidden items-center gap-2 text-sm md:flex">
              <Link href="/app/projects" className="font-medium text-primary transition-colors">
                Projects
              </Link>
              <span className="text-muted-foreground">/</span>
              <span className="text-muted-foreground">Current Project</span>
            </nav>
          </div>
        </div>
      </div>

      <div className="flex items-center gap-3">
        <label className="relative hidden md:block">
          <span className="sr-only">Search projects</span>
          <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-muted-foreground">
            <SearchIcon />
          </span>
          <input
            className="h-10 w-72 rounded-full border border-border/40 bg-background pl-10 pr-4 text-sm text-foreground placeholder:text-muted-foreground/80 shadow-none outline-none transition-colors focus:border-primary/[0.35] focus:ring-2 focus:ring-primary/[0.15]"
            placeholder="Search projects..."
            type="search"
          />
        </label>

        <button
          type="button"
          aria-label="Notifications"
          className="inline-flex size-9 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
        >
          <BellIcon />
        </button>

        <button
          type="button"
          aria-label="Help"
          className="inline-flex size-9 items-center justify-center rounded-full text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
        >
          <HelpIcon />
        </button>

        <div className="flex size-9 items-center justify-center rounded-full border border-border/40 bg-card text-[11px] font-semibold tracking-[0.08em] text-secondary-foreground shadow-none">
          AK
        </div>
      </div>
    </header>
  )
}

function buildNavigationSections(pathname: string): SidebarNavSection[] {
  return [
    {
      items: [
        {
          label: 'Projects',
          href: '/app/projects',
          current: isProjectsRoute(pathname),
          icon: <ProjectsIcon />,
        },
        {
          label: 'Workspace Settings',
          href: '/app/dashboard',
          disabled: true,
          icon: <SettingsIcon />,
        },
      ],
    },
  ]
}

function WorkspaceSidebar({ pathname }: { pathname: string }): ReactElement {
  const navigationSections = buildNavigationSections(pathname)

  return (
    <div className="flex h-full flex-col px-6 py-5">
      <div className="mb-8">
        <div className="mb-1 flex items-center gap-2">
          <div className="grid size-8 shrink-0 grid-cols-2 gap-[2px] rounded bg-white p-1.5 text-primary">
            <span className="rounded-[1px] bg-current" />
            <span className="rounded-[1px] bg-current" />
            <span className="rounded-[1px] bg-current" />
            <span className="rounded-[1px] bg-current" />
          </div>
          <h1 className="text-[17px] font-bold text-white">Boardly</h1>
        </div>
        <p className="px-1 text-xs text-white/50">Management Hub</p>
      </div>

      <div className="mt-6 flex min-h-0 flex-1 flex-col">
        <SidebarNav
          label="Main navigation"
          sections={navigationSections}
          className="space-y-3"
        />
        <div className="mt-auto pt-6">
          <Link
            href="/app/projects/new"
            className="inline-flex h-10 w-full items-center justify-center rounded-lg bg-white/10 px-4 py-2 text-sm font-bold text-white transition-all hover:bg-white/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/40 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent"
          >
            New Project
          </Link>
        </div>
      </div>
    </div>
  )
}

export function ProtectedWorkspaceShell({ children }: { children: ReactNode }): ReactElement {
  const pathname = usePathname()

  return (
    <ProtectedRoute>
      <AppShell sidebar={<WorkspaceSidebar pathname={pathname} />} header={<WorkspaceTopbar />} sidebarClassName="!bg-primary !text-white !border-none">
        {children}
      </AppShell>
    </ProtectedRoute>
  )
}
