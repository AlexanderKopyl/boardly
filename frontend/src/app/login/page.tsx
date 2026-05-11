import { LoginForm } from '@/contexts/identity-access/presentation/ui/LoginForm'
import { PageHeader } from '@/shared/ui/PageHeader'

export default function LoginPage() {
  return (
    <main className="ui-auth-page">
      <section className="ui-auth-page__panel" aria-labelledby="login-page-title">
        <PageHeader
          eyebrow="Boardly"
          title="Sign in"
          description="Access your workspace to review projects, issues, and workflow state."
          id="login-page-title"
        />
        <LoginForm />
      </section>
    </main>
  )
}
