import type { HTMLInputTypeAttribute } from 'react';

export type InputProps = {
  type?: HTMLInputTypeAttribute;
  value: string;
  onChange: (value: string) => void;
  placeholder?: string;
  disabled?: boolean;
  required?: boolean;
  name?: string;
};

export function Input({ type = 'text', value, onChange, placeholder, disabled, required, name }: InputProps) {
  return (
    <input
      type={type}
      value={value}
      onChange={(e) => onChange(e.target.value)}
      placeholder={placeholder}
      disabled={disabled}
      required={required}
      name={name}
    />
  );
}
