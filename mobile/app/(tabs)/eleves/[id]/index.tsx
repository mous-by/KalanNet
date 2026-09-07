import { router, useLocalSearchParams } from 'expo-router';
import { RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Card, Divider, Text } from 'react-native-paper';

import { useAuth } from '@/context/AuthContext';
import { useApiGet } from '@/lib/useApi';
import { Eleve } from '@/types/api';

interface EleveDetail {
  eleve: Eleve;
  annee?: { annee: string } | null;
  paiements_recents: Array<{ id_paiement?: number; reference?: string; montant_paye?: number; date_paiement?: string }>;
  payment_summary?: { attendu?: number; paye?: number; reste?: number } | null;
  evaluations_recentes: Array<{ note?: number; matiere?: { nom_matiere?: string } }>;
  moyennes?: Record<string, unknown> | null;
}

export default function EleveDetailScreen() {
  const { id } = useLocalSearchParams<{ id: string }>();
  const { user } = useAuth();
  const { data, isLoading, error, reload } = useApiGet<EleveDetail>(`/eleves/${id}`, [id]);

  const canManage = user?.droit && ['SupAdmin', 'Admin', 'Gestionnaire', 'DAE', 'DCAP'].includes(user.droit);

  if (isLoading) return <ActivityIndicator style={styles.spinner} size="large" />;
  if (error || !data) return <Text style={styles.error}>{error ?? 'Élève introuvable.'}</Text>;

  const { eleve, payment_summary, paiements_recents, evaluations_recentes } = data;

  return (
    <ScrollView
      contentContainerStyle={styles.content}
      refreshControl={<RefreshControl refreshing={false} onRefresh={reload} />}>
      <Text style={styles.name}>
        {eleve.prenom_eleve} {eleve.nom_eleve}
      </Text>
      <Text style={styles.meta}>
        {eleve.classe?.nom_classe ?? '—'} · Matricule {eleve.matricule ?? '—'}
      </Text>

      {canManage ? (
        <View style={styles.actions}>
          <Button mode="outlined" onPress={() => router.push(`/eleves/${eleve.id_eleve}/edit`)} style={styles.actionButton}>
            Modifier
          </Button>
          {eleve.etat_dossier === 0 ? (
            <Button mode="outlined" onPress={() => router.push(`/eleves/${eleve.id_eleve}/transfer`)} style={styles.actionButton}>
              Transférer
            </Button>
          ) : (
            <Button mode="outlined" onPress={() => router.push(`/eleves/${eleve.id_eleve}/reintegrate`)} style={styles.actionButton}>
              Réintégrer
            </Button>
          )}
        </View>
      ) : null}

      {canManage ? (
        <Button
          mode="contained-tonal"
          onPress={() => router.push(`/plus/finances/new?id_eleve=${eleve.id_eleve}`)}
          style={styles.paymentButton}>
          Enregistrer un paiement
        </Button>
      ) : null}

      <Card style={styles.card}>
        <Card.Title title="Informations" />
        <Card.Content>
          <InfoRow label="Genre" value={eleve.genre_eleve} />
          <InfoRow label="Date de naissance" value={eleve.date_naissance} />
          <InfoRow label="Lieu de naissance" value={eleve.lieu_naiss} />
          <InfoRow label="Adresse" value={eleve.adresse_eleve} />
          <InfoRow label="Statut de paiement" value={eleve.statut_paiement} />
        </Card.Content>
      </Card>

      {payment_summary ? (
        <Card style={styles.card}>
          <Card.Title title="Paiements" />
          <Card.Content>
            <InfoRow label="Attendu" value={formatAmount(payment_summary.attendu)} />
            <InfoRow label="Payé" value={formatAmount(payment_summary.paye)} />
            <InfoRow label="Reste" value={formatAmount(payment_summary.reste)} />
          </Card.Content>
        </Card>
      ) : null}

      {paiements_recents?.length > 0 ? (
        <Card style={styles.card}>
          <Card.Title title="Derniers paiements" />
          <Card.Content>
            {paiements_recents.map((paiement, index) => (
              <View key={paiement.id_paiement ?? index}>
                {index > 0 ? <Divider style={styles.divider} /> : null}
                <InfoRow label={paiement.reference ?? paiement.date_paiement ?? '—'} value={formatAmount(paiement.montant_paye)} />
              </View>
            ))}
          </Card.Content>
        </Card>
      ) : null}

      {evaluations_recentes?.length > 0 ? (
        <Card style={styles.card}>
          <Card.Title title="Évaluations récentes" />
          <Card.Content>
            {evaluations_recentes.map((evaluation, index) => (
              <View key={index}>
                {index > 0 ? <Divider style={styles.divider} /> : null}
                <InfoRow label={evaluation.matiere?.nom_matiere ?? '—'} value={evaluation.note != null ? String(evaluation.note) : '—'} />
              </View>
            ))}
          </Card.Content>
        </Card>
      ) : null}
    </ScrollView>
  );
}

function InfoRow({ label, value }: { label: string; value: string | number | null | undefined }) {
  return (
    <View style={styles.infoRow}>
      <Text style={styles.infoLabel}>{label}</Text>
      <Text style={styles.infoValue}>{value ?? '—'}</Text>
    </View>
  );
}

function formatAmount(value: number | undefined | null): string {
  if (value == null) return '—';
  return `${Number(value).toLocaleString('fr-FR')} FCFA`;
}

const styles = StyleSheet.create({
  content: {
    padding: 20,
  },
  spinner: {
    marginTop: 40,
  },
  error: {
    color: '#d33',
    textAlign: 'center',
    marginTop: 40,
    padding: 20,
  },
  name: {
    fontSize: 22,
    fontWeight: 'bold',
  },
  meta: {
    opacity: 0.6,
    marginTop: 4,
    marginBottom: 16,
  },
  actions: {
    flexDirection: 'row',
    gap: 10,
    marginBottom: 16,
  },
  actionButton: {
    flex: 1,
  },
  paymentButton: {
    marginBottom: 16,
  },
  card: {
    marginBottom: 14,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 6,
  },
  infoLabel: {
    opacity: 0.6,
  },
  infoValue: {
    fontWeight: '500',
  },
  divider: {
    marginVertical: 4,
  },
});
