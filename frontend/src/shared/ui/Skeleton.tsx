import type { HTMLAttributes, ReactElement } from 'react'

import { cn } from '@/shared/lib/cn'

export type SkeletonProps = HTMLAttributes<HTMLDivElement> & {
  className?: string
}

export function Skeleton({ className, ...props }: SkeletonProps): ReactElement {
  return (
    <div aria-hidden="true" className={cn('animate-pulse rounded-md bg-muted/80', className)} {...props} />
  )
}
