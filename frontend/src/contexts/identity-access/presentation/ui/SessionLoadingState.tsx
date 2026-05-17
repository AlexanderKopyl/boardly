'use client'

import { Card } from '@/shared/ui/Card'
import { Skeleton } from '@/shared/ui/Skeleton'

export function SessionLoadingState() {
  return (
    <section className="flex min-h-screen items-center justify-center bg-background px-4 py-8" aria-busy="true">
      <Card className="w-full max-w-md space-y-6 p-6 sm:p-8">
        <div className="space-y-2">
          <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">
            Boardly
          </p>
          <h1 className="text-2xl font-semibold tracking-tight text-foreground">
            Checking your session
          </h1>
          <p className="text-sm leading-6 text-muted-foreground">
            Please wait while Boardly restores your authenticated session.
          </p>
        </div>
        <Skeleton className="h-12 w-full" />
        <Skeleton className="h-24 w-full" />
      </Card>
    </section>
  )
}
