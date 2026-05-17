'use client'

import { useState } from 'react'
import type { ReactElement } from 'react'

import { cn } from '@/shared/lib/cn'

import { Button } from './Button'
import { Input } from './Input'
import type { InputProps } from './Input'

export type PasswordInputProps = Omit<InputProps, 'className' | 'type'> & {
  className?: string
  inputClassName?: string
  revealLabel?: string
  concealLabel?: string
}

export function PasswordInput({
  className,
  inputClassName,
  revealLabel = 'Show password',
  concealLabel = 'Hide password',
  disabled,
  id,
  ...props
}: PasswordInputProps): ReactElement {
  const [isVisible, setIsVisible] = useState(false)

  return (
    <div className={cn('flex items-stretch gap-2', className)}>
      <Input
        id={id}
        {...props}
        type={isVisible ? 'text' : 'password'}
        className={cn('flex-1', inputClassName)}
      />
      <Button
        type="button"
        disabled={disabled}
        variant="outline"
        size="md"
        className="shrink-0 px-3"
        aria-controls={id}
        aria-pressed={isVisible}
        aria-label={isVisible ? concealLabel : revealLabel}
        onClick={() => setIsVisible((current) => !current)}
      >
        {isVisible ? concealLabel : revealLabel}
      </Button>
    </div>
  )
}
