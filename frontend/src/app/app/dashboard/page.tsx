import { ProtectedRoute } from '@/contexts/identity-access/presentation/guards/ProtectedRoute'
import { LogoutButton } from '@/contexts/identity-access/presentation/ui/LogoutButton'
import { AppShell } from '@/shared/ui/AppShell'
import { EmptyState } from '@/shared/ui/EmptyState'
import { PageHeader } from '@/shared/ui/PageHeader'
import { SidebarNav } from '@/shared/ui/SidebarNav'

export default function AppDashboardPage() {
  return (
    <ProtectedRoute>
      <AppShell
        sidebar={
          <div>
            <SidebarNav
              items={[
                {
                  label: 'Dashboard',
                  href: '/app/dashboard',
                  description: 'Workspace overview',
                  current: true,
                },
              ]}
            />
          </div>
        }
        header={
          <PageHeader
            eyebrow="Workspace"
            title="Dashboard"
            description="The Boardly app shell is in place. Project, issue, and workflow surfaces can now plug into it."
            actions={<LogoutButton />}
          />
        }
      >
        <EmptyState
          title="Workspace shell ready"
          description="This screen is intentionally sparse until the product contexts start rendering real data."
        />
      </AppShell>
    </ProtectedRoute>
  )
}
