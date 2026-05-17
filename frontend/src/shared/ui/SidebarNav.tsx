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
    <nav aria-label={label} className={cn('space-y-4', className)} {...props}>
      {groups.map((group, groupIndex) => (
        <div key={`${group.label ?? 'group'}-${groupIndex}`} className="space-y-2">
          {group.label ? (
            <p className="px-3 text-[11px] font-semibold uppercase tracking-[0.2em] text-[var(--sidebar-muted)]">
              {group.label}
            </p>
          ) : null}
          <ul className="space-y-2">
            {group.items.map((item, index) => (
              <li key={`${item.href}-${index}`}>
                {item.disabled ? (
                  <span
                    aria-disabled="true"
                    data-disabled="true"
                    data-current={item.current || undefined}
                    className={cn(
                      'flex items-center gap-3 rounded-[16px] px-4 py-3 text-sm font-medium transition-colors',
                      item.current
                        ? 'bg-white/[0.12] text-[var(--sidebar-foreground)]'
                        : 'text-[var(--sidebar-muted)]',
                    )}
                  >
                    {item.icon ? <span className="shrink-0 text-[var(--sidebar-foreground)]">{item.icon}</span> : null}
                    <span className="min-w-0 flex-1">
                      <span className="block">{item.label}</span>
                      {item.description ? (
                        <span className="mt-0.5 block text-xs text-[var(--sidebar-muted)]">
                          {item.description}
                        </span>
                      ) : null}
                    </span>
                    {item.badge ? <span className="shrink-0">{item.badge}</span> : null}
                  </span>
                ) : (
                  <Link
                    href={item.href}
                    aria-current={item.current ? 'page' : undefined}
                    data-current={item.current || undefined}
                    className={cn(
                      'flex items-center gap-3 rounded-[16px] px-4 py-3 text-sm font-medium transition-colors',
                      item.current
                        ? 'bg-white/[0.12] text-[var(--sidebar-foreground)]'
                        : 'text-[var(--sidebar-muted)] hover:bg-white/[0.06] hover:text-[var(--sidebar-foreground)]',
                    )}
                  >
                    {item.icon ? <span className="shrink-0 text-[var(--sidebar-foreground)]">{item.icon}</span> : null}
                    <span className="min-w-0 flex-1">
                      <span className="block">{item.label}</span>
                      {item.description ? (
                        <span className="mt-0.5 block text-xs text-[var(--sidebar-muted)]">
                          {item.description}
                        </span>
                      ) : null}
                    </span>
                    {item.badge ? <span className="shrink-0">{item.badge}</span> : null}
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
