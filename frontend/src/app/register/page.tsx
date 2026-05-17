import Link from 'next/link'

import { RegisterForm } from '@/contexts/identity-access/presentation/ui/RegisterForm'
import { Badge } from '@/shared/ui/Badge'
import { Card } from '@/shared/ui/Card'
import { PageHeader } from '@/shared/ui/PageHeader'

export default function RegisterPage() {
  return (
    <main className="relative min-h-screen overflow-hidden bg-[linear-gradient(180deg,rgba(37,99,235,0.10),transparent_26%),linear-gradient(225deg,rgba(15,23,42,0.06),transparent_42%),var(--background)] px-4 py-8 sm:px-6 lg:px-8 lg:py-12">
      <div className="pointer-events-none absolute inset-0 overflow-hidden">
        <div className="absolute left-[-8rem] top-[-6rem] h-64 w-64 rounded-full bg-primary/10 blur-3xl" />
        <div className="absolute right-[-6rem] top-1/3 h-72 w-72 rounded-full bg-accent/60 blur-3xl" />
      </div>
      <div className="relative mx-auto grid min-h-[calc(100vh-4rem)] w-full max-w-6xl items-stretch gap-8 lg:grid-cols-[1.05fr_0.95fr]">
        <section
          className="flex flex-col justify-between rounded-[2rem] border border-border/70 bg-card/75 p-6 shadow-sm backdrop-blur-sm sm:p-8 lg:p-10"
          aria-label="Boardly access request"
        >
          <div className="space-y-8">
            <div className="space-y-4">
              <Badge variant="warning" className="w-fit">
                Pending approval
              </Badge>
              <div className="space-y-3">
                <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">
                  Boardly access
                </p>
                <h1 className="max-w-xl text-4xl font-semibold tracking-tight text-foreground sm:text-5xl">
                  Request a workspace account.
                </h1>
                <p className="max-w-xl text-sm leading-6 text-muted-foreground sm:text-base">
                  Boardly keeps approval, identity lifecycle, and session handling on the backend
                  while the frontend stays lightweight and reversible.
                </p>
              </div>
            </div>

            <Card className="max-w-xl space-y-3 bg-background/70 p-5 shadow-none">
              <div className="flex items-center gap-3">
                <Badge variant="warning">Pending approval</Badge>
              </div>
              <p className="text-sm leading-6 text-muted-foreground">
                Registration creates an account request, not an active session.
              </p>
            </Card>
          </div>

          <p className="mt-8 text-sm text-muted-foreground">
            Approval and session lifecycle remain server-owned so this screen only collects the
            request.
          </p>
        </section>

        <section className="flex items-center" aria-labelledby="register-page-title">
          <Card className="w-full rounded-[2rem] border-border/70 bg-card/90 p-6 shadow-xl shadow-slate-200/60 backdrop-blur sm:p-8 lg:p-10">
            <div className="space-y-6">
              <PageHeader
                eyebrow="Create account"
                title="Request access"
                description="Use your name, email, and a strong password to request a Boardly account."
                id="register-page-title"
              />
              <RegisterForm />
              <div className="flex items-center justify-between border-t border-border/70 pt-4 text-sm text-muted-foreground">
                <span>Already waiting for approval?</span>
                <Link
                  className="font-medium text-primary hover:underline"
                  href="/pending-approval"
                >
                  See the approval page
                </Link>
              </div>
            </div>
          </Card>
        </section>
      </div>
    </main>
  )
}
