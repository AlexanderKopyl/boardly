import type { HTMLAttributes, ReactElement, ReactNode } from 'react'

import { cn } from '@/shared/lib/cn'

export type AppShellProps = HTMLAttributes<HTMLDivElement> & {
  sidebar?: ReactNode
  header?: ReactNode
  children: ReactNode
  className?: string
  sidebarClassName?: string
  headerClassName?: string
  contentClassName?: string
  mainClassName?: string
}

export function AppShell({
  sidebar,
  header,
  children,
  className,
  sidebarClassName,
  headerClassName,
  contentClassName,
  mainClassName,
  ...props
}: AppShellProps): ReactElement {
  return (
    <div className={cn('ui-app-shell', className)} {...props}>
      {sidebar ? (
        <aside className={cn('ui-app-shell__sidebar', sidebarClassName)}>{sidebar}</aside>
      ) : null}

      <div className={cn('ui-app-shell__content', contentClassName)}>
        {header ? (
          <div className={cn('ui-app-shell__header', headerClassName)}>{header}</div>
        ) : null}
        <main className={cn('ui-app-shell__main', mainClassName)}>{children}</main>
      </div>
    </div>
  )
}
