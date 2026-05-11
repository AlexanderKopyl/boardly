'use client'

import type { FormEvent } from 'react'
import { useState } from 'react'
import { useRouter } from 'next/navigation'

import { Alert } from '@/shared/ui/Alert'
import { Button } from '@/shared/ui/Button'
import { FormField } from '@/shared/ui/FormField'
import { Input } from '@/shared/ui/Input'
import { PasswordInput } from '@/shared/ui/PasswordInput'
import { useAuth } from '../hooks/useAuth'

export function RegisterForm() {
  const { register } = useAuth()
  const router = useRouter()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [plainPassword, setPlainPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)

  async function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      await register(email, plainPassword, name)
      router.push('/pending-approval')
    } catch {
      setError('Registration failed. Please try again.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <form className="ui-form-stack" onSubmit={handleSubmit}>
      <FormField
        label="Full name"
        description="Use the name your teammates will recognize."
        required
      >
        {({ inputId, describedBy, invalid, required }) => (
          <Input
            id={inputId}
            type="text"
            name="name"
            value={name}
            onChange={setName}
            autoComplete="name"
            aria-describedby={describedBy}
            aria-invalid={invalid || undefined}
            required={required}
            disabled={loading}
          />
        )}
      </FormField>

      <FormField label="Email" required>
        {({ inputId, describedBy, invalid, required }) => (
          <Input
            id={inputId}
            type="email"
            name="email"
            value={email}
            onChange={setEmail}
            autoComplete="email"
            aria-describedby={describedBy}
            aria-invalid={invalid || undefined}
            required={required}
            disabled={loading}
          />
        )}
      </FormField>

      <FormField
        label="Password"
        description="Choose a password you do not use elsewhere."
        required
      >
        {({ inputId, describedBy, invalid, required }) => (
          <PasswordInput
            id={inputId}
            name="plainPassword"
            value={plainPassword}
            onChange={setPlainPassword}
            autoComplete="new-password"
            aria-describedby={describedBy}
            invalid={invalid}
            required={required}
            disabled={loading}
          />
        )}
      </FormField>

      {error ? <Alert variant="destructive">{error}</Alert> : null}

      <Button type="submit" disabled={loading} isLoading={loading} className="ui-form-submit">
        {loading ? 'Registering…' : 'Register'}
      </Button>
    </form>
  )
}
