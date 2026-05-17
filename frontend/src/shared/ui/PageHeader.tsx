import { useId } from 'react'
import type { HTMLAttributes, ReactElement, ReactNode } from 'react'

import { cn } from '@/shared/lib/cn'

export type PageHeaderProps = HTMLAttributes<HTMLElement> & {
  title: ReactNode
  description?: ReactNode
  eyebrow?: ReactNode
  actions?: ReactNode
  compact?: boolean
  className?: string
}

export function PageHeader({
  title,
  description,
  eyebrow,
  actions,
  compact = false,
  className,
  ...props
}: PageHeaderProps): ReactElement {
  const id = useId()
  const titleId = `page-header-${id}-title`
  const descriptionId = description ? `page-header-${id}-description` : undefined

  return (
    <header
      aria-labelledby={titleId}
      aria-describedby={descriptionId}
      data-compact={compact || undefined}
      className={cn(
        'flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between',
        compact && 'gap-3',
        className,
      )}
      {...props}
    >
      <div className="space-y-2">
        {eyebrow ? (
          <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-muted-foreground">
            {eyebrow}
          </p>
        ) : null}
        <h1
          className={cn(
            'text-balance text-2xl font-semibold tracking-tight text-foreground sm:text-3xl',
            compact && 'text-xl sm:text-2xl',
          )}
          id={titleId}
        >
          {title}
        </h1>
        {description ? (
          <p
            className={cn(
              'max-w-3xl text-sm leading-6 text-muted-foreground',
              compact && 'max-w-2xl text-xs sm:text-sm',
            )}
            id={descriptionId}
          >
            {description}
          </p>
        ) : null}
      </div>
      {actions ? <div className="flex flex-wrap items-center gap-2">{actions}</div> : null}
    </header>
  )
}
