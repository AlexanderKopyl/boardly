import Link from 'next/link'

import { Badge } from '@/shared/ui/Badge'
import { EmptyState } from '@/shared/ui/EmptyState'

export default function PendingApprovalPage() {
  return (
    <main className="flex min-h-screen items-center justify-center bg-background px-4 py-8 sm:px-6 lg:px-8">
      <section className="w-full">
        <EmptyState
          icon={<Badge variant="warning">Pending approval</Badge>}
          title="Account request received"
          description="An administrator needs to approve the account before sign-in is allowed."
          actions={<Link href="/login">Return to login</Link>}
        />
      </section>
    </main>
  )
}
