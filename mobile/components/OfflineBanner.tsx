import { router } from 'expo-router';
import { Pressable, StyleSheet, View } from 'react-native';
import { Text } from 'react-native-paper';
import { MaterialCommunityIcons } from '@expo/vector-icons';

import { useOffline } from '@/context/OfflineContext';

// Sits at the top of a screen that supports offline writes, so the person
// filling out the form always knows whether "Enregistrer" will hit the
// server now or get queued for later.
export default function OfflineBanner() {
  const { isOnline, pendingCount, conflictCount } = useOffline();

  if (isOnline && pendingCount === 0 && conflictCount === 0) return null;

  return (
    <Pressable style={[styles.banner, !isOnline ? styles.offline : styles.pending]} onPress={() => router.push('/plus/synchronisation')}>
      <MaterialCommunityIcons name={isOnline ? 'cloud-sync-outline' : 'cloud-off-outline'} size={16} color="#fff" />
      <View style={styles.textWrap}>
        {!isOnline ? <Text style={styles.text}>Hors ligne — les enregistrements seront synchronisés au retour du réseau.</Text> : null}
        {pendingCount > 0 ? <Text style={styles.text}>{pendingCount} en attente de synchronisation.</Text> : null}
        {conflictCount > 0 ? <Text style={styles.text}>{conflictCount} conflit(s) à vérifier.</Text> : null}
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  banner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    paddingVertical: 8,
    paddingHorizontal: 14,
    marginHorizontal: -20,
    marginTop: -20,
    marginBottom: 16,
  },
  offline: {
    backgroundColor: '#78716c',
  },
  pending: {
    backgroundColor: '#b8860b',
  },
  textWrap: {
    flex: 1,
  },
  text: {
    color: '#fff',
    fontSize: 12,
  },
});
