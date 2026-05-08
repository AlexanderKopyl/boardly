'use client'

import { useRouter } from 'next/navigation'

import { Button } from '@/shared/ui/Button'
import { useAuth } from '../hooks/useAuth'

export function LogoutButton() {
  const { logout } = useAuth()
  const router = useRouter()

  async function handleClick() {
    await logout()
    router.push('/login')
  }

  return <Button onClick={handleClick}>Sign out</Button>
}
