import type { ReactNode } from 'react';

export type ButtonProps = {
  type?: 'button' | 'submit' | 'reset';
  disabled?: boolean;
  onClick?: () => void;
  children: ReactNode;
};

export function Button({ type = 'button', disabled, onClick, children }: ButtonProps) {
  return (
    <button type={type} disabled={disabled} onClick={onClick}>
      {children}
    </button>
  );
}
