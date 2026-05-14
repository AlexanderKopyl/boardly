'use client'

import type { FormEvent } from 'react'
import { useState } from 'react'
import { useRouter } from 'next/navigation'

import { ApiError } from '@/shared/api/api-error'
import { Alert } from '@/shared/ui/Alert'
import { Button } from '@/shared/ui/Button'
import { FormField } from '@/shared/ui/FormField'
import { Input } from '@/shared/ui/Input'
import { PasswordInput } from '@/shared/ui/PasswordInput'
import { AuthError } from '../../domain/auth-error'
import { useAuth } from '../hooks/useAuth'

export function LoginForm() {
  const { login } = useAuth()
  const router = useRouter()
  const [email, setEmail] = useState('')
  const [plainPassword, setPlainPassword] = useState('')
  const [fieldErrors, setFieldErrors] = useState<{ email?: string; plainPassword?: string }>(
    {},
  )
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)

  type LoginFieldName = 'email' | 'plainPassword'

  function mapValidationErrors(details: unknown) {
    const nextFieldErrors: Partial<Record<LoginFieldName, string>> = {}

    if (!Array.isArray(details)) {
      return nextFieldErrors
    }

    for (const violation of details) {
      if (
        violation !== null &&
        typeof violation === 'object' &&
        'field' in violation &&
        'message' in violation &&
        typeof violation.field === 'string' &&
        typeof violation.message === 'string'
      ) {
        if (violation.field === 'email' || violation.field === 'plainPassword') {
          const field = violation.field as LoginFieldName
          nextFieldErrors[field] = violation.message
        }
      }
    }

    return nextFieldErrors
  }

  async function handleSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault()
    setError(null)
    setFieldErrors({})
    setLoading(true)
    try {
      await login(email, plainPassword)
      router.push('/app/dashboard')
    } catch (error) {
      if (error instanceof AuthError && error.code === 'account_not_active') {
        router.push('/pending-approval')
        return
      }

      if (error instanceof AuthError && error.code === 'too_many_login_attempts') {
        setError('Too many sign-in attempts. Please wait and try again.')
        return
      }

      if (error instanceof AuthError && error.code === 'invalid_credentials') {
        setError('Invalid credentials. Please try again.')
        return
      }

      if (error instanceof ApiError && error.errorCode === 'validation_failed') {
        setFieldErrors(mapValidationErrors(error.details))
        setError('Check the highlighted fields and try again.')
        return
      }

      setError('Sign-in failed. Please try again.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <form className="ui-form-stack" onSubmit={handleSubmit}>
      <FormField
        label="Email"
        description="Use the email address linked to your Boardly account."
        required
        error={fieldErrors.email}
      >
        {({ inputId, describedBy, invalid, required }) => (
          <Input
            id={inputId}
            type="email"
            name="email"
            value={email}
            onChange={setEmail}
            autoComplete="email"
            aria-describedby={describedBy}
            invalid={invalid}
            required={required}
            disabled={loading}
          />
        )}
      </FormField>

      <FormField label="Password" required error={fieldErrors.plainPassword}>
        {({ inputId, describedBy, invalid, required }) => (
          <PasswordInput
            id={inputId}
            name="plainPassword"
            value={plainPassword}
            onChange={setPlainPassword}
            autoComplete="current-password"
            aria-describedby={describedBy}
            invalid={invalid}
            required={required}
            disabled={loading}
          />
        )}
      </FormField>

      {error ? <Alert variant="destructive">{error}</Alert> : null}

      <Button type="submit" disabled={loading} isLoading={loading} className="ui-form-submit">
        {loading ? 'Signing in…' : 'Sign in'}
      </Button>
    </form>
  )
}
