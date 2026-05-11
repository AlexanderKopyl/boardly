import { RegisterForm } from '@/contexts/identity-access/presentation/ui/RegisterForm'
import { PageHeader } from '@/shared/ui/PageHeader'

export default function RegisterPage() {
  return (
    <main className="ui-auth-page">
      <section className="ui-auth-page__panel" aria-labelledby="register-page-title">
        <PageHeader
          eyebrow="Boardly"
          title="Create account"
          description="Request access to the workspace with a new Boardly account."
          id="register-page-title"
        />
        <RegisterForm />
      </section>
    </main>
  )
}
