import { StyleSheet, View } from 'react-native';
import { Text } from 'react-native-paper';

import { SURFACE } from '@/lib/themes';

interface Classe {
  id_classe: number;
  nom_classe: string;
}

interface Eleve {
  id_eleve: number;
  prenom_eleve: string;
  nom_eleve: string;
  classe?: Classe | null;
}

interface FinancialRow {
  eleve: Eleve;
  classe: Classe | null;
  attendu: number;
  paye: number;
  reste: number;
  retards: number;
  statut: string;
}

interface Payment {
  id_paiement: number;
  montant_paye: number | null;
  montant: number | null;
  date_paiement: string;
  eleve?: Eleve | null;
}

interface Annonce {
  id_annonce: number;
  titre: string;
  contenu: string;
  date_publication: string;
  auteur: string | null;
}

interface PublishedBulletin {
  id_eleve: number;
  nom_trimestre?: string | null;
}

export interface ParentDashboardData {
  children: Eleve[];
  payments: Payment[];
  financialRows: FinancialRow[];
  annonces: Annonce[];
  publishedBulletins: PublishedBulletin[];
  totalExpected: number;
  totalPaid: number;
  totalRemaining: number;
}

function amount(value: number): string {
  return `${Math.round(value).toLocaleString('fr-FR')} FCFA`;
}

const STATUS_STYLES: Record<string, { bg: string; fg: string }> = {
  'À jour': { bg: '#dcfce7', fg: '#166534' },
  Retard: { bg: '#fee2e2', fg: '#991b1b' },
  'En cours': { bg: '#fef3c7', fg: '#92400e' },
};

export default function ParentDashboardView({ data }: { data: ParentDashboardData }) {
  return (
    <View>
      <View style={styles.summaryRow}>
        <View style={styles.summaryCard}>
          <Text style={styles.summaryValue}>{amount(data.totalExpected)}</Text>
          <Text style={styles.summaryLabel}>Attendu</Text>
        </View>
        <View style={styles.summaryCard}>
          <Text style={[styles.summaryValue, styles.textSuccess]}>{amount(data.totalPaid)}</Text>
          <Text style={styles.summaryLabel}>Payé</Text>
        </View>
        <View style={styles.summaryCard}>
          <Text style={[styles.summaryValue, data.totalRemaining > 0 ? styles.textDanger : undefined]}>{amount(data.totalRemaining)}</Text>
          <Text style={styles.summaryLabel}>Reste</Text>
        </View>
      </View>

      <Text style={styles.sectionTitle}>Mes enfants ({data.children.length})</Text>
      {data.children.length === 0 ? (
        <Text style={styles.muted}>Aucun enfant rattaché à ce compte.</Text>
      ) : (
        data.children.map((child) => {
          const row = data.financialRows.find((r) => r.eleve.id_eleve === child.id_eleve);
          const statusStyle = row ? STATUS_STYLES[row.statut] : undefined;
          return (
            <View key={child.id_eleve} style={styles.childRow}>
              <View style={styles.childHeader}>
                <Text style={styles.childName}>
                  {child.prenom_eleve} {child.nom_eleve}
                </Text>
                {row ? (
                  <View style={[styles.badge, { backgroundColor: statusStyle?.bg ?? '#e2e8f0' }]}>
                    <Text style={[styles.badgeText, { color: statusStyle?.fg ?? SURFACE.text }]}>{row.statut}</Text>
                  </View>
                ) : null}
              </View>
              <Text style={styles.childMeta}>{child.classe?.nom_classe ?? '—'}</Text>
              {row ? (
                <Text style={styles.childMeta}>
                  {amount(row.paye)} payé sur {amount(row.attendu)}
                  {row.retards > 0 ? ` · ${row.retards} échéance(s) en retard` : ''}
                </Text>
              ) : null}
            </View>
          );
        })
      )}

      <Text style={styles.sectionTitle}>Paiements récents</Text>
      {data.payments.length === 0 ? (
        <Text style={styles.muted}>Aucun paiement enregistré.</Text>
      ) : (
        data.payments.map((payment) => (
          <View key={payment.id_paiement} style={styles.paymentRow}>
            <View style={styles.paymentInfo}>
              <Text style={styles.paymentName}>
                {payment.eleve ? `${payment.eleve.prenom_eleve} ${payment.eleve.nom_eleve}` : 'Élève'}
              </Text>
              <Text style={styles.childMeta}>{payment.date_paiement}</Text>
            </View>
            <Text style={styles.paymentAmount}>{amount(payment.montant_paye ?? payment.montant ?? 0)}</Text>
          </View>
        ))
      )}

      {data.publishedBulletins.length > 0 ? (
        <View style={styles.bulletinBanner}>
          <Text style={styles.bulletinText}>{data.publishedBulletins.length} bulletin(s) publié(s) disponible(s).</Text>
        </View>
      ) : null}

      <Text style={styles.sectionTitle}>Annonces</Text>
      {data.annonces.length === 0 ? (
        <Text style={styles.muted}>Aucune annonce récente.</Text>
      ) : (
        data.annonces.map((annonce) => (
          <View key={annonce.id_annonce} style={styles.annonceRow}>
            <Text style={styles.annonceTitle}>{annonce.titre}</Text>
            <Text style={styles.annonceContent} numberOfLines={3}>
              {annonce.contenu}
            </Text>
            <Text style={styles.childMeta}>{annonce.date_publication}</Text>
          </View>
        ))
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  summaryRow: { flexDirection: 'row', gap: 10 },
  summaryCard: {
    flex: 1,
    backgroundColor: SURFACE.card,
    borderWidth: 1,
    borderColor: SURFACE.border,
    borderRadius: 12,
    paddingVertical: 14,
    alignItems: 'center',
  },
  summaryValue: { fontSize: 14, fontWeight: '700', color: SURFACE.text },
  summaryLabel: { fontSize: 11, color: SURFACE.muted, marginTop: 4 },
  textSuccess: { color: '#16a34a' },
  textDanger: { color: '#dc2626' },
  sectionTitle: { fontSize: 16, fontWeight: '700', color: SURFACE.text, marginTop: 20, marginBottom: 10 },
  muted: { color: SURFACE.muted },
  childRow: {
    backgroundColor: SURFACE.card,
    borderWidth: 1,
    borderColor: SURFACE.border,
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
  },
  childHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  childName: { fontWeight: '600', color: SURFACE.text, flex: 1 },
  childMeta: { fontSize: 12, color: SURFACE.muted, marginTop: 2 },
  badge: { borderRadius: 20, paddingHorizontal: 10, paddingVertical: 3 },
  badgeText: { fontSize: 11, fontWeight: '700' },
  paymentRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    backgroundColor: SURFACE.card,
    borderWidth: 1,
    borderColor: SURFACE.border,
    borderRadius: 12,
    padding: 12,
    marginBottom: 8,
  },
  paymentInfo: { flex: 1 },
  paymentName: { fontWeight: '600', color: SURFACE.text },
  paymentAmount: { fontWeight: '700', color: '#1f8a4c' },
  bulletinBanner: {
    marginTop: 8,
    backgroundColor: '#dbeafe',
    borderRadius: 12,
    padding: 12,
  },
  bulletinText: { color: '#1e40af', fontSize: 13 },
  annonceRow: {
    backgroundColor: SURFACE.card,
    borderWidth: 1,
    borderColor: SURFACE.border,
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
  },
  annonceTitle: { fontWeight: '700', color: SURFACE.text },
  annonceContent: { fontSize: 13, color: SURFACE.text, marginTop: 4 },
});
