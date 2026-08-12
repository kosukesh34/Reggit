import type { CSSProperties } from "react";
import type { AppCustomization, DesignPreset } from "../types/customization";
import type { ThemeConfig } from "../types/theme";

const presetTokens: Record<Exclude<DesignPreset, "editorial">, Record<string, string>> = {
  bold: {
    "--color-primary": "#17130f",
    "--color-secondary": "#5b5147",
    "--color-accent": "#ef542f",
    "--color-background": "#f1eadf",
    "--color-surface": "#fffdf8",
    "--color-elevated": "#ffffff",
    "--color-text-primary": "#17130f",
    "--color-text-secondary": "#6c6258",
    "--color-border": "#cfc2b3",
    "--radius-button": "4px",
    "--radius-card": "8px",
    "--radius-image": "6px",
    "--font-heading-weight": "800",
  },
  airy: {
    "--color-primary": "#174b55",
    "--color-secondary": "#608087",
    "--color-accent": "#d66f55",
    "--color-background": "#edf5f3",
    "--color-surface": "#fbfffe",
    "--color-elevated": "#ffffff",
    "--color-text-primary": "#18383d",
    "--color-text-secondary": "#668086",
    "--color-border": "#cfe0dc",
    "--radius-button": "24px",
    "--radius-card": "30px",
    "--radius-image": "28px",
    "--font-heading-weight": "650",
  },
  minimal: {
    "--color-primary": "#151515",
    "--color-secondary": "#6d6d6d",
    "--color-accent": "#151515",
    "--color-background": "#f7f7f5",
    "--color-surface": "#ffffff",
    "--color-elevated": "#ffffff",
    "--color-text-primary": "#151515",
    "--color-text-secondary": "#767676",
    "--color-border": "#e7e7e3",
    "--radius-button": "10px",
    "--radius-card": "14px",
    "--radius-image": "12px",
    "--font-heading-weight": "600",
  },
  luxury: {
    "--color-primary": "#31251d",
    "--color-secondary": "#756354",
    "--color-accent": "#ad8650",
    "--color-background": "#f4efe6",
    "--color-surface": "#fffcf7",
    "--color-elevated": "#ffffff",
    "--color-text-primary": "#2c231d",
    "--color-text-secondary": "#776b61",
    "--color-border": "#ded1bf",
    "--radius-button": "2px",
    "--radius-card": "2px",
    "--radius-image": "2px",
    "--font-heading-weight": "600",
  },
  pop: {
    "--color-primary": "#5527d7",
    "--color-secondary": "#765cc0",
    "--color-accent": "#f2509d",
    "--color-background": "#fff3aa",
    "--color-surface": "#fffdf4",
    "--color-elevated": "#ffffff",
    "--color-text-primary": "#2c165b",
    "--color-text-secondary": "#725f7d",
    "--color-border": "#dccb88",
    "--radius-button": "999px",
    "--radius-card": "22px",
    "--radius-image": "22px",
    "--font-heading-weight": "800",
  },
  natural: {
    "--color-primary": "#35543b",
    "--color-secondary": "#72806b",
    "--color-accent": "#b86543",
    "--color-background": "#eeeadd",
    "--color-surface": "#faf8f0",
    "--color-elevated": "#ffffff",
    "--color-text-primary": "#28342a",
    "--color-text-secondary": "#6f776b",
    "--color-border": "#d6d0bc",
    "--radius-button": "16px",
    "--radius-card": "20px",
    "--radius-image": "34px",
    "--font-heading-weight": "700",
  },
  monochrome: {
    "--color-primary": "#050505",
    "--color-secondary": "#4d4d4d",
    "--color-accent": "#050505",
    "--color-background": "#eeeeee",
    "--color-surface": "#ffffff",
    "--color-elevated": "#ffffff",
    "--color-text-primary": "#050505",
    "--color-text-secondary": "#676767",
    "--color-border": "#bdbdbd",
    "--radius-button": "0px",
    "--radius-card": "0px",
    "--radius-image": "0px",
    "--font-heading-weight": "850",
  },
  glass: {
    "--color-primary": "#4c46a8",
    "--color-secondary": "#626a9a",
    "--color-accent": "#2ea6a8",
    "--color-background": "#e7eaff",
    "--color-surface": "#f7f8ff",
    "--color-elevated": "#ffffff",
    "--color-text-primary": "#242451",
    "--color-text-secondary": "#686a8d",
    "--color-border": "#c9cce8",
    "--radius-button": "18px",
    "--radius-card": "26px",
    "--radius-image": "26px",
    "--font-heading-weight": "700",
  },
  neo: {
    "--color-primary": "#101a42",
    "--color-secondary": "#566081",
    "--color-accent": "#2fd7a0",
    "--color-background": "#eef1f7",
    "--color-surface": "#ffffff",
    "--color-elevated": "#ffffff",
    "--color-text-primary": "#111a38",
    "--color-text-secondary": "#68708b",
    "--color-border": "#d4d9e7",
    "--radius-button": "12px",
    "--radius-card": "18px",
    "--radius-image": "16px",
    "--font-heading-weight": "750",
  },
  restaurant: {
    "--color-primary": "#342019",
    "--color-secondary": "#75645d",
    "--color-accent": "#a63d2f",
    "--color-background": "#f7f1e8",
    "--color-surface": "#fffdfa",
    "--color-elevated": "#ffffff",
    "--color-text-primary": "#2b201c",
    "--color-text-secondary": "#766861",
    "--color-border": "#dfd2c5",
    "--radius-button": "10px",
    "--radius-card": "16px",
    "--radius-image": "12px",
    "--font-heading-weight": "700",
  },
  izakaya: {
    "--color-primary": "#171c24",
    "--color-secondary": "#5d5a55",
    "--color-accent": "#d48b34",
    "--color-background": "#f1ece3",
    "--color-surface": "#fffaf1",
    "--color-elevated": "#ffffff",
    "--color-text-primary": "#201d19",
    "--color-text-secondary": "#746b60",
    "--color-border": "#ded3c4",
    "--radius-button": "8px",
    "--radius-card": "14px",
    "--radius-image": "10px",
    "--font-heading-weight": "700",
  },
  fastfood: {
    "--color-primary": "#c62424",
    "--color-secondary": "#6f251e",
    "--color-accent": "#ffc93d",
    "--color-background": "#fff7e8",
    "--color-surface": "#ffffff",
    "--color-elevated": "#ffffff",
    "--color-text-primary": "#241713",
    "--color-text-secondary": "#745d53",
    "--color-border": "#efd6bb",
    "--radius-button": "16px",
    "--radius-card": "20px",
    "--radius-image": "18px",
    "--font-heading-weight": "800",
  },
};

export function customizationToCssVars(customization: AppCustomization): CSSProperties {
  const tokens = customization.designPreset === "editorial" ? {} : presetTokens[customization.designPreset];
  return {
    ...tokens,
    ...(customization.colors.primary ? { ["--color-primary" as string]: customization.colors.primary } : {}),
    ...(customization.colors.secondary ? { ["--color-secondary" as string]: customization.colors.secondary } : {}),
    ...(customization.colors.accent ? { ["--color-accent" as string]: customization.colors.accent } : {}),
    ...(customization.colors.background ? { ["--color-background" as string]: customization.colors.background } : {}),
    ...(customization.colors.surface ? { ["--color-surface" as string]: customization.colors.surface } : {}),
    ...(customization.colors.textPrimary ? { ["--color-text-primary" as string]: customization.colors.textPrimary } : {}),
    ...(customization.colors.textSecondary ? { ["--color-text-secondary" as string]: customization.colors.textSecondary } : {}),
  };
}

function readableTextColor(background: string): string {
  const match = background.trim().match(/^#([0-9a-f]{3}|[0-9a-f]{6})$/i);
  if (match === null) {
    return "#ffffff";
  }
  const raw = match[1].length === 3
    ? match[1].split("").map((part) => `${part}${part}`).join("")
    : match[1];
  const channels = [0, 2, 4].map((offset) => {
    const value = Number.parseInt(raw.slice(offset, offset + 2), 16) / 255;
    return value <= 0.04045 ? value / 12.92 : ((value + 0.055) / 1.055) ** 2.4;
  });
  const luminance = 0.2126 * channels[0] + 0.7152 * channels[1] + 0.0722 * channels[2];
  return luminance > 0.179 ? "#101712" : "#ffffff";
}

export function contrastCssVars(
  customization: AppCustomization,
  theme: ThemeConfig,
): CSSProperties {
  const colors = resolveCustomizationColors(customization, theme);
  return {
    ["--color-on-primary" as string]: readableTextColor(colors.primary),
    ["--color-on-accent" as string]: readableTextColor(colors.accent),
  };
}

export function resolveCustomizationColors(
  customization: AppCustomization,
  theme: ThemeConfig,
): Required<AppCustomization["colors"]> {
  const variables = customizationToCssVars(customization) as Record<string, string>;
  return {
    primary: variables["--color-primary"] ?? theme.colors.primary,
    secondary: variables["--color-secondary"] ?? theme.colors.secondary,
    accent: variables["--color-accent"] ?? theme.colors.accent,
    background: variables["--color-background"] ?? theme.colors.background,
    surface: variables["--color-surface"] ?? theme.colors.surface,
    textPrimary: variables["--color-text-primary"] ?? theme.colors.textPrimary,
    textSecondary: variables["--color-text-secondary"] ?? theme.colors.textSecondary,
  };
}
