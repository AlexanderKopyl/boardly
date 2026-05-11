import { EmptyState } from '@/shared/ui/EmptyState'
import { Skeleton } from '@/shared/ui/Skeleton'

export default function SessionLoadingPage() {
  return (
    <main aria-busy="true">
      <EmptyState
        title="Checking your session"
        description="Please wait while Boardly verifies your sign-in state."
        icon={<Skeleton className="ui-session-loading__skeleton" />}
      />
    </main>
  )
}
