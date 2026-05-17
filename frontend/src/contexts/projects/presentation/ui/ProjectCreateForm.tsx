'use client'

import type { FormEvent } from 'react'
import { useEffect, useRef, useState } from 'react'
import { useRouter } from 'next/navigation'

import { Alert } from '@/shared/ui/Alert'
import { Button } from '@/shared/ui/Button'
import { FormField } from '@/shared/ui/FormField'
import { Input } from '@/shared/ui/Input'

import { createProjectUseCase } from '../../application/use-cases/create-project'
import { ProjectsError } from '../../domain/project-errors'
import { ProjectsHttpGateway } from '../../infrastructure/http/projects-http-gateway'

const gateway = new ProjectsHttpGateway()

type CreateProjectFieldName = 'name' | 'iconKey'

type CreateProjectFieldErrors = Partial<Record<CreateProjectFieldName, string>>

function mapValidationErrors(details: unknown): CreateProjectFieldErrors {
  const nextFieldErrors: CreateProjectFieldErrors = {}

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
      if (violation.field === 'name' || violation.field === 'iconKey') {
        const field = violation.field as CreateProjectFieldName
        nextFieldErrors[field] = violation.message
      }
    }
  }

  return nextFieldErrors
}

function getErrorMessage(error: unknown): string {
  if (error instanceof ProjectsError) {
    if (error.code === 'unauthorized') {
      return 'Your session is not available. Sign in again to continue.'
    }

    if (error.code === 'forbidden') {
      return 'You do not have access to create projects.'
    }

    if (error.code === 'validation_failed') {
      return 'Check the highlighted fields and try again.'
    }
  }

  return error instanceof Error ? error.message : 'We could not create the project right now.'
}

export function ProjectCreateForm() {
  const router = useRouter()
  const isMountedRef = useRef(true)
  const [name, setName] = useState('')
  const [iconKey, setIconKey] = useState('')
  const [fieldErrors, setFieldErrors] = useState<CreateProjectFieldErrors>({})
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)

  useEffect(() => {
    return () => {
      isMountedRef.current = false
    }
  }, [])

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()
    setError(null)
    setFieldErrors({})

    const trimmedName = name.trim()
    const trimmedIconKey = iconKey.trim()

    if (trimmedName.length === 0) {
      setFieldErrors({ name: 'Project name is required.' })
      setError('Check the highlighted fields and try again.')
      return
    }

    setLoading(true)
    try {
      const result = await createProjectUseCase(
        {
          name: trimmedName,
          iconKey: trimmedIconKey.length > 0 ? trimmedIconKey : undefined,
        },
        { gateway },
      )

      router.replace(`/app/projects/${result.project.id}`)
    } catch (caughtError) {
      if (caughtError instanceof ProjectsError && caughtError.code === 'validation_failed') {
        setFieldErrors(mapValidationErrors(caughtError.details))
        setError('Check the highlighted fields and try again.')
        return
      }

      setError(getErrorMessage(caughtError))
    } finally {
      if (isMountedRef.current) {
        setLoading(false)
      }
    }
  }

  return (
    <form className="ui-form-stack" onSubmit={handleSubmit}>
      <FormField
        label="Project name"
        description="Use the name your team will recognize in the workspace."
        required
        error={fieldErrors.name}
      >
        {({ inputId, describedBy, invalid, required }) => (
          <Input
            id={inputId}
            name="name"
            value={name}
            onChange={setName}
            autoComplete="off"
            placeholder="Launch Northstar"
            aria-describedby={describedBy}
            invalid={invalid}
            required={required}
            disabled={loading}
            maxLength={120}
          />
        )}
      </FormField>

      <FormField
        label="Icon key"
        description="Optional. Leave blank to use the backend default of folder."
        error={fieldErrors.iconKey}
      >
        {({ inputId, describedBy, invalid }) => (
          <Input
            id={inputId}
            name="iconKey"
            value={iconKey}
            onChange={setIconKey}
            autoComplete="off"
            placeholder="folder"
            aria-describedby={describedBy}
            invalid={invalid}
            disabled={loading}
            maxLength={64}
          />
        )}
      </FormField>

      {error ? <Alert variant="destructive">{error}</Alert> : null}

      <Button type="submit" disabled={loading} isLoading={loading} className="ui-form-submit">
        {loading ? 'Creating project…' : 'Create project'}
      </Button>
    </form>
  )
}
