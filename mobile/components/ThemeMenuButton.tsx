import { useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { IconButton, Menu, Text } from 'react-native-paper';

import { useAppTheme } from '@/context/ThemeContext';
import { THEMES } from '@/lib/themes';

export default function ThemeMenuButton({ color }: { color?: string }) {
  const { themeKey, setThemeKey } = useAppTheme();
  const [visible, setVisible] = useState(false);

  return (
    <Menu
      visible={visible}
      onDismiss={() => setVisible(false)}
      anchor={<IconButton icon="palette-outline" iconColor={color} onPress={() => setVisible(true)} />}>
      {THEMES.map((theme) => (
        <Menu.Item
          key={theme.key}
          leadingIcon={() => <View style={[styles.swatch, { backgroundColor: theme.chrome }]} />}
          title={theme.label}
          trailingIcon={theme.key === themeKey ? 'check' : undefined}
          onPress={() => {
            setThemeKey(theme.key);
            setVisible(false);
          }}
        />
      ))}
    </Menu>
  );
}

const styles = StyleSheet.create({
  swatch: {
    width: 16,
    height: 16,
    borderRadius: 8,
    borderWidth: 1,
    borderColor: 'rgba(0,0,0,0.15)',
  },
});
