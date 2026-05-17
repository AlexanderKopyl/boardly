import { forwardRef, type InputHTMLAttributes, type ReactElement } from 'react'
import type { HTMLInputTypeAttribute } from 'react'

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

export const Input = forwardRef<HTMLInputElement, InputProps>(function Input(
  {
    type = 'text',
    value,
    onChange,
    invalid = false,
    className,
    ...props
  },
  ref,
): ReactElement {
  return (
    <input
      ref={ref}
      type={type}
      value={value}
      onChange={(event) => onChange(event.target.value)}
      aria-invalid={invalid || undefined}
      data-invalid={invalid || undefined}
      className={cn(
        'flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm shadow-sm transition-[color,background-color,border-color,box-shadow] placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:ring-offset-background disabled:cursor-not-allowed disabled:bg-muted disabled:text-muted-foreground disabled:opacity-100',
        invalid &&
          'border-destructive focus-visible:border-destructive focus-visible:ring-destructive/20',
        className,
      )}
      {...props}
    />
  )
})
