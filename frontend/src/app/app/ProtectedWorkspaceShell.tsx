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
    <div className="ui-app-shell__sidebar-content">
      <div className="ui-app-shell__sidebar-primary">
        <div className="ui-app-shell__sidebar-brand">
          <div className="ui-app-shell__sidebar-brand-mark">B</div>
          <div className="ui-app-shell__sidebar-brand-copy">
            <div className="ui-app-shell__sidebar-brand-label">Boardly</div>
            <div className="ui-app-shell__sidebar-brand-subtitle">
              Project delivery workspace
            </div>
          </div>
        </div>

        <section className="ui-app-shell__sidebar-group">
          <div className="ui-app-shell__workspace-card">
            <div className="ui-app-shell__workspace-card-copy">
              <div className="ui-app-shell__workspace-name">Northstar Studio</div>
              <div className="ui-app-shell__workspace-meta">12 active projects</div>
            </div>
            <Badge className="ui-app-shell__workspace-badge" variant="neutral">
              Main
            </Badge>
          </div>
        </section>

        <SidebarNav label="Main navigation" sections={navigationSections} />
      </div>

      <div className="ui-app-shell__sidebar-footer">
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
