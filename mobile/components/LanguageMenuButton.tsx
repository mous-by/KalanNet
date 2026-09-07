import { useState } from 'react';
import { IconButton, Menu } from 'react-native-paper';

import { useLocale } from '@/context/LocaleContext';
import { LOCALES } from '@/lib/i18n';

export default function LanguageMenuButton({ color }: { color?: string }) {
  const { locale, setLocale } = useLocale();
  const [visible, setVisible] = useState(false);

  return (
    <Menu
      visible={visible}
      onDismiss={() => setVisible(false)}
      anchor={<IconButton icon="translate" iconColor={color} onPress={() => setVisible(true)} />}>
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
