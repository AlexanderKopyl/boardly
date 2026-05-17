'use client'

import { usePathname } from 'next/navigation'
import type { ReactElement, ReactNode } from 'react'

import { ProtectedRoute } from '@/contexts/identity-access/presentation/guards/ProtectedRoute'
import { LogoutButton } from '@/contexts/identity-access/presentation/ui/LogoutButton'
import { SidebarAccountCard } from '@/contexts/identity-access/presentation/ui/SidebarAccountCard'
import { Badge } from '@/shared/ui/Badge'
import { AppShell } from '@/shared/ui/AppShell'
import { SidebarNav, type SidebarNavSection } from '@/shared/ui/SidebarNav'

function isProjectsRoute(pathname: string): boolean {
  return pathname === '/app/projects' || pathname.startsWith('/app/projects/')
}

function buildNavigationSections(pathname: string): SidebarNavSection[] {
  return [
    {
      label: 'Main',
      items: [
        {
          label: 'Projects',
          href: '/app/projects',
          current: isProjectsRoute(pathname),
        },
        {
          label: 'Dashboard',
          href: '/app/dashboard',
          current: pathname === '/app/dashboard',
        },
        {
          label: 'My tasks',
          href: '/app/dashboard',
          disabled: true,
        },
      ],
    },
    {
      label: 'Work',
      items: [
        {
          label: 'Boards',
          href: '/app/dashboard',
          disabled: true,
        },
        {
          label: 'Calendar',
          href: '/app/dashboard',
          disabled: true,
        },
        {
          label: 'Settings',
          href: '/app/dashboard',
          disabled: true,
        },
      ],
    },
  ]
}

function WorkspaceSidebar({ pathname }: { pathname: string }): ReactElement {
  const navigationSections = buildNavigationSections(pathname)

  return (
    <div className="flex h-full flex-col gap-6 p-4">
      <div className="space-y-6">
        <div className="flex items-center gap-3 rounded-3xl border border-[color:var(--sidebar-border)] bg-white/5 px-4 py-3">
          <div className="flex size-11 shrink-0 items-center justify-center rounded-2xl bg-[var(--sidebar-accent)] text-base font-semibold text-[var(--sidebar-foreground)]">
            B
          </div>
          <div className="min-w-0">
            <div className="text-sm font-semibold text-[var(--sidebar-foreground)]">Boardly</div>
            <div className="text-xs text-[var(--sidebar-muted)]">Project delivery workspace</div>
          </div>
        </div>

        <section className="space-y-3">
          <div className="flex items-center justify-between rounded-2xl border border-[color:var(--sidebar-border)] bg-white/5 px-4 py-3">
            <div className="min-w-0">
              <div className="text-sm font-semibold text-[var(--sidebar-foreground)]">
                Northstar Studio
              </div>
              <div className="text-xs text-[var(--sidebar-muted)]">12 active projects</div>
            </div>
            <Badge
              className="border-white/10 bg-white/10 text-[var(--sidebar-foreground)]"
              variant="neutral"
            >
              Main
            </Badge>
          </div>
        </section>

        <SidebarNav label="Main navigation" sections={navigationSections} />
      </div>

      <div className="space-y-3">
        <SidebarAccountCard />
        <LogoutButton />
      </div>
    </div>
  )
}

export function ProtectedWorkspaceShell({ children }: { children: ReactNode }): ReactElement {
  const pathname = usePathname()

  return (
    <ProtectedRoute>
      <AppShell sidebar={<WorkspaceSidebar pathname={pathname} />}>{children}</AppShell>
    </ProtectedRoute>
  )
}
