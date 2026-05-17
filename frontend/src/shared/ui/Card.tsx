import type { HTMLAttributes, ReactElement } from 'react'

import { cn } from '@/shared/lib/cn'

export type CardProps = HTMLAttributes<HTMLDivElement> & {
  className?: string
}

export function Card({ className, ...props }: CardProps): ReactElement {
  return (
    <div
      className={cn(
        'rounded-3xl border border-border/70 bg-card text-card-foreground shadow-sm',
        className,
      )}
      {...props}
    />
  )
}
