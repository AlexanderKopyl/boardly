'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'

import { Button } from '@/shared/ui/Button'
import { Input } from '@/shared/ui/Input'
import { useAuth } from '../hooks/useAuth'

export function LoginForm() {
  const { login } = useAuth()
  const router = useRouter()
  const [email, setEmail] = useState('')
  const [plainPassword, setPlainPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      await login(email, plainPassword)
      router.push('/dashboard')
    } catch {
      setError('Invalid credentials. Please try again.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <form onSubmit={handleSubmit}>
      <Input
        type="email"
        name="email"
        value={email}
        onChange={setEmail}
        placeholder="Email"
        required
        disabled={loading}
      />
      <Input
        type="password"
        name="plainPassword"
        value={plainPassword}
        onChange={setPlainPassword}
        placeholder="Password"
        required
        disabled={loading}
      />
      {error && <p>{error}</p>}
      <Button type="submit" disabled={loading}>
        {loading ? 'Signing in…' : 'Sign in'}
      </Button>
    </form>
  )
}
