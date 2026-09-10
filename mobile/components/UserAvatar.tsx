import { useState } from 'react';
import { router } from 'expo-router';
import { Image, Pressable, StyleSheet, View } from 'react-native';
import { Menu } from 'react-native-paper';
import { MaterialCommunityIcons } from '@expo/vector-icons';

import { useAuth } from '@/context/AuthContext';

// Tapping the avatar opens a dropdown (Profil / Déconnexion) matching the
// web navbar's user menu, instead of navigating straight to the profile screen.
export default function UserAvatar() {
  const { user, logout } = useAuth();
  const [visible, setVisible] = useState(false);

  return (
    <Menu
      visible={visible}
      onDismiss={() => setVisible(false)}
      anchor={
        <Pressable style={styles.wrapper} onPress={() => setVisible(true)}>
          {user?.photo_url ? (
            <Image source={{ uri: user.photo_url }} style={styles.circle} />
          ) : (
            <View style={styles.circle}>
              <MaterialCommunityIcons name="account" size={20} color="#ffffff" />
            </View>
          )}
          <View style={styles.onlineDot} />
        </Pressable>
      }>
      <Menu.Item
        leadingIcon="account-outline"
        title="Profil"
        onPress={() => {
          setVisible(false);
          router.push('/profile');
        }}
      />
      <Menu.Item
        leadingIcon="logout"
        title="Déconnexion"
        onPress={() => {
          setVisible(false);
          logout();
        }}
      />
    </Menu>
  );
}

const styles = StyleSheet.create({
  wrapper: {
    marginLeft: 4,
    marginRight: 8,
  },
  circle: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: 'rgba(255,255,255,0.25)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  onlineDot: {
    position: 'absolute',
    right: 0,
    bottom: 0,
    width: 10,
    height: 10,
    borderRadius: 5,
    backgroundColor: '#22c55e',
    borderWidth: 2,
    borderColor: '#ffffff',
  },
});
