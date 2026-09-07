import { Pressable, StyleSheet, View } from 'react-native';

import { ThemeKey, THEMES } from '@/lib/themes';

interface Props {
  value: ThemeKey;
  onChange: (key: ThemeKey) => void;
  size?: number;
}

// Compact row of color dots, mirroring the .theme-row picker on the web
// login page — used where a full dropdown/menu would be too heavy (e.g.
// the pre-login preview).
export default function ThemeDots({ value, onChange, size = 22 }: Props) {
  return (
    <View style={styles.row}>
      {THEMES.map((theme) => {
        const selected = theme.key === value;
        return (
          <Pressable
            key={theme.key}
            onPress={() => onChange(theme.key)}
            style={[
              styles.dot,
              { width: size, height: size, borderRadius: size / 2, backgroundColor: theme.chrome },
              selected && styles.dotSelected,
            ]}
          />
        );
      })}
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'center',
    gap: 8,
  },
  dot: {
    borderWidth: 2,
    borderColor: 'rgba(255,255,255,0.6)',
  },
  dotSelected: {
    borderColor: '#0f172a',
  },
});
