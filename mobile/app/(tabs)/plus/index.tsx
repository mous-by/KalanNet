import { router } from 'expo-router';
import { ScrollView, StyleSheet, View } from 'react-native';
import { Divider, List } from 'react-native-paper';

import { useAuth } from '@/context/AuthContext';
import { Droit } from '@/types/api';

interface MenuItem {
  label: string;
  icon: string;
  href: string;
  roles?: Droit[];
}

const SECTIONS: { title: string; items: MenuItem[] }[] = [
  {
    title: 'Pédagogie',
    items: [
      { label: 'Enseignants', icon: 'account-tie', href: '/plus/enseignants', roles: ['SupAdmin', 'Admin', 'Gestionnaire', 'DAE', 'DCAP'] },
      { label: 'Emploi du temps', icon: 'calendar-clock', href: '/plus/timetable' },
      { label: 'Évaluations', icon: 'clipboard-text', href: '/plus/evaluations' },
    ],
  },
];

export default function PlusMenuScreen() {
  const { user } = useAuth();

  return (
    <ScrollView contentContainerStyle={styles.content}>
      {SECTIONS.map((section) => {
        const items = section.items.filter((item) => !item.roles || (user?.droit && item.roles.includes(user.droit)));
        if (items.length === 0) return null;

        return (
          <List.Section key={section.title}>
            <List.Subheader>{section.title}</List.Subheader>
            {items.map((item, index) => (
              <View key={item.href}>
                <List.Item
                  title={item.label}
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
