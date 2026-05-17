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
    <div
      className={cn(
        'min-h-screen overflow-x-hidden bg-surface text-foreground',
        className,
      )}
      {...props}
    >
      {sidebar ? (
        <aside
          className={cn(
            'bg-[var(--sidebar)] text-[var(--sidebar-foreground)] lg:fixed lg:inset-y-0 lg:left-0 lg:z-30 lg:h-screen lg:w-[280px] lg:border-r lg:border-[color:var(--sidebar-border)]',
            sidebarClassName,
          )}
        >
          {sidebar}
        </aside>
      ) : null}

        <div
          className={cn(
            'flex min-h-screen min-w-0 flex-col',
            sidebar ? 'lg:pl-[280px]' : undefined,
            contentClassName,
          )}
        >
          {header ? (
            <div
              className={cn(
                'border-b border-border/40 bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/90 lg:fixed lg:top-0 lg:z-20 lg:left-[280px] lg:w-[calc(100%-280px)]',
                headerClassName,
              )}
            >
              {header}
          </div>
        ) : null}
        <main
          className={cn(
            'min-w-0 flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8',
            header ? 'lg:pt-16' : undefined,
            mainClassName,
          )}
        >
          {children}
        </main>
      </div>
    </div>
  )
}
