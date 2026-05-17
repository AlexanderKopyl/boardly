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
            className="h-10 w-72 rounded-full border border-border/70 bg-background pl-10 pr-4 text-sm text-foreground placeholder:text-muted-foreground/80 shadow-sm outline-none transition-colors focus:border-primary/40 focus:ring-2 focus:ring-primary/20"
            placeholder="Search projects..."
            type="search"
          />
        </label>

        <button
          type="button"
          aria-label="Notifications"
          className="inline-flex size-10 items-center justify-center rounded-full border border-border/70 bg-background text-muted-foreground transition-colors hover:border-primary/30 hover:text-primary"
        >
          <BellIcon />
        </button>

        <button
          type="button"
          aria-label="Help"
          className="inline-flex size-10 items-center justify-center rounded-full border border-border/70 bg-background text-muted-foreground transition-colors hover:border-primary/30 hover:text-primary"
        >
          <HelpIcon />
        </button>

        <div className="flex size-10 items-center justify-center rounded-full border border-border/70 bg-secondary-container text-sm font-semibold text-secondary-foreground shadow-sm">
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
    <div className="flex h-full flex-col px-4 py-5">
      <div className="flex items-start gap-3 rounded-[22px] border border-white/10 bg-white/5 px-4 py-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.04)]">
        <div className="grid size-10 shrink-0 grid-cols-2 gap-1 rounded-xl bg-white p-2 text-[var(--sidebar)]">
          <span className="rounded-[2px] bg-current" />
          <span className="rounded-[2px] bg-current" />
          <span className="rounded-[2px] bg-current" />
          <span className="rounded-[2px] bg-current" />
        </div>
        <div className="min-w-0">
          <div className="text-lg font-semibold leading-none text-[var(--sidebar-foreground)]">Boardly</div>
          <div className="mt-1 text-xs text-[var(--sidebar-muted)]">Management Hub</div>
        </div>
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
            className="inline-flex h-12 w-full items-center justify-center rounded-[18px] border border-white/10 bg-white/10 px-4 text-sm font-semibold text-[var(--sidebar-foreground)] shadow-[inset_0_1px_0_rgba(255,255,255,0.06)] transition-colors hover:bg-white/20 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/50 focus-visible:ring-offset-2 focus-visible:ring-offset-transparent"
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
      <AppShell sidebar={<WorkspaceSidebar pathname={pathname} />} header={<WorkspaceTopbar />}>
        {children}
      </AppShell>
    </ProtectedRoute>
  )
}
