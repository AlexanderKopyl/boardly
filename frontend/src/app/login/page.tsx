import Link from 'next/link'

import { LoginForm } from '@/contexts/identity-access/presentation/ui/LoginForm'
import { Badge } from '@/shared/ui/Badge'
import { PageHeader } from '@/shared/ui/PageHeader'

export default function LoginPage() {
  return (
    <main className="auth-page">
      <div className="auth-page__grid">
        <section className="auth-page__hero" aria-label="Boardly overview">
          <div className="auth-page__hero-meta">
            <p className="auth-page__eyebrow">Boardly workspace</p>
            <h1 className="auth-page__hero-title">Keep the first app shell focused.</h1>
            <p className="auth-page__hero-copy">
              Sign in to the protected dashboard, validate the auth flow, and keep the
              next product slices behind the secure session boundary.
            </p>
          </div>

          <div className="auth-page__hero-note">
            <Badge variant="info">Memory-only access token</Badge>
            <span>Refresh stays browser-managed and never leaves the HttpOnly cookie.</span>
          </div>
        </section>

        <section className="auth-page__panel" aria-labelledby="login-page-title">
          <div className="auth-page__panel-inner">
            <PageHeader
              eyebrow="Sign in"
              title="Welcome back"
              description="Use the Boardly account tied to your workspace."
              id="login-page-title"
            />
            <LoginForm />
            <div className="auth-page__footer">
              <span>Need an account?</span>
              <Link href="/register">Request access</Link>
            </div>
          </div>
        </section>
      </div>
    </main>
  )
}
