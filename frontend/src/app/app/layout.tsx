import type { ReactNode } from 'react'

import { ProtectedWorkspaceShell } from './ProtectedWorkspaceShell'

export default function AppLayout({ children }: { children: ReactNode }) {
  return <ProtectedWorkspaceShell>{children}</ProtectedWorkspaceShell>
}
