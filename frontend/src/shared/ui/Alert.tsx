import type { HTMLAttributes, ReactElement, ReactNode } from 'react'

import { cn } from '@/shared/lib/cn'

export type AlertVariant = 'info' | 'success' | 'warning' | 'destructive'

export type AlertProps = HTMLAttributes<HTMLDivElement> & {
  variant?: AlertVariant
  title?: ReactNode
  children: ReactNode
  className?: string
}

const variantClasses: Record<AlertVariant, string> = {
  info: 'border-border/70 bg-card text-card-foreground',
  success: 'border-success/25 bg-success/10 text-foreground',
  warning: 'border-warning/25 bg-warning/10 text-foreground',
  destructive: 'border-destructive/25 bg-destructive/10 text-destructive-foreground',
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
      className={cn(
        'rounded-2xl border px-4 py-3 text-sm shadow-sm',
        variantClasses[variant],
        className,
      )}
      {...props}
    >
      {title ? <strong className="mb-1 block font-semibold">{title}</strong> : null}
      <div className="leading-6">{children}</div>
    </div>
  )
}
