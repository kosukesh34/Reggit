import type { ComponentPropsWithoutRef } from "react";

interface CardProps extends ComponentPropsWithoutRef<"section"> {
  padding?: "sm" | "md" | "lg";
}

export function Card({ children, className = "", padding = "md", ...props }: CardProps) {
  return <section {...props} className={`card card--${padding} ${className}`.trim()}>{children}</section>;
}
