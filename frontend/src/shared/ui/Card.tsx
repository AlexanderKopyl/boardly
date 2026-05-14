import type { HTMLAttributes, ReactElement } from 'react'

import { cn } from '@/shared/lib/cn'

export type CardProps = HTMLAttributes<HTMLDivElement> & {
  className?: string
}

export function Card({ className, ...props }: CardProps): ReactElement {
  return <div className={cn('ui-card', className)} {...props} />
}
