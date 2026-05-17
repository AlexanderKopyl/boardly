import Link from 'next/link'

import { LoginForm } from '@/contexts/identity-access/presentation/ui/LoginForm'
import { Badge } from '@/shared/ui/Badge'
import { Card } from '@/shared/ui/Card'
import { PageHeader } from '@/shared/ui/PageHeader'

export default function LoginPage() {
  return (
    <main className="relative min-h-screen overflow-hidden bg-[linear-gradient(180deg,rgba(37,99,235,0.10),transparent_26%),linear-gradient(225deg,rgba(15,23,42,0.06),transparent_42%),var(--background)] px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
      <div className="pointer-events-none absolute inset-0 overflow-hidden">
        <div className="absolute left-[-8rem] top-[-6rem] h-64 w-64 rounded-full bg-primary/10 blur-3xl" />
        <div className="absolute right-[-6rem] top-1/3 h-72 w-72 rounded-full bg-accent/60 blur-3xl" />
      </div>
      <div className="relative mx-auto grid min-h-[calc(100vh-4rem)] w-full max-w-6xl items-stretch gap-8 lg:grid-cols-[1.05fr_0.95fr]">
        <section
          className="flex flex-col justify-between rounded-[2rem] border border-border/70 bg-card/75 p-6 shadow-sm backdrop-blur-sm sm:p-8 lg:p-10"
          aria-label="Boardly overview"
        >
          <div className="space-y-8">
            <div className="space-y-4">
              <Badge variant="info" className="w-fit">
                Workspace access
              </Badge>
              <div className="space-y-3">
                <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                  Boardly workspace
                </p>
                <h1 className="max-w-xl text-4xl font-semibold tracking-tight text-foreground sm:text-5xl">
                  Keep the first app shell focused.
                </h1>
                <p className="max-w-xl text-sm leading-6 text-muted-foreground sm:text-base">
                  Sign in to the protected dashboard, validate the auth flow, and keep the next
                  product slices behind the secure session boundary.
                </p>
              </div>
            </div>

            <Card className="max-w-xl space-y-3 bg-background/70 p-5 shadow-none">
              <div className="flex items-center gap-3">
                <Badge variant="info">Memory-only access token</Badge>
              </div>
              <p className="text-sm leading-6 text-muted-foreground">
                Refresh stays browser-managed and never leaves the HttpOnly cookie.
              </p>
            </Card>
          </div>

          <p className="mt-8 text-sm text-muted-foreground">
            The shell remains lightweight now so the protected workspace can inherit the same
            token-driven styling foundation.
          </p>
        </section>

        <section className="flex items-center" aria-labelledby="login-page-title">
          <Card className="w-full rounded-[2rem] border-border/70 bg-card/90 p-6 shadow-xl shadow-slate-200/60 backdrop-blur sm:p-8 lg:p-10">
            <div className="space-y-6">
              <PageHeader
                eyebrow="Sign in"
                title="Welcome back"
                description="Use the Boardly account tied to your workspace."
                id="login-page-title"
              />
              <LoginForm />
              <div className="flex items-center justify-between border-t border-border/70 pt-4 text-sm text-muted-foreground">
                <span>Need an account?</span>
                <Link className="font-medium text-primary hover:underline" href="/register">
                  Request access
                </Link>
              </div>
            </div>
          </Card>
        </section>
      </div>
    </main>
  )
}
