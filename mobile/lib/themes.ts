export type ThemeKey =
  | 'bleu-sombre'
  | 'light'
  | 'dark'
  | 'vert'
  | 'violet'
  | 'rouge'
  | 'orange'
  | 'ocean'
  | 'ambre'
  | 'ardoise'
  | 'brume';

export interface ThemeDefinition {
  key: ThemeKey;
  label: string;
  /** "Navbar/sidebar" chrome color — used for headers and the tab bar background. */
  chrome: string;
  /** Text/icon color on top of the chrome color. */
  onChrome: string;
  /** Buttons, links, active states — matches --theme-accent on web. */
  accent: string;
}

// Mirrors resources/css/themes.css and public/css/themes.css exactly, so the
// mobile app's palette matches the web navbar/sidebar theme switcher.
export const THEMES: ThemeDefinition[] = [
  { key: 'bleu-sombre', label: 'Bleu Sombre', chrome: '#001529', onChrome: '#ffffff', accent: '#001529' },
  { key: 'light', label: 'Light (Clair)', chrome: '#ffffff', onChrome: '#1e293b', accent: '#475569' },
  { key: 'dark', label: 'Dark (Sombre)', chrome: '#831843', onChrome: '#ffffff', accent: '#831843' },
  { key: 'vert', label: 'Vert', chrome: '#14532d', onChrome: '#ffffff', accent: '#14532d' },
  { key: 'violet', label: 'Violet', chrome: '#2e1065', onChrome: '#ffffff', accent: '#2e1065' },
  { key: 'rouge', label: 'Rouge / Bordeaux', chrome: '#450a0a', onChrome: '#ffffff', accent: '#450a0a' },
  { key: 'orange', label: 'Orange', chrome: '#431407', onChrome: '#ffffff', accent: '#431407' },
  { key: 'ocean', label: 'Océan', chrome: '#0b2e33', onChrome: '#ffffff', accent: '#0d9488' },
  { key: 'ambre', label: 'Ambre', chrome: '#241b12', onChrome: '#ffffff', accent: '#d97706' },
  { key: 'ardoise', label: 'Ardoise', chrome: '#1e293b', onChrome: '#ffffff', accent: '#6366f1' },
  { key: 'brume', label: 'Brume', chrome: '#eef2f9', onChrome: '#0f172a', accent: '#059669' },
];

export const DEFAULT_THEME_KEY: ThemeKey = 'bleu-sombre';

// The content surface (cards, backgrounds, body text) stays constant across
// every theme on web — only the navbar/sidebar "chrome" changes — so mobile
// mirrors that instead of also toggling a separate light/dark mode.
export const SURFACE = {
  background: '#f1f5f9',
  card: '#ffffff',
  text: '#1e293b',
  muted: '#64748b',
  border: '#e2e8f0',
};

export function getTheme(key: string | null | undefined): ThemeDefinition {
  return THEMES.find((t) => t.key === key) ?? THEMES.find((t) => t.key === DEFAULT_THEME_KEY)!;
}

export function withOpacity(hex: string, alpha: number): string {
  const value = hex.replace('#', '');
  const r = parseInt(value.substring(0, 2), 16);
  const g = parseInt(value.substring(2, 4), 16);
  const b = parseInt(value.substring(4, 6), 16);
  return `rgba(${r}, ${g}, ${b}, ${alpha})`;
}
