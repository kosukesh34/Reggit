import type { PropsWithChildren } from "react";

interface CardProps {
  className?: string;
  padding?: "sm" | "md" | "lg";
}

export function Card({ children, className = "", padding = "md" }: PropsWithChildren<CardProps>) {
  return <section className={`card card--${padding} ${className}`.trim()}>{children}</section>;
}

