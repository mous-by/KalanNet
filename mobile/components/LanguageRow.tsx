import { Pressable, StyleSheet, View } from 'react-native';
import { Text } from 'react-native-paper';

import { Locale, LOCALES } from '@/lib/i18n';

interface Props {
  value: Locale;
  onChange: (locale: Locale) => void;
}

// Compact "Fr | En | Ar" style row, matching the language switcher shown
// on the web login page.
export default function LanguageRow({ value, onChange }: Props) {
  return (
    <View style={styles.row}>
      {LOCALES.map((item, index) => (
        <View key={item.key} style={styles.item}>
          {index > 0 ? <Text style={styles.separator}>|</Text> : null}
          <Pressable onPress={() => onChange(item.key)}>
            <Text style={[styles.label, item.key === value && styles.labelActive]}>{item.key.toUpperCase()}</Text>
          </Pressable>
        </View>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 12,
  },
  item: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  separator: {
    marginHorizontal: 8,
    color: '#cbd5e1',
  },
  label: {
    fontWeight: '600',
    color: '#94a3b8',
  },
  labelActive: {
    color: '#16a34a',
  },
});
