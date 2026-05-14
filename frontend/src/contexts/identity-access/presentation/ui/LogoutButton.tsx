'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'

import { Button } from '@/shared/ui/Button'
import { useAuth } from '../hooks/useAuth'

export function LogoutButton() {
  const { logout } = useAuth()
  const router = useRouter()
  const [loading, setLoading] = useState(false)

  async function handleClick() {
    if (loading) {
      return
    }

    setLoading(true)
    try {
      await logout()
      router.push('/login')
    } finally {
      setLoading(false)
    }
  }

  return (
    <Button onClick={handleClick} disabled={loading} isLoading={loading}>
      Sign out
    </Button>
  )
}
