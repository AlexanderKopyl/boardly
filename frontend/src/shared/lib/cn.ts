type ClassDictionary = Record<string, boolean | null | undefined>

export type ClassValue =
  | string
  | number
  | null
  | undefined
  | false
  | ClassValue[]
  | ClassDictionary

function collectClassNames(value: ClassValue, classNames: Array<string>): void {
  if (!value) {
    return
  }

  if (typeof value === 'string' || typeof value === 'number') {
    classNames.push(String(value))
    return
  }

  if (Array.isArray(value)) {
    for (const item of value) {
      collectClassNames(item, classNames)
    }
    return
  }

  for (const [className, isEnabled] of Object.entries(value)) {
    if (isEnabled) {
      classNames.push(className)
    }
  }
}

export function cn(...values: ClassValue[]): string {
  const classNames: Array<string> = []

  for (const value of values) {
    collectClassNames(value, classNames)
  }

  return classNames.join(' ')
}
