'use client'

import type { ReactElement } from 'react'

import { useAuth } from '../hooks/useAuth'

function getAvatarLabel(name: string, email: string): string {
  const trimmedName = name.trim()

  if (trimmedName.length > 0) {
    const parts = trimmedName.split(/\s+/)

    if (parts.length >= 2) {
      return `${parts[0][0]}${parts[1][0]}`.toUpperCase()
    }

    return trimmedName.slice(0, 2).toUpperCase()
  }

  const localPart = email.split('@')[0]?.trim() ?? ''

  if (localPart.length === 0) {
    return '?'
  }

  const parts = localPart.split(/[._-]+/).filter(Boolean)

  if (parts.length >= 2) {
    return `${parts[0][0]}${parts[1][0]}`.toUpperCase()
  }

  return localPart.slice(0, 2).toUpperCase()
}

export function SidebarAccountCard(): ReactElement {
  const { session, isLoading } = useAuth()
  const account = session?.account
  const isRestoring = isLoading || (session !== null && account === null)

  const name = account?.name ?? (isRestoring ? 'Loading account' : 'Authenticated account')
  const email = account?.email ?? (isRestoring ? 'Restoring session' : 'Account details unavailable')
  const avatarLabel = account ? getAvatarLabel(account.name, account.email) : isRestoring ? '…' : '?'

  return (
    <div className="ui-app-shell__account-card">
      <div className="ui-app-shell__account-avatar">{avatarLabel}</div>
      <div className="ui-app-shell__account-copy">
        <div className="ui-app-shell__account-name" title={name}>
          {name}
        </div>
        <div className="ui-app-shell__account-email" title={email}>
          {email}
        </div>
      </div>
    </div>
  )
}
