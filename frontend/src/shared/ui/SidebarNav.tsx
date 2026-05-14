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
  disabled?: boolean
}

export type SidebarNavSection = {
  label?: ReactNode
  items: readonly SidebarNavItem[]
}

export type SidebarNavProps = HTMLAttributes<HTMLElement> & {
  items?: readonly SidebarNavItem[]
  sections?: readonly SidebarNavSection[]
  label?: string
  className?: string
}

export function SidebarNav({
  items,
  sections,
  label = 'Sidebar',
  className,
  ...props
}: SidebarNavProps): ReactElement {
  const groups = sections ?? (items ? [{ items }] : [])

  return (
    <nav aria-label={label} className={cn('ui-sidebar-nav', className)} {...props}>
      {groups.map((group, groupIndex) => (
        <div className="ui-sidebar-nav__section" key={`${group.label ?? 'group'}-${groupIndex}`}>
          {group.label ? <p className="ui-sidebar-nav__section-label">{group.label}</p> : null}
          <ul className="ui-sidebar-nav__list">
            {group.items.map((item, index) => (
              <li key={`${item.href}-${index}`}>
                {item.disabled ? (
                  <span
                    aria-disabled="true"
                    data-disabled="true"
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
                  </span>
                ) : (
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
                )}
              </li>
            ))}
          </ul>
        </div>
      ))}
    </nav>
  )
}
