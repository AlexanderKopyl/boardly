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
      className={cn('ui-empty-state', className)}
      {...props}
    >
      <div className="ui-empty-state__panel">
        {icon ? <div className="ui-empty-state__icon">{icon}</div> : null}
        <div className="ui-empty-state__content">
          <h2 className="ui-empty-state__title" id={titleId}>
            {title}
          </h2>
          {description ? (
            <p className="ui-empty-state__description" id={descriptionId}>
              {description}
            </p>
          ) : null}
        </div>
        {actions ? <div className="ui-empty-state__actions">{actions}</div> : null}
      </div>
    </section>
  )
}
