import { ProtectedRoute } from '@/contexts/identity-access/presentation/guards/ProtectedRoute'
import { LogoutButton } from '@/contexts/identity-access/presentation/ui/LogoutButton'
import { AppShell } from '@/shared/ui/AppShell'
import { Badge } from '@/shared/ui/Badge'
import { EmptyState } from '@/shared/ui/EmptyState'
import { PageHeader } from '@/shared/ui/PageHeader'
import { SidebarNav } from '@/shared/ui/SidebarNav'

export default function AppDashboardPage() {
  return (
    <ProtectedRoute>
      <AppShell
        sidebar={
          <div className="ui-app-shell__sidebar-content">
            <div className="ui-app-shell__sidebar-brand">
              <div className="ui-app-shell__sidebar-brand-label">Boardly</div>
              <div className="ui-app-shell__sidebar-brand-subtitle">
                Protected workspace shell
              </div>
            </div>
            <SidebarNav
              label="Workspace navigation"
              items={[
                {
                  label: 'Dashboard',
                  href: '/app/dashboard',
                  description: 'Workspace overview',
                  current: true,
                },
              ]}
            />
            <div className="ui-app-shell__sidebar-footer">
              <Badge variant="success">Authenticated</Badge>
              <LogoutButton />
            </div>
          </div>
        }
        header={
          <PageHeader
            eyebrow="Workspace"
            title="Dashboard"
            description="The first authenticated screen is deliberately sparse until real projects, issues, and workflow data arrive."
            actions={<Badge variant="info">Protected route</Badge>}
          />
        }
      >
        <EmptyState
          icon={<Badge variant="neutral">MVP</Badge>}
          title="Workspace shell ready"
          description="This page verifies the app shell, protected routing, and logout flow without introducing product-specific boards or tasks yet."
        />
      </AppShell>
    </ProtectedRoute>
  )
}
