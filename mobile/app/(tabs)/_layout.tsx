import { useState } from 'react';
import { SymbolView } from 'expo-symbols';
import { Tabs } from 'expo-router';
import { View } from 'react-native';
import { IconButton } from 'react-native-paper';

import LanguageMenuButton from '@/components/LanguageMenuButton';
import MenuDrawer from '@/components/MenuDrawer';
import NotificationBell from '@/components/NotificationBell';
import ThemeMenuButton from '@/components/ThemeMenuButton';
import UserAvatar from '@/components/UserAvatar';
import { useAuth } from '@/context/AuthContext';
import { useLocale } from '@/context/LocaleContext';
import { useAppTheme } from '@/context/ThemeContext';
import { withOpacity } from '@/lib/themes';

export default function TabLayout() {
  const { theme } = useAppTheme();
  const { t } = useLocale();
  const { user } = useAuth();
  const [menuOpen, setMenuOpen] = useState(false);
  // Mirrors the web sidebar's students/parents menu, which is never shown to
  // a parent account — a raw, unfiltered classes list has no use for them.
  const hideClassesTab = user?.droit === 'parent';

  return (
    <View style={{ flex: 1 }}>
      <Tabs
        screenOptions={{
          headerStyle: { backgroundColor: theme.chrome },
          headerTintColor: theme.onChrome,
          headerLeft: () => <IconButton icon="menu" iconColor={theme.onChrome} onPress={() => setMenuOpen((open) => !open)} />,
          headerRight: () => (
            <View style={{ flexDirection: 'row', alignItems: 'center' }}>
              <LanguageMenuButton color={theme.onChrome} />
              <ThemeMenuButton color={theme.onChrome} />
              <NotificationBell color={theme.onChrome} />
              <UserAvatar />
            </View>
          ),
          tabBarStyle: { backgroundColor: theme.chrome },
          tabBarActiveTintColor: theme.onChrome,
          tabBarInactiveTintColor: withOpacity(theme.onChrome, 0.55),
        }}>
        <Tabs.Screen
          name="index"
          options={{
            title: t('tabs.home'),
            headerTitle: () => null,
            tabBarIcon: ({ color }) => (
              <SymbolView
                name={{ ios: 'house.fill', android: 'home', web: 'home' }}
                tintColor={color}
                size={26}
              />
            ),
          }}
        />
        <Tabs.Screen
          name="eleves"
          options={{
            title: t('tabs.students'),
            headerShown: false,
            tabBarIcon: ({ color }) => (
              <SymbolView
                name={{ ios: 'person.2.fill', android: 'people', web: 'people' }}
                tintColor={color}
                size={26}
              />
            ),
          }}
        />
        <Tabs.Screen
          name="classes"
          options={{
            title: t('tabs.classes'),
            headerShown: false,
            href: hideClassesTab ? null : undefined,
            tabBarIcon: ({ color }) => (
              <SymbolView
                name={{ ios: 'building.2.fill', android: 'school', web: 'school' }}
                tintColor={color}
                size={26}
              />
            ),
          }}
        />
        <Tabs.Screen
          name="plus"
          options={{
            title: t('tabs.more'),
            headerShown: false,
            tabBarIcon: ({ color }) => (
              <SymbolView
                name={{ ios: 'ellipsis.circle', android: 'more_horiz', web: 'more_horiz' }}
                tintColor={color}
                size={26}
              />
            ),
          }}
        />
        <Tabs.Screen
          name="profile"
          options={{
            title: 'Profil',
            href: null,
          }}
        />
      </Tabs>

      <MenuDrawer visible={menuOpen} onClose={() => setMenuOpen(false)} />
    </View>
  );
}
