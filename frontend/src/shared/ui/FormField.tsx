import { useId } from 'react'
import type { ReactElement, ReactNode } from 'react'

import { cn } from '@/shared/lib/cn'

export type FormFieldRenderProps = {
  inputId: string
  descriptionId?: string
  errorId?: string
  describedBy?: string
  invalid: boolean
  required: boolean
}

export type FormFieldProps = {
  label: ReactNode
  children: (props: FormFieldRenderProps) => ReactNode
  description?: ReactNode
  error?: ReactNode
  required?: boolean
  invalid?: boolean
  className?: string
}

export function FormField({
  label,
  children,
  description,
  error,
  required = false,
  invalid = Boolean(error),
  className,
}: FormFieldProps): ReactElement {
  const id = useId()
  const inputId = `field-${id}`
  const descriptionId = description ? `${inputId}-description` : undefined
  const errorId = error ? `${inputId}-error` : undefined
  const describedBy = [descriptionId, errorId].filter(Boolean).join(' ') || undefined

  return (
    <div className={cn('ui-form-field', className)} data-invalid={invalid || undefined}>
      <label className="ui-form-field__label" htmlFor={inputId}>
        {label}
        {required ? <span aria-hidden="true"> *</span> : null}
      </label>

      {children({
        inputId,
        descriptionId,
        errorId,
        describedBy,
        invalid,
        required,
      })}

      {description ? (
        <p className="ui-form-field__description" id={descriptionId}>
          {description}
        </p>
      ) : null}

      {error ? (
        <p className="ui-form-field__error" id={errorId} role="alert">
          {error}
        </p>
      ) : null}
    </div>
  )
}
