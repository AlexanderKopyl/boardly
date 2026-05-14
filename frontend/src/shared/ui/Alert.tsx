import type { HTMLAttributes, ReactElement, ReactNode } from 'react'

import { cn } from '@/shared/lib/cn'

export type AlertVariant = 'info' | 'success' | 'warning' | 'destructive'

export type AlertProps = HTMLAttributes<HTMLDivElement> & {
  variant?: AlertVariant
  title?: ReactNode
  children: ReactNode
  className?: string
}

export function Alert({
  variant = 'info',
  title,
  children,
  className,
  ...props
}: AlertProps): ReactElement {
  return (
    <div
      role={variant === 'destructive' ? 'alert' : 'status'}
      aria-live={variant === 'destructive' ? 'assertive' : 'polite'}
      data-variant={variant}
      className={cn('ui-alert', className)}
      {...props}
    >
      {title ? <strong>{title}</strong> : null}
      <div>{children}</div>
    </div>
  )
}
