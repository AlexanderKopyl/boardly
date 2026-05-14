import Link from 'next/link'
import type { HTMLAttributes, ReactElement, ReactNode } from 'react'

import { cn } from '@/shared/lib/cn'

export type SidebarNavItem = {
  label: ReactNode
  href: string
  description?: ReactNode
  icon?: ReactNode
  badge?: ReactNode
  current?: boolean
}

export type SidebarNavProps = HTMLAttributes<HTMLElement> & {
  items: readonly SidebarNavItem[]
  label?: string
  className?: string
}

export function SidebarNav({
  items,
  label = 'Sidebar',
  className,
  ...props
}: SidebarNavProps): ReactElement {
  return (
    <nav aria-label={label} className={cn('ui-sidebar-nav', className)} {...props}>
      <ul className="ui-sidebar-nav__list">
        {items.map((item, index) => (
          <li key={`${item.href}-${index}`}>
            <Link
              href={item.href}
              aria-current={item.current ? 'page' : undefined}
              data-current={item.current || undefined}
              className="ui-sidebar-nav__link"
            >
              {item.icon ? <span className="ui-sidebar-nav__icon">{item.icon}</span> : null}
              <span className="ui-sidebar-nav__content">
                <span className="ui-sidebar-nav__label">{item.label}</span>
                {item.description ? (
                  <span className="ui-sidebar-nav__description">{item.description}</span>
                ) : null}
              </span>
              {item.badge ? <span className="ui-sidebar-nav__badge">{item.badge}</span> : null}
            </Link>
          </li>
        ))}
      </ul>
    </nav>
  )
}
