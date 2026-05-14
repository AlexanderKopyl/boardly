import Link from 'next/link'

import { RegisterForm } from '@/contexts/identity-access/presentation/ui/RegisterForm'
import { Badge } from '@/shared/ui/Badge'
import { PageHeader } from '@/shared/ui/PageHeader'

export default function RegisterPage() {
  return (
    <main className="auth-page">
      <div className="auth-page__grid">
        <section className="auth-page__hero" aria-label="Boardly access request">
          <div className="auth-page__hero-meta">
            <p className="auth-page__eyebrow">Boardly access</p>
            <h1 className="auth-page__hero-title">Request a workspace account.</h1>
            <p className="auth-page__hero-copy">
              Boardly keeps approval, identity lifecycle, and session handling on the
              backend while the frontend stays lightweight and reversible.
            </p>
          </div>

          <div className="auth-page__hero-note">
            <Badge variant="warning">Pending approval</Badge>
            <span>Registration creates an account request, not an active session.</span>
          </div>
        </section>

        <section className="auth-page__panel" aria-labelledby="register-page-title">
          <div className="auth-page__panel-inner">
            <PageHeader
              eyebrow="Create account"
              title="Request access"
              description="Use your name, email, and a strong password to request a Boardly account."
              id="register-page-title"
            />
            <RegisterForm />
            <div className="auth-page__footer">
              <span>Already waiting for approval?</span>
              <Link href="/pending-approval">See the approval page</Link>
            </div>
          </div>
        </section>
      </div>
    </main>
  )
}
