'use client'

import { AuthProvider } from '@/contexts/identity-access/presentation/hooks/useAuth'

export function Providers({ children }: { children: React.ReactNode }) {
  return <AuthProvider>{children}</AuthProvider>
}
