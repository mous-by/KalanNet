import { router } from 'expo-router';
import { Modal, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { Divider, List, Text } from 'react-native-paper';

import { useAuth } from '@/context/AuthContext';
import { useLocale } from '@/context/LocaleContext';
import { MENU_SECTIONS } from '@/lib/menuSections';
import { hasAnyPermission } from '@/lib/permissions';
import { SURFACE } from '@/lib/themes';

interface Props {
  visible: boolean;
  onClose: () => void;
}

export default function MenuDrawer({ visible, onClose }: Props) {
  const { user } = useAuth();
  const { t } = useLocale();

  function handleSelect(href: string) {
    onClose();
    router.push(href as never);
  }

  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <View style={styles.root}>
        <Pressable style={[StyleSheet.absoluteFill, styles.backdrop]} onPress={onClose} />
        <View style={styles.sheet}>
          <View style={styles.handle} />
          <Text style={styles.title}>{t('tabs.more')}</Text>
          <ScrollView contentContainerStyle={styles.content} showsVerticalScrollIndicator={false}>
            {MENU_SECTIONS.map((section) => {
              const items = section.items.filter((item) => !item.permissions || hasAnyPermission(user, item.permissions));
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
                        onPress={() => handleSelect(item.href)}
                      />
                      {index < items.length - 1 ? <Divider /> : null}
                    </View>
                  ))}
                </List.Section>
              );
            })}
          </ScrollView>
        </View>
      </View>
    </Modal>
  );
}

const styles = StyleSheet.create({
  root: {
    flex: 1,
    justifyContent: 'flex-end',
  },
  backdrop: {
    backgroundColor: 'rgba(15,23,42,0.4)',
  },
  sheet: {
    backgroundColor: SURFACE.background,
    borderTopLeftRadius: 24,
    borderTopRightRadius: 24,
    maxHeight: '80%',
    paddingTop: 10,
  },
  handle: {
    alignSelf: 'center',
    width: 40,
    height: 4,
    borderRadius: 2,
    backgroundColor: SURFACE.border,
    marginBottom: 8,
  },
  title: {
    textAlign: 'center',
    fontWeight: '700',
    fontSize: 16,
    color: SURFACE.text,
    marginBottom: 4,
  },
  content: {
    paddingBottom: 32,
  },
});
