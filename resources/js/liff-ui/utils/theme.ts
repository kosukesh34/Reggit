import type { CSSProperties } from "react";
import type { ThemeConfig } from "../types/theme";

export function themeToCssVars(theme: ThemeConfig): CSSProperties {
  return {
    ["--color-primary" as string]: theme.colors.primary,
    ["--color-secondary" as string]: theme.colors.secondary,
    ["--color-accent" as string]: theme.colors.accent,
    ["--color-background" as string]: theme.colors.background,
    ["--color-surface" as string]: theme.colors.surface,
    ["--color-elevated" as string]: theme.colors.elevated,
    ["--color-text-primary" as string]: theme.colors.textPrimary,
    ["--color-text-secondary" as string]: theme.colors.textSecondary,
    ["--color-border" as string]: theme.colors.border,
    ["--radius-button" as string]: `${theme.radius.button}px`,
    ["--radius-card" as string]: `${theme.radius.card}px`,
    ["--radius-image" as string]: `${theme.radius.image}px`,
    ["--font-heading-weight" as string]: String(theme.typography.headingWeight),
    ["--font-body-weight" as string]: String(theme.typography.bodyWeight),
  };
}

