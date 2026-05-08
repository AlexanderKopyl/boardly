import { ProtectedRoute } from '@/contexts/identity-access/presentation/guards/ProtectedRoute'
import { LogoutButton } from '@/contexts/identity-access/presentation/ui/LogoutButton'

export default function DashboardPage() {
  return (
    <ProtectedRoute>
      <main>
        <h1>Dashboard</h1>
        <p>Dashboard placeholder</p>
        <LogoutButton />
      </main>
    </ProtectedRoute>
  )
}
