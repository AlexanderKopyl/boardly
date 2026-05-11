import Link from 'next/link'

import { EmptyState } from '@/shared/ui/EmptyState'

export default function PendingApprovalPage() {
  return (
    <main>
      <EmptyState
        title="Account pending approval"
        description="Your account request has been received and is waiting for administrator approval."
        actions={<Link href="/login">Return to login</Link>}
      />
    </main>
  )
}
