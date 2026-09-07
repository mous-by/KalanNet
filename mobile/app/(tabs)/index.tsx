import { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, RefreshControl, ScrollView, StyleSheet } from 'react-native';

import { Text, View } from '@/components/Themed';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';

type DashboardData = Record<string, unknown>;

function humanizeKey(key: string): string {
  return key.replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase());
}

function formatValue(value: unknown): string {
  if (value === null || value === undefined) return '—';
  if (Array.isArray(value)) return `${value.length} élément(s)`;
  if (typeof value === 'object') return `${Object.keys(value).length} champ(s)`;
  return String(value);
}

// The web dashboard's shape differs a lot by role (SupAdmin/Admin/enseignant/
// parent) and isn't fixed — see docs/API.md. This renders whatever comes
// back generically as a starting point; each role will get a dedicated,
// hand-designed screen once the mobile UI work moves past this scaffold.
function DashboardFields({ data }: { data: DashboardData }) {
  const entries = Object.entries(data);

  if (entries.length === 0) {
    return <Text style={styles.muted}>Aucune donnée à afficher.</Text>;
  }

  return (
    <View style={styles.grid}>
      {entries.map(([key, value]) => (
        <View key={key} style={styles.card}>
          <Text style={styles.cardLabel}>{humanizeKey(key)}</Text>
          <Text style={styles.cardValue}>{formatValue(value)}</Text>
        </View>
      ))}
    </View>
  );
}

export default function DashboardScreen() {
  const { user, subscriptionBlocked } = useAuth();
  const [data, setData] = useState<DashboardData | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);

  const load = useCallback(async (opts: { silent?: boolean } = {}) => {
    if (!opts.silent) setIsLoading(true);
    setError(null);
    try {
      const { data } = await api.get<DashboardData>('/dashboard');
      setData(data);
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible de charger le tableau de bord.'));
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  }, []);

  useEffect(() => {
    load();
  }, [load]);

  function handleRefresh() {
    setIsRefreshing(true);
    load({ silent: true });
  }

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={isRefreshing} onRefresh={handleRefresh} />}>
      <Text style={styles.greeting}>Bonjour, {user?.nom_prenom ?? ''}</Text>
      <Text style={styles.school}>{user?.ecole?.nom ?? user?.droit}</Text>

      {subscriptionBlocked ? (
        <View style={styles.warningBanner}>
          <Text style={styles.warningText}>
            L'abonnement de votre école a expiré. Certaines fonctionnalités sont bloquées.
          </Text>
        </View>
      ) : null}

      {isLoading ? (
        <ActivityIndicator style={styles.spinner} size="large" />
      ) : error ? (
        <Text style={styles.error}>{error}</Text>
      ) : (
        <DashboardFields data={data ?? {}} />
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  content: {
    padding: 20,
  },
  greeting: {
    fontSize: 22,
    fontWeight: 'bold',
  },
  school: {
    fontSize: 14,
    opacity: 0.6,
    marginTop: 4,
    marginBottom: 20,
  },
  warningBanner: {
    backgroundColor: '#fdecea',
    borderRadius: 10,
    padding: 14,
    marginBottom: 20,
  },
  warningText: {
    color: '#9c2b1f',
    fontSize: 14,
  },
  spinner: {
    marginTop: 40,
  },
  error: {
    color: '#d33',
    fontSize: 15,
  },
  muted: {
    opacity: 0.6,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  card: {
    width: '47%',
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 12,
    padding: 14,
  },
  cardLabel: {
    fontSize: 12,
    opacity: 0.6,
    marginBottom: 6,
  },
  cardValue: {
    fontSize: 18,
    fontWeight: '600',
  },
});
