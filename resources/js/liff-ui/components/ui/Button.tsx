import type { ButtonHTMLAttributes, PropsWithChildren, ReactNode } from "react";

type ButtonVariant = "solid" | "soft" | "outline" | "ghost";
type ButtonSize = "sm" | "md" | "lg" | "icon";

interface ButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
  variant?: ButtonVariant;
  size?: ButtonSize;
  ariaLabel?: string;
}

export function Button({
  children,
  className = "",
  variant = "solid",
  size = "md",
  ariaLabel,
  ...props
}: PropsWithChildren<ButtonProps>) {
  const classes = ["button", `button--${variant}`, `button--${size}`, className].filter(Boolean).join(" ");
  return (
    <button className={classes} aria-label={ariaLabel} {...props}>
      {children as ReactNode}
    </button>
  );
}

