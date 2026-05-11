import type { ButtonHTMLAttributes, ReactElement, ReactNode } from 'react'

import { cn } from '@/shared/lib/cn'

export type ButtonVariant =
  | 'primary'
  | 'secondary'
  | 'outline'
  | 'ghost'
  | 'destructive'

export type ButtonSize = 'sm' | 'md' | 'lg'

export type ButtonProps = ButtonHTMLAttributes<HTMLButtonElement> & {
  children: ReactNode
  variant?: ButtonVariant
  size?: ButtonSize
  isLoading?: boolean
  className?: string
}

export function Button({
  type = 'button',
  disabled,
  isLoading = false,
  variant = 'primary',
  size = 'md',
  className,
  children,
  ...props
}: ButtonProps): ReactElement {
  const isDisabled = disabled || isLoading

  return (
    <button
      type={type}
      disabled={isDisabled}
      aria-busy={isLoading || undefined}
      data-variant={variant}
      data-size={size}
      className={cn('ui-button', className)}
      {...props}
    >
      {children}
    </button>
  )
}
