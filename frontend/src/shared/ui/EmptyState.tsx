import { useId } from 'react'
import type { HTMLAttributes, ReactElement, ReactNode } from 'react'

import { cn } from '@/shared/lib/cn'

export type EmptyStateProps = HTMLAttributes<HTMLElement> & {
  title: ReactNode
  description?: ReactNode
  icon?: ReactNode
  actions?: ReactNode
  className?: string
}

export function EmptyState({
  title,
  description,
  icon,
  actions,
  className,
  ...props
}: EmptyStateProps): ReactElement {
  const id = useId()
  const titleId = `empty-state-${id}-title`
  const descriptionId = description ? `empty-state-${id}-description` : undefined

  return (
    <section
      aria-labelledby={titleId}
      aria-describedby={descriptionId}
      className={cn('mx-auto w-full max-w-xl', className)}
      {...props}
    >
      <div className="rounded-3xl border border-border/70 bg-card px-6 py-7 text-center shadow-sm sm:px-8 sm:py-9">
        {icon ? <div className="mb-4 flex justify-center">{icon}</div> : null}
        <div className="space-y-2">
          <h2 className="text-2xl font-semibold tracking-tight text-foreground" id={titleId}>
            {title}
          </h2>
          {description ? (
            <p className="text-sm leading-6 text-muted-foreground" id={descriptionId}>
              {description}
            </p>
          ) : null}
        </div>
        {actions ? <div className="mt-6 flex flex-wrap justify-center gap-3">{actions}</div> : null}
      </div>
    </section>
  )
}
