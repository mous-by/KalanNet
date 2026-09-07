import { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { Text } from 'react-native-paper';

import AdminDashboardView, { AdminDashboardData } from '@/components/dashboard/AdminDashboardView';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
import { SURFACE } from '@/lib/themes';

type DashboardData = Record<string, unknown>;

const STAFF_ROLES = ['SupAdmin', 'Admin', 'Gestionnaire', 'DAE', 'DCAP'];

function humanizeKey(key: string): string {
  return key.replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase());
}

function formatValue(value: unknown): string {
  if (value === null || value === undefined) return '—';
  if (Array.isArray(value)) return `${value.length} élément(s)`;
  if (typeof value === 'object') return `${Object.keys(value).length} champ(s)`;
  return String(value);
}

// Fallback for roles without a dedicated dashboard view yet (enseignant,
// parent) — renders whatever the API returns generically.
function GenericDashboardFields({ data }: { data: DashboardData }) {
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

  const isStaff = user?.droit && STAFF_ROLES.includes(user.droit);

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={isRefreshing} onRefresh={handleRefresh} />}>
      <Text style={styles.greeting}>Bonjour, {user?.nom_prenom ?? ''} 👋</Text>
      <Text style={styles.school}>
        {user?.fonction ?? user?.droit}
        {user?.ecole?.nom ? ` · ${user.ecole.nom}` : ''}
      </Text>

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
      ) : isStaff ? (
        <AdminDashboardView data={data as unknown as AdminDashboardData} />
      ) : (
        <GenericDashboardFields data={data ?? {}} />
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: SURFACE.background,
  },
  content: {
    padding: 20,
  },
  greeting: {
    fontSize: 22,
    fontWeight: 'bold',
    color: SURFACE.text,
  },
  school: {
    fontSize: 14,
    color: SURFACE.muted,
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
    color: SURFACE.muted,
  },
  grid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  card: {
    width: '47%',
    backgroundColor: SURFACE.card,
    borderWidth: 1,
    borderColor: SURFACE.border,
    borderRadius: 12,
    padding: 14,
  },
  cardLabel: {
    fontSize: 12,
    color: SURFACE.muted,
    marginBottom: 6,
  },
  cardValue: {
    fontSize: 18,
    fontWeight: '600',
    color: SURFACE.text,
  },
});
