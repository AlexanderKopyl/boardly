import Link from 'next/link'

import { Badge } from '@/shared/ui/Badge'
import { EmptyState } from '@/shared/ui/EmptyState'

export default function PendingApprovalPage() {
  return (
    <main className="ui-auth-page">
      <section className="ui-auth-page__panel">
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
