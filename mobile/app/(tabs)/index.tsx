import { useCallback, useEffect, useState } from 'react';
import { ActivityIndicator, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { Text } from 'react-native-paper';

import AdminDashboardView, { AdminDashboardData } from '@/components/dashboard/AdminDashboardView';
import ParentDashboardView, { ParentDashboardData } from '@/components/dashboard/ParentDashboardView';
import SupAdminDashboardView, { SupAdminDashboardData } from '@/components/dashboard/SupAdminDashboardView';
import TeacherDashboardView, { TeacherDashboardData } from '@/components/dashboard/TeacherDashboardView';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
import { SURFACE } from '@/lib/themes';

type DashboardData = Record<string, unknown>;

const STAFF_ROLES = ['Admin', 'Gestionnaire', 'DAE', 'DCAP'];

function humanizeKey(key: string): string {
  return key.replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase());
}

function formatValue(value: unknown): string {
  if (value === null || value === undefined) return '—';
  if (Array.isArray(value)) return `${value.length} élément(s)`;
  if (typeof value === 'object') return `${Object.keys(value).length} champ(s)`;
  return String(value);
}

// Last-resort fallback for any role without a dedicated dashboard view —
// renders whatever the API returns generically.
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

  const isSupAdmin = user?.droit === 'SupAdmin';
  const isStaff = user?.droit && STAFF_ROLES.includes(user.droit);
  const isTeacher = user?.droit === 'enseignant';
  const isParent = user?.droit === 'parent';

  return (
    <ScrollView
      style={styles.container}
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={isRefreshing} onRefresh={handleRefresh} />}>
      <View style={styles.headerRow}>
        <View style={styles.headerLeft}>
          <Text style={styles.greeting}>Bonjour, {user?.nom_prenom ?? ''} 👋</Text>
          <Text style={styles.role}>{user?.fonction ?? user?.droit}</Text>
          {user?.ecole?.nom ? <Text style={styles.school}>{user.ecole.nom}</Text> : null}
        </View>
        {isStaff && typeof data?.anneeEnCours === 'object' && data?.anneeEnCours ? (
          <View style={styles.anneeBlock}>
            <Text style={styles.anneeLabel}>Année scolaire</Text>
            <View style={styles.anneeBox}>
              <Text style={styles.anneeValue}>{(data.anneeEnCours as { annee: string }).annee}</Text>
            </View>
          </View>
        ) : null}
      </View>

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
      ) : isSupAdmin ? (
        <SupAdminDashboardView data={data as unknown as SupAdminDashboardData} onReload={() => load({ silent: true })} />
      ) : isStaff ? (
        <AdminDashboardView data={data as unknown as AdminDashboardData} />
      ) : isTeacher ? (
        <TeacherDashboardView data={data as unknown as TeacherDashboardData} />
      ) : isParent ? (
        <ParentDashboardView data={data as unknown as ParentDashboardData} />
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
  headerRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'flex-start',
    marginBottom: 20,
  },
  headerLeft: {
    flex: 1,
  },
  greeting: {
    fontSize: 20,
    fontWeight: 'bold',
    color: SURFACE.text,
  },
  role: {
    fontSize: 13,
    color: SURFACE.muted,
    marginTop: 4,
  },
  school: {
    fontSize: 13,
    color: SURFACE.muted,
  },
  anneeBlock: {
    marginLeft: 12,
  },
  anneeLabel: {
    fontSize: 11,
    color: SURFACE.muted,
    marginBottom: 4,
    textAlign: 'right',
  },
  anneeBox: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: SURFACE.card,
    borderWidth: 1,
    borderColor: SURFACE.border,
    borderRadius: 8,
    paddingHorizontal: 10,
    paddingVertical: 6,
  },
  anneeValue: {
    fontSize: 13,
    fontWeight: '700',
    color: SURFACE.text,
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
