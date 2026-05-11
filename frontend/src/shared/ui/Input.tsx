import type {
  HTMLInputTypeAttribute,
  InputHTMLAttributes,
  ReactElement,
} from 'react'

import { cn } from '@/shared/lib/cn'

export type InputProps = Omit<
  InputHTMLAttributes<HTMLInputElement>,
  'type' | 'value' | 'onChange'
> & {
  type?: HTMLInputTypeAttribute
  value: string
  onChange: (value: string) => void
  invalid?: boolean
  className?: string
}

export function Input({
  type = 'text',
  value,
  onChange,
  invalid = false,
  className,
  ...props
}: InputProps): ReactElement {
  return (
    <input
      type={type}
      value={value}
      onChange={(event) => onChange(event.target.value)}
      aria-invalid={invalid || undefined}
      data-invalid={invalid || undefined}
      className={cn('ui-input', className)}
      {...props}
    />
  )
}
