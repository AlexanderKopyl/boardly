import { useId } from 'react'
import type { HTMLAttributes, ReactElement, ReactNode } from 'react'

import { cn } from '@/shared/lib/cn'

export type PageHeaderProps = HTMLAttributes<HTMLElement> & {
  title: ReactNode
  description?: ReactNode
  eyebrow?: ReactNode
  actions?: ReactNode
  className?: string
}

export function PageHeader({
  title,
  description,
  eyebrow,
  actions,
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
      className={cn('ui-page-header', className)}
      {...props}
    >
      <div className="ui-page-header__content">
        {eyebrow ? <p className="ui-page-header__eyebrow">{eyebrow}</p> : null}
        <h1 className="ui-page-header__title" id={titleId}>
          {title}
        </h1>
        {description ? (
          <p className="ui-page-header__description" id={descriptionId}>
            {description}
          </p>
        ) : null}
      </div>
      {actions ? <div className="ui-page-header__actions">{actions}</div> : null}
    </header>
  )
}
