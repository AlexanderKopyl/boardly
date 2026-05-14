'use client'

import { Skeleton } from '@/shared/ui/Skeleton'

export function SessionLoadingState() {
  return (
    <section className="auth-loading" aria-busy="true">
      <div className="auth-loading__card">
        <div className="auth-loading__copy">
          <p className="auth-page__eyebrow">Boardly</p>
          <h1>Checking your session</h1>
          <p>Please wait while Boardly restores your authenticated session.</p>
        </div>
        <Skeleton style={{ height: '3rem' }} />
        <Skeleton style={{ height: '5.5rem' }} />
      </div>
    </section>
  )
}
