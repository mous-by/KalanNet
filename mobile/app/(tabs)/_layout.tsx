import { SymbolView } from 'expo-symbols';
import { Tabs } from 'expo-router';
import { View } from 'react-native';

import LanguageMenuButton from '@/components/LanguageMenuButton';
import NotificationBell from '@/components/NotificationBell';
import ThemeMenuButton from '@/components/ThemeMenuButton';
import { useLocale } from '@/context/LocaleContext';
import { useAppTheme } from '@/context/ThemeContext';
import { withOpacity } from '@/lib/themes';

export default function TabLayout() {
  const { theme } = useAppTheme();
  const { t } = useLocale();

  return (
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
          title: t('tabs.profile'),
          tabBarIcon: ({ color }) => (
            <SymbolView
              name={{ ios: 'person.fill', android: 'person', web: 'person' }}
              tintColor={color}
              size={26}
            />
          ),
        }}
      />
    </Tabs>
  );
}
