import { useState } from 'react';
import { router } from 'expo-router';
import { StyleSheet, View } from 'react-native';
import { Button, Text } from 'react-native-paper';

import DateField from '@/components/DateField';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { api, apiErrorMessage } from '@/lib/api';
import { SURFACE } from '@/lib/themes';

interface Offre {
  id: number;
  nom: string;
}

interface Abonnement {
  id: number;
  statut: string;
  debut_at: string | null;
  fin_at: string | null;
  offre: Offre | null;
}

interface Ecole {
  idEcole: number;
  nomEcole: string;
}

interface SubscriptionRow {
  ecole: Ecole;
  abonnement: Abonnement | null;
  days_remaining: number | null;
}

interface ConnectedUser {
  idUtilisateur: number;
  nomPrenom: string;
  droit: string;
  last_login_at: string | null;
  last_activity: string | null;
  ecole: Ecole | null;
}

interface Health {
  app_env: string;
  app_debug: boolean;
  php_version: string;
  disk_free_space: number | 'N/A';
  disk_total_space: number | 'N/A';
  db_connection: string;
}

interface PendingValidation {
  id: number;
}

export interface SupAdminDashboardData {
  subscriptionOverview: SubscriptionRow[];
  connectedUsers: ConnectedUser[];
  health: Health;
  pendingValidations: PendingValidation[];
}

function timeAgo(dateString: string | null): string {
  if (!dateString) return 'Maintenant';
  const diffMs = Date.now() - new Date(dateString).getTime();
  const minutes = Math.max(0, Math.round(diffMs / 60000));
  if (minutes < 1) return "À l'instant";
  if (minutes < 60) return `${minutes} min`;
  return `${Math.round(minutes / 60)} h`;
}

function formatHour(dateString: string | null): string {
  if (!dateString) return 'N/A';
  return new Date(dateString).toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
}

function SubscriptionRowEditor({ row, onSaved }: { row: SubscriptionRow; onSaved: (message: string) => void }) {
  const abonnement = row.abonnement!;
  const [debutAt, setDebutAt] = useState<string | null>(abonnement.debut_at ? abonnement.debut_at.slice(0, 10) : null);
  const [finAt, setFinAt] = useState<string | null>(abonnement.fin_at ? abonnement.fin_at.slice(0, 10) : null);
  const [isSaving, setIsSaving] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSave() {
    if (!debutAt || !finAt) {
      setError('Les deux dates sont requises.');
      return;
    }
    setIsSaving(true);
    setError(null);
    try {
      await api.put(`/dashboard/abonnements/${abonnement.id}/dates`, { debut_at: debutAt, fin_at: finAt });
      onSaved(`Abonnement de ${row.ecole.nomEcole} mis à jour avec succès.`);
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible de mettre à jour cet abonnement.'));
    } finally {
      setIsSaving(false);
    }
  }

  return (
    <View style={styles.dateEditor}>
      <DateField label="Début" value={debutAt} onChange={setDebutAt} />
      <DateField label="Fin" value={finAt} onChange={setFinAt} />
      {error ? <Text style={styles.error}>{error}</Text> : null}
      <Button mode="contained-tonal" compact loading={isSaving} onPress={handleSave}>
        Sauver
      </Button>
    </View>
  );
}

function SchoolRow({ row, onSaved }: { row: SubscriptionRow; onSaved: (message: string) => void }) {
  const { ecole, abonnement, days_remaining: daysRemaining } = row;
  const isLifetime = !!abonnement && abonnement.fin_at === null && abonnement.statut === 'actif';

  return (
    <View style={styles.schoolRow}>
      <View style={styles.schoolHeader}>
        <Text style={styles.schoolName}>{ecole.nomEcole}</Text>
        {abonnement ? (
          <View style={[styles.badge, abonnement.statut === 'actif' ? styles.badgeSuccess : styles.badgeWarning]}>
            <Text style={styles.badgeText}>{abonnement.statut === 'actif' ? 'Actif' : abonnement.statut}</Text>
          </View>
        ) : (
          <View style={[styles.badge, styles.badgeMuted]}>
            <Text style={styles.badgeText}>Aucun</Text>
          </View>
        )}
      </View>
      <Text style={styles.schoolMeta}>{abonnement?.offre?.nom ?? 'Aucune formule'}</Text>

      {!abonnement ? (
        <Button
          compact
          mode="outlined"
          style={styles.assignButton}
          onPress={() => router.push('/plus/configuration/ecoles')}>
          Attribuer une offre
        </Button>
      ) : isLifetime ? (
        <View style={[styles.badge, styles.badgeInfo, styles.lifetimeBadge]}>
          <Text style={styles.badgeText}>Licence à vie — accès illimité</Text>
        </View>
      ) : (
        <>
          {daysRemaining !== null ? (
            <View
              style={[
                styles.badge,
                daysRemaining < 0 ? styles.badgeDanger : daysRemaining <= 7 ? styles.badgeWarning : styles.badgeSuccess,
                styles.remainingBadge,
              ]}>
              <Text style={styles.badgeText}>{daysRemaining < 0 ? 'Expiré' : `${daysRemaining} j restants`}</Text>
            </View>
          ) : null}
          <SubscriptionRowEditor row={row} onSaved={onSaved} />
        </>
      )}
    </View>
  );
}

export default function SupAdminDashboardView({ data, onReload }: { data: SupAdminDashboardData; onReload: () => void }) {
  const [successMessage, setSuccessMessage] = useState('');
  const [successVisible, setSuccessVisible] = useState(false);

  function handleSaved(message: string) {
    setSuccessMessage(message);
    setSuccessVisible(true);
    onReload();
  }

  const { health, connectedUsers, subscriptionOverview, pendingValidations } = data;
  const diskUsedPct =
    health.disk_free_space !== 'N/A' && health.disk_total_space !== 'N/A' && health.disk_total_space > 0
      ? Math.round(((health.disk_total_space - health.disk_free_space) / health.disk_total_space) * 100)
      : null;

  return (
    <View>
      <Text style={styles.sectionTitle}>Santé de l'application</Text>
      <View style={styles.healthGrid}>
        <View style={[styles.healthCard, health.app_debug ? styles.healthCardDanger : null]}>
          <Text style={styles.healthLabel}>Mode debug</Text>
          <Text style={[styles.healthValue, health.app_debug ? styles.textDanger : styles.textSuccess]}>
            {health.app_debug ? 'ACTIVÉ' : 'DÉSACTIVÉ'}
          </Text>
        </View>
        <View style={styles.healthCard}>
          <Text style={styles.healthLabel}>Environnement</Text>
          <Text style={styles.healthValue}>{health.app_env.toUpperCase()}</Text>
          <Text style={styles.healthSub}>PHP {health.php_version}</Text>
        </View>
        <View style={[styles.healthCard, health.db_connection !== 'OK' ? styles.healthCardDanger : null]}>
          <Text style={styles.healthLabel}>Base de données</Text>
          <Text style={[styles.healthValue, health.db_connection === 'OK' ? styles.textSuccess : styles.textDanger]}>
            {health.db_connection}
          </Text>
        </View>
        <View style={styles.healthCard}>
          <Text style={styles.healthLabel}>Espace disque</Text>
          <Text style={styles.healthValue}>
            {health.disk_free_space !== 'N/A' ? `${health.disk_free_space} / ${health.disk_total_space} Go` : 'N/A'}
          </Text>
          {diskUsedPct !== null ? <Text style={styles.healthSub}>{diskUsedPct}% utilisé</Text> : null}
        </View>
      </View>

      {pendingValidations.length > 0 ? (
        <View style={styles.pendingBanner}>
          <Text style={styles.pendingText}>
            {pendingValidations.length} demande{pendingValidations.length > 1 ? 's' : ''} d'abonnement en attente de validation.
          </Text>
          <Button compact mode="contained" onPress={() => router.push('/plus/abonnement')}>
            Traiter
          </Button>
        </View>
      ) : null}

      <Text style={styles.sectionTitle}>Vue globale des écoles ({subscriptionOverview.length})</Text>
      {subscriptionOverview.length === 0 ? (
        <Text style={styles.muted}>Aucune école trouvée.</Text>
      ) : (
        subscriptionOverview.map((row) => <SchoolRow key={row.ecole.idEcole} row={row} onSaved={handleSaved} />)
      )}

      <Text style={styles.sectionTitle}>En ligne · 15 min ({connectedUsers.length})</Text>
      {connectedUsers.length === 0 ? (
        <Text style={styles.muted}>Aucun utilisateur connecté récemment.</Text>
      ) : (
        connectedUsers.map((u) => (
          <View key={u.idUtilisateur} style={styles.userRow}>
            <View style={styles.userAvatar}>
              <Text style={styles.userAvatarText}>{u.nomPrenom.charAt(0).toUpperCase()}</Text>
            </View>
            <View style={styles.userInfo}>
              <Text style={styles.userName}>{u.nomPrenom}</Text>
              <Text style={styles.userMeta}>
                {u.droit}
                {u.ecole ? ` · ${u.ecole.nomEcole}` : ''}
              </Text>
            </View>
            <View style={styles.userTime}>
              <Text style={styles.userMeta}>{formatHour(u.last_login_at)}</Text>
              <Text style={styles.userAgo}>{timeAgo(u.last_activity)}</Text>
            </View>
          </View>
        ))
      )}

      <SuccessSnackbar visible={successVisible} message={successMessage} onDismiss={() => setSuccessVisible(false)} />
    </View>
  );
}

const styles = StyleSheet.create({
  sectionTitle: {
    fontSize: 16,
    fontWeight: '700',
    color: SURFACE.text,
    marginTop: 20,
    marginBottom: 10,
  },
  muted: {
    color: SURFACE.muted,
  },
  healthGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: 12,
  },
  healthCard: {
    width: '47%',
    backgroundColor: SURFACE.card,
    borderWidth: 1,
    borderColor: SURFACE.border,
    borderRadius: 12,
    padding: 14,
  },
  healthCardDanger: {
    borderColor: '#dc2626',
  },
  healthLabel: {
    fontSize: 11,
    textTransform: 'uppercase',
    color: SURFACE.muted,
    marginBottom: 4,
  },
  healthValue: {
    fontSize: 16,
    fontWeight: '700',
    color: SURFACE.text,
  },
  healthSub: {
    fontSize: 11,
    color: SURFACE.muted,
    marginTop: 2,
  },
  textDanger: { color: '#dc2626' },
  textSuccess: { color: '#16a34a' },
  pendingBanner: {
    marginTop: 16,
    backgroundColor: '#fef3c7',
    borderRadius: 12,
    padding: 14,
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: 10,
  },
  pendingText: {
    flex: 1,
    color: '#92400e',
    fontSize: 13,
  },
  schoolRow: {
    backgroundColor: SURFACE.card,
    borderWidth: 1,
    borderColor: SURFACE.border,
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
  },
  schoolHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  schoolName: {
    fontSize: 15,
    fontWeight: '700',
    color: SURFACE.text,
    flex: 1,
  },
  schoolMeta: {
    fontSize: 12,
    color: SURFACE.muted,
    marginTop: 2,
    marginBottom: 8,
  },
  badge: {
    borderRadius: 20,
    paddingHorizontal: 10,
    paddingVertical: 4,
  },
  badgeSuccess: { backgroundColor: '#dcfce7' },
  badgeWarning: { backgroundColor: '#fef3c7' },
  badgeDanger: { backgroundColor: '#fee2e2' },
  badgeInfo: { backgroundColor: '#dbeafe' },
  badgeMuted: { backgroundColor: '#e2e8f0' },
  badgeText: {
    fontSize: 11,
    fontWeight: '700',
    color: SURFACE.text,
  },
  lifetimeBadge: {
    alignSelf: 'flex-start',
  },
  remainingBadge: {
    alignSelf: 'flex-start',
    marginBottom: 8,
  },
  assignButton: {
    alignSelf: 'flex-start',
  },
  dateEditor: {
    gap: 4,
  },
  error: {
    color: '#d33',
    fontSize: 12,
    marginBottom: 4,
  },
  userRow: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: SURFACE.card,
    borderWidth: 1,
    borderColor: SURFACE.border,
    borderRadius: 12,
    padding: 12,
    marginBottom: 8,
    gap: 10,
  },
  userAvatar: {
    width: 34,
    height: 34,
    borderRadius: 17,
    backgroundColor: '#dbeafe',
    alignItems: 'center',
    justifyContent: 'center',
  },
  userAvatarText: {
    fontWeight: '700',
    color: '#2563eb',
  },
  userInfo: {
    flex: 1,
  },
  userName: {
    fontWeight: '600',
    color: SURFACE.text,
  },
  userMeta: {
    fontSize: 11,
    color: SURFACE.muted,
  },
  userTime: {
    alignItems: 'flex-end',
  },
  userAgo: {
    fontSize: 10,
    color: '#16a34a',
    marginTop: 2,
  },
});
