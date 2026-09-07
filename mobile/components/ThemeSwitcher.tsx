import { Pressable, StyleSheet, View } from 'react-native';
import { Text } from 'react-native-paper';

import { useAppTheme } from '@/context/ThemeContext';
import { SURFACE, THEMES } from '@/lib/themes';

export default function ThemeSwitcher() {
  const { themeKey, setThemeKey } = useAppTheme();

  return (
    <View style={styles.grid}>
      {THEMES.map((theme) => {
        const selected = theme.key === themeKey;
        return (
          <Pressable key={theme.key} style={styles.item} onPress={() => setThemeKey(theme.key)}>
            <View
              style={[
                styles.swatch,
                { backgroundColor: theme.chrome },
                selected && styles.swatchSelected,
              ]}>
              {selected ? <View style={[styles.check, { borderColor: theme.onChrome }]} /> : null}
            </View>
            <Text style={styles.label} numberOfLines={1}>
              {theme.label}
            </Text>
          </Pressable>
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 16,
  },
  item: {
    alignItems: 'center',
    width: 72,
  },
  swatch: {
    width: 44,
    height: 44,
    borderRadius: 22,
    borderWidth: 1,
    borderColor: SURFACE.border,
    alignItems: 'center',
    justifyContent: 'center',
  },
  swatchSelected: {
    borderWidth: 3,
    borderColor: '#1f8a4c',
  },
  check: {
    width: 10,
    height: 10,
    borderRadius: 5,
    borderWidth: 2,
    backgroundColor: 'transparent',
  },
  label: {
    fontSize: 11,
    color: SURFACE.muted,
    marginTop: 6,
    textAlign: 'center',
  },
});
