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
    <div className={cn('ui-password-input', className)}>
      <Input
        id={id}
        {...props}
        type={isVisible ? 'text' : 'password'}
        className={cn('ui-password-input__control', inputClassName)}
      />
      <Button
        type="button"
        disabled={disabled}
        variant="ghost"
        size="sm"
        className="ui-password-input__toggle"
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
