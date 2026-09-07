import { ScrollView, StyleSheet, View } from 'react-native';
import { Button, Text } from 'react-native-paper';

import { useAuth } from '@/context/AuthContext';
import { SURFACE } from '@/lib/themes';

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
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      <Text style={styles.name}>{user?.nom_prenom}</Text>
      <Text style={styles.role}>{user?.droit}</Text>

      <View style={styles.section}>
        <Row label="École" value={user?.ecole?.nom} />
        <Row label="Fonction" value={user?.fonction} />
        <Row label="Email" value={user?.email} />
        <Row label="Téléphone" value={user?.telephone} />
      </View>

      <Button mode="contained" buttonColor="#d33" onPress={logout} style={styles.logoutButton}>
        Se déconnecter
      </Button>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: SURFACE.background,
  },
  content: {
    padding: 24,
    paddingBottom: 48,
  },
  name: {
    fontSize: 22,
    fontWeight: 'bold',
    color: SURFACE.text,
  },
  role: {
    fontSize: 14,
    color: SURFACE.muted,
    marginTop: 4,
    marginBottom: 24,
  },
  section: {
    borderTopWidth: StyleSheet.hairlineWidth,
    borderColor: SURFACE.border,
  },
  row: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 14,
    borderBottomWidth: StyleSheet.hairlineWidth,
    borderColor: SURFACE.border,
  },
  rowLabel: {
    color: SURFACE.muted,
  },
  rowValue: {
    fontWeight: '500',
    color: SURFACE.text,
  },
  logoutButton: {
    marginTop: 32,
    borderRadius: 10,
  },
});
