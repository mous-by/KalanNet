import { useState } from 'react';
import { Pressable, StyleSheet } from 'react-native';
import { Menu, Text } from 'react-native-paper';

import { useLocale } from '@/context/LocaleContext';
import { LOCALES } from '@/lib/i18n';

const SHORT_LABEL: Record<string, string> = { fr: 'Fr', en: 'En', ar: 'Ar' };

export default function LanguageMenuButton({ color }: { color?: string }) {
  const { locale, setLocale } = useLocale();
  const [visible, setVisible] = useState(false);

  return (
    <Menu
      visible={visible}
      onDismiss={() => setVisible(false)}
      anchor={
        <Pressable style={styles.button} onPress={() => setVisible(true)}>
          <Text style={[styles.label, { color }]}>{SHORT_LABEL[locale] ?? locale.toUpperCase()}</Text>
        </Pressable>
      }>
      {LOCALES.map((item) => (
        <Menu.Item
          key={item.key}
          title={item.native}
          trailingIcon={item.key === locale ? 'check' : undefined}
          onPress={() => {
            setLocale(item.key);
            setVisible(false);
          }}
        />
      ))}
    </Menu>
  );
}

const styles = StyleSheet.create({
  button: {
    paddingHorizontal: 12,
    paddingVertical: 8,
    justifyContent: 'center',
    alignItems: 'center',
  },
  label: {
    fontWeight: '700',
    fontSize: 15,
  },
});
