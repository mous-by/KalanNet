import { MD3LightTheme } from 'react-native-paper';
import { SURFACE, ThemeDefinition } from './themes';

export function buildPaperTheme(theme: ThemeDefinition) {
  return {
    ...MD3LightTheme,
    colors: {
      ...MD3LightTheme.colors,
      primary: theme.accent,
      background: SURFACE.background,
      surface: SURFACE.card,
      surfaceVariant: SURFACE.card,
      onSurface: SURFACE.text,
      outline: SURFACE.border,
    },
  };
}
