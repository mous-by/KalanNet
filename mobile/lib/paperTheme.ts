import { MD3DarkTheme, MD3LightTheme } from 'react-native-paper';

const brandGreen = '#1f8a4c';

export const paperLightTheme = {
  ...MD3LightTheme,
  colors: {
    ...MD3LightTheme.colors,
    primary: brandGreen,
  },
};

export const paperDarkTheme = {
  ...MD3DarkTheme,
  colors: {
    ...MD3DarkTheme.colors,
    primary: '#4cbf7c',
  },
};
