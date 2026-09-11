import { StyleSheet, Text, View } from 'react-native';

// Full-screen takeover shown the moment ANY API call returns the maintenance
// response (registered in lib/api.ts, set via AuthContext) — mirrors the
// web's CheckMaintenanceMode middleware, which replaces every page the
// same way regardless of which one was requested.
export default function MaintenanceScreen({ message }: { message: string }) {
  return (
    <View style={styles.container}>
      <View style={styles.card}>
        <Text style={styles.icon}>🛠️</Text>
        <Text style={styles.title}>
          <Text style={{ color: '#4ade80' }}>KAL</Text>
          <Text style={{ color: '#facc15' }}>AN</Text>
          <Text style={{ color: '#f87171' }}>NET</Text>
        </Text>
        <Text style={styles.message}>{message}</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#0f172a',
    alignItems: 'center',
    justifyContent: 'center',
    padding: 24,
  },
  card: {
    maxWidth: 340,
    backgroundColor: '#1c2b22',
    borderRadius: 16,
    padding: 32,
    alignItems: 'center',
  },
  icon: { fontSize: 44, marginBottom: 12 },
  title: { fontSize: 22, fontWeight: '800', marginBottom: 12 },
  message: { color: '#a9b3a2', fontSize: 15, textAlign: 'center', lineHeight: 22 },
});
