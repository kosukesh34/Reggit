import { ThemeConfig } from "../types/theme";

export const minimalLightTheme: ThemeConfig = {
  id: "minimal-light",
  name: "Minimal Light",
  colors: {
    primary: "#1f2937",
    secondary: "#6b7280",
    accent: "#0f766e",
    background: "#f8fafc",
    surface: "#ffffff",
    elevated: "#ffffff",
    textPrimary: "#0f172a",
    textSecondary: "#64748b",
    border: "#e2e8f0",
  },
  radius: { button: 14, card: 22, image: 20 },
  typography: { headingWeight: 700, bodyWeight: 500 },
  appearance: { cardStyle: "flat", buttonStyle: "solid", iconStyle: "outline", density: "normal" },
};

export const cafeNaturalTheme: ThemeConfig = {
  id: "cafe-natural",
  name: "Cafe Natural",
  colors: {
    primary: "#3a2a20",
    secondary: "#78685b",
    accent: "#b85f35",
    background: "#f4efe7",
    surface: "#fffdf8",
    elevated: "#ffffff",
    textPrimary: "#2c211a",
    textSecondary: "#75665a",
    border: "#e4d9ca",
  },
  radius: { button: 16, card: 24, image: 18 },
  typography: { headingWeight: 700, bodyWeight: 500 },
  appearance: { cardStyle: "shadow", buttonStyle: "soft", iconStyle: "filled", density: "comfortable" },
};

export const fashionChicTheme: ThemeConfig = {
  id: "fashion-chic",
  name: "Fashion Chic",
  colors: {
    primary: "#171717",
    secondary: "#68625f",
    accent: "#87344b",
    background: "#f6f3f0",
    surface: "#ffffff",
    elevated: "#faf8f6",
    textPrimary: "#171717",
    textSecondary: "#716b68",
    border: "#e5dfdb",
  },
  radius: { button: 18, card: 26, image: 22 },
  typography: { headingWeight: 700, bodyWeight: 500 },
  appearance: { cardStyle: "border", buttonStyle: "solid", iconStyle: "outline", density: "normal" },
};

export const salonElegantTheme: ThemeConfig = {
  id: "salon-elegant",
  name: "Salon Elegant",
  colors: {
    primary: "#4b3038",
    secondary: "#806b70",
    accent: "#9b6570",
    background: "#f7f2ef",
    surface: "#ffffff",
    elevated: "#ffffff",
    textPrimary: "#33272a",
    textSecondary: "#7b6a6e",
    border: "#e7dcd9",
  },
  radius: { button: 18, card: 28, image: 24 },
  typography: { headingWeight: 700, bodyWeight: 500 },
  appearance: { cardStyle: "shadow", buttonStyle: "soft", iconStyle: "filled", density: "comfortable" },
};

export const premiumGoldTheme: ThemeConfig = {
  id: "premium-gold",
  name: "Premium Gold",
  colors: {
    primary: "#3f2d0a",
    secondary: "#7c6525",
    accent: "#d4a73a",
    background: "#fbf7ef",
    surface: "#fffdf8",
    elevated: "#ffffff",
    textPrimary: "#2b1d07",
    textSecondary: "#7c6525",
    border: "#eadfbf",
  },
  radius: { button: 18, card: 28, image: 24 },
  typography: { headingWeight: 800, bodyWeight: 500 },
  appearance: { cardStyle: "shadow", buttonStyle: "solid", iconStyle: "filled", density: "comfortable" },
};

export const izakayaNightTheme: ThemeConfig = {
  id: "izakaya-night",
  name: "Izakaya Night",
  colors: {
    primary: "#171c24",
    secondary: "#5d5a55",
    accent: "#d48b34",
    background: "#f1ece3",
    surface: "#fffaf1",
    elevated: "#ffffff",
    textPrimary: "#201d19",
    textSecondary: "#746b60",
    border: "#ded3c4",
  },
  radius: { button: 8, card: 14, image: 10 },
  typography: { headingWeight: 700, bodyWeight: 500 },
  appearance: { cardStyle: "border", buttonStyle: "solid", iconStyle: "filled", density: "normal" },
};

export const quickServiceTheme: ThemeConfig = {
  id: "quick-service",
  name: "Quick Service",
  colors: {
    primary: "#c62424",
    secondary: "#6f251e",
    accent: "#ffc93d",
    background: "#fff7e8",
    surface: "#ffffff",
    elevated: "#ffffff",
    textPrimary: "#241713",
    textSecondary: "#745d53",
    border: "#efd6bb",
  },
  radius: { button: 16, card: 20, image: 18 },
  typography: { headingWeight: 800, bodyWeight: 600 },
  appearance: { cardStyle: "shadow", buttonStyle: "solid", iconStyle: "filled", density: "compact" },
};

export const allThemes = [
  minimalLightTheme,
  cafeNaturalTheme,
  fashionChicTheme,
  salonElegantTheme,
  premiumGoldTheme,
  izakayaNightTheme,
  quickServiceTheme,
] satisfies ThemeConfig[];

export const themeMap = Object.fromEntries(allThemes.map((theme) => [theme.id, theme])) as Record<
  string,
  ThemeConfig
>;
