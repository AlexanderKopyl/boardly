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
import { useAuth } from '../hooks/useAuth'

export function RegisterForm() {
  const { register } = useAuth()
  const router = useRouter()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [plainPassword, setPlainPassword] = useState('')
  const [fieldErrors, setFieldErrors] = useState<{
    name?: string
    email?: string
    plainPassword?: string
  }>({})
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)

  type RegisterFieldName = 'name' | 'email' | 'plainPassword'

  function mapValidationErrors(details: unknown) {
    const nextFieldErrors: Partial<Record<RegisterFieldName, string>> = {}

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
        if (
          violation.field === 'name' ||
          violation.field === 'email' ||
          violation.field === 'plainPassword'
        ) {
          const field = violation.field as RegisterFieldName
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
      await register(email, plainPassword, name)
      router.push('/pending-approval')
    } catch (error) {
      if (error instanceof ApiError && error.errorCode === 'email_already_registered') {
        setError('An account with this email already exists.')
        return
      }

      if (error instanceof ApiError && error.errorCode === 'validation_failed') {
        setFieldErrors(mapValidationErrors(error.details))
        setError('Check the highlighted fields and try again.')
        return
      }

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
        error={fieldErrors.name}
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
            invalid={invalid}
            required={required}
            disabled={loading}
          />
        )}
      </FormField>

      <FormField label="Email" required error={fieldErrors.email}>
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

      <FormField
        label="Password"
        description="Choose a password you do not use elsewhere."
        required
        error={fieldErrors.plainPassword}
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
