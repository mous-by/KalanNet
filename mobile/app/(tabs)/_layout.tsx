import { useState } from 'react';
import { SymbolView } from 'expo-symbols';
import { Tabs } from 'expo-router';
import { View } from 'react-native';

import LanguageMenuButton from '@/components/LanguageMenuButton';
import MenuDrawer from '@/components/MenuDrawer';
import NotificationBell from '@/components/NotificationBell';
import ThemeMenuButton from '@/components/ThemeMenuButton';
import { useLocale } from '@/context/LocaleContext';
import { useAppTheme } from '@/context/ThemeContext';
import { withOpacity } from '@/lib/themes';

export default function TabLayout() {
  const { theme } = useAppTheme();
  const { t } = useLocale();
  const [menuOpen, setMenuOpen] = useState(false);

  return (
    <View style={{ flex: 1 }}>
      <Tabs
        screenOptions={{
          headerStyle: { backgroundColor: theme.chrome },
          headerTintColor: theme.onChrome,
          headerRight: () => (
            <View style={{ flexDirection: 'row', alignItems: 'center' }}>
              <LanguageMenuButton color={theme.onChrome} />
              <ThemeMenuButton color={theme.onChrome} />
              <NotificationBell color={theme.onChrome} />
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
            tabBarIcon: ({ color }) => (
              <SymbolView
                name={{ ios: 'house.fill', android: 'home', web: 'home' }}
                tintColor={color}
                size={26}
              />
            ),
          }}
          listeners={{ tabPress: () => setMenuOpen(false) }}
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
          listeners={{ tabPress: () => setMenuOpen(false) }}
        />
        <Tabs.Screen
          name="classes"
          options={{
            title: t('tabs.classes'),
            headerShown: false,
            tabBarIcon: ({ color }) => (
              <SymbolView
                name={{ ios: 'building.2.fill', android: 'school', web: 'school' }}
                tintColor={color}
                size={26}
              />
            ),
          }}
          listeners={{ tabPress: () => setMenuOpen(false) }}
        />
        <Tabs.Screen
          name="plus"
          options={{
            title: t('tabs.more'),
            headerShown: false,
            tabBarIcon: ({ color }) => (
              <SymbolView
                name={{ ios: menuOpen ? 'xmark.circle.fill' : 'line.horizontal.3', android: menuOpen ? 'close' : 'menu', web: menuOpen ? 'close' : 'menu' }}
                tintColor={color}
                size={26}
              />
            ),
          }}
          listeners={{
            tabPress: (e) => {
              // Don't navigate to the "/plus" screen — toggle the drawer
              // overlay instead, and flip it closed again on a second tap.
              e.preventDefault();
              setMenuOpen((open) => !open);
            },
          }}
        />
        <Tabs.Screen
          name="profile"
          options={{
            title: t('tabs.profile'),
            tabBarIcon: ({ color }) => (
              <SymbolView
                name={{ ios: 'person.fill', android: 'person', web: 'person' }}
                tintColor={color}
                size={26}
              />
            ),
          }}
          listeners={{ tabPress: () => setMenuOpen(false) }}
        />
      </Tabs>

      <MenuDrawer visible={menuOpen} onClose={() => setMenuOpen(false)} />
    </View>
  );
}
