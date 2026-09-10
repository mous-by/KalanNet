import { router } from 'expo-router';
import { ScrollView, StyleSheet, View } from 'react-native';
import { Divider, List } from 'react-native-paper';

import { useAuth } from '@/context/AuthContext';
import { useLocale } from '@/context/LocaleContext';
import { MENU_SECTIONS, isMenuItemVisible } from '@/lib/menuSections';

// Reachable only if something ever pushes to "/plus" directly — the tab bar
// button now toggles the MenuDrawer overlay instead of navigating here (see
// (tabs)/_layout.tsx). Kept as a plain fallback showing the same sections.
export default function PlusMenuScreen() {
  const { user } = useAuth();
  const { t } = useLocale();

  return (
    <ScrollView contentContainerStyle={styles.content}>
      {MENU_SECTIONS.map((section) => {
        const items = section.items.filter((item) => isMenuItemVisible(user, item));
        if (items.length === 0) return null;

        return (
          <List.Section key={section.titleKey}>
            <List.Subheader>{t(section.titleKey)}</List.Subheader>
            {items.map((item, index) => (
              <View key={item.href}>
                <List.Item
                  title={t(item.labelKey)}
                  left={(props) => <List.Icon {...props} icon={item.icon} />}
                  right={(props) => <List.Icon {...props} icon="chevron-right" />}
                  onPress={() => router.push(item.href as never)}
                />
                {index < items.length - 1 ? <Divider /> : null}
              </View>
            ))}
          </List.Section>
        );
      })}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: {
    paddingBottom: 40,
  },
});
