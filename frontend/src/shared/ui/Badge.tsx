import type { HTMLAttributes, ReactElement, ReactNode } from 'react'

import { cn } from '@/shared/lib/cn'

export type BadgeVariant =
  | 'neutral'
  | 'info'
  | 'success'
  | 'warning'
  | 'destructive'

export type BadgeProps = HTMLAttributes<HTMLSpanElement> & {
  variant?: BadgeVariant
  children: ReactNode
  className?: string
}

export function Badge({
  variant = 'neutral',
  children,
  className,
  ...props
}: BadgeProps): ReactElement {
  return (
    <span data-variant={variant} className={cn('ui-badge', className)} {...props}>
      {children}
    </span>
  )
}
