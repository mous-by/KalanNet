import { Pressable, StyleSheet } from 'react-native';

import { Text, View } from '@/components/Themed';
import { useAuth } from '@/context/AuthContext';

function Row({ label, value }: { label: string; value: string | null | undefined }) {
  if (!value) return null;
  return (
    <View style={styles.row}>
      <Text style={styles.rowLabel}>{label}</Text>
      <Text style={styles.rowValue}>{value}</Text>
    </View>
  );
}

export default function ProfileScreen() {
  const { user, logout } = useAuth();

  return (
    <View style={styles.container}>
      <Text style={styles.name}>{user?.nom_prenom}</Text>
      <Text style={styles.role}>{user?.droit}</Text>

      <View style={styles.section}>
        <Row label="École" value={user?.ecole?.nom} />
        <Row label="Fonction" value={user?.fonction} />
        <Row label="Email" value={user?.email} />
        <Row label="Téléphone" value={user?.telephone} />
      </View>

      <Pressable style={styles.logoutButton} onPress={logout}>
        <Text style={styles.logoutText}>Se déconnecter</Text>
      </Pressable>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    padding: 24,
  },
  name: {
    fontSize: 22,
    fontWeight: 'bold',
  },
  role: {
    fontSize: 14,
    opacity: 0.6,
    marginTop: 4,
    marginBottom: 24,
  },
  section: {
    borderTopWidth: StyleSheet.hairlineWidth,
    borderColor: 'rgba(128,128,128,0.4)',
  },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 14,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderColor: 'rgba(128,128,128,0.4)',
  },
  rowLabel: {
    opacity: 0.6,
  },
  rowValue: {
    fontWeight: '500',
  },
  logoutButton: {
    marginTop: 32,
    backgroundColor: '#d33',
    borderRadius: 10,
    paddingVertical: 14,
    alignItems: 'center',
  },
  logoutText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
});
