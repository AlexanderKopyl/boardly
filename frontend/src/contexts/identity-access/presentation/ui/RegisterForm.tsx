'use client'

import { useState } from 'react'

import { Button } from '@/shared/ui/Button'
import { Input } from '@/shared/ui/Input'
import { useAuth } from '../hooks/useAuth'

export function RegisterForm() {
  const { register } = useAuth()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState<string | null>(null)
  const [loading, setLoading] = useState(false)
  const [submitted, setSubmitted] = useState(false)

  async function handleSubmit(e: React.FormEvent) {
    e.preventDefault()
    setError(null)
    setLoading(true)
    try {
      await register(email, password, name)
      setSubmitted(true)
    } catch {
      setError('Registration failed. Please try again.')
    } finally {
      setLoading(false)
    }
  }

  if (submitted) {
    return <p>Your account is pending approval. You will be notified once it is activated.</p>
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
        name="password"
        value={password}
        onChange={setPassword}
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
