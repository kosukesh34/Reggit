export type ThemeCardStyle = "flat" | "border" | "shadow";
export type ThemeButtonStyle = "solid" | "outline" | "soft";
export type ThemeIconStyle = "outline" | "filled";
export type ThemeDensity = "compact" | "normal" | "comfortable";

export interface ThemeConfig {
  id: string;
  name: string;
  colors: {
    primary: string;
    secondary: string;
    accent: string;
    background: string;
    surface: string;
    elevated: string;
    textPrimary: string;
    textSecondary: string;
    border: string;
  };
  radius: {
    button: number;
    card: number;
    image: number;
  };
  typography: {
    headingWeight: number;
    bodyWeight: number;
  };
  appearance: {
    cardStyle: ThemeCardStyle;
    buttonStyle: ThemeButtonStyle;
    iconStyle: ThemeIconStyle;
    density: ThemeDensity;
  };
}

