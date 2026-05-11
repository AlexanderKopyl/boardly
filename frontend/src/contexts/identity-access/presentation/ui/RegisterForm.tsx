'use client'

import { useState } from 'react'
import { useRouter } from 'next/navigation'

import { Button } from '@/shared/ui/Button'
import { Input } from '@/shared/ui/Input'
import { useAuth } from '../hooks/useAuth'

export function RegisterForm() {
  const { register } = useAuth()
  const router = useRouter()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [plainPassword, setPlainPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      await register(email, plainPassword, name)
      router.push('/pending-approval')
    } catch {
      setError('Registration failed. Please try again.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <form onSubmit={handleSubmit}>
      <Input
        type="text"
        name="name"
        value={name}
        onChange={setName}
        placeholder="Full name"
        required
        disabled={loading}
      />
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
        {loading ? 'Registering…' : 'Register'}
      </Button>
    </form>
  )
}
