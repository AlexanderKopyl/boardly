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
        'min-h-screen bg-background text-foreground lg:grid lg:grid-cols-[18rem_minmax(0,1fr)]',
        className,
      )}
      {...props}
    >
      {sidebar ? (
        <aside
          className={cn(
            'border-b border-border/70 bg-[var(--sidebar)] text-[var(--sidebar-foreground)] lg:sticky lg:top-0 lg:h-screen lg:border-b-0 lg:border-r lg:overflow-y-auto',
            sidebarClassName,
          )}
        >
          {sidebar}
        </aside>
      ) : null}

      <div className={cn('flex min-h-screen min-w-0 flex-col', contentClassName)}>
        {header ? (
          <div className={cn('border-b border-border/70 bg-background/95 backdrop-blur', headerClassName)}>
            {header}
          </div>
        ) : null}
        <main className={cn('min-w-0 flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8', mainClassName)}>
          {children}
        </main>
      </div>
    </div>
  )
}
