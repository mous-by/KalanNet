import { router } from 'expo-router';
import { StyleSheet, View } from 'react-native';
import { Button, Text } from 'react-native-paper';

import { SURFACE } from '@/lib/themes';

interface Classe {
  id_classe: number;
  nom_classe: string;
}

interface Matiere {
  id_matiere: number;
  nom_matiere: string;
}

interface Assignment {
  id_ligneclasse: number;
  classe?: Classe | null;
  matiere?: Matiere | null;
}

interface PresenceProgressRow {
  classe: string;
  titre: string;
  date: string | null;
  hours: number;
  percent: number;
}

interface RecentEvaluation {
  id_ligneEvaluation: number;
  evaluation?: { libeller: string } | null;
  classe?: Classe | null;
  matiere?: Matiere | null;
}

interface RecentEmargement {
  id_emargement: number;
  classe?: Classe | null;
  matiere?: Matiere | null;
  date_emargement: string | null;
}

export interface TeacherDashboardData {
  enseignant: { nom_prenom_enseignant: string; specialite: string | null } | null;
  assignments: Assignment[];
  totalClasses: number;
  totalMatieres: number;
  totalEleves: number;
  totalEmargements: number;
  heuresEmargees: number;
  totalPresences: number;
  evaluationsCount: number;
  recentEmargements: RecentEmargement[];
  recentEvaluations: RecentEvaluation[];
  teacherPresenceProgressRows: PresenceProgressRow[];
  teacherPermissions: {
    emargement: boolean;
    presence: boolean;
    evaluations: boolean;
    programmes: boolean;
    timetable: boolean;
  };
}

function formatDate(value: string | null): string {
  if (!value) return '—';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return value;
  return date.toLocaleDateString('fr-FR');
}

export default function TeacherDashboardView({ data }: { data: TeacherDashboardData }) {
  const links = [
    data.teacherPermissions.emargement && { label: 'Émargements', href: '/plus/emargements', icon: 'pencil-square' },
    data.teacherPermissions.presence && { label: 'Cahier de présence', href: '/plus/presences', icon: 'clipboard-check' },
    data.teacherPermissions.evaluations && { label: 'Évaluations', href: '/plus/evaluations', icon: 'journal-check' },
    data.teacherPermissions.timetable && { label: 'Mon emploi du temps', href: '/plus/timetable', icon: 'calendar-week' },
  ].filter((l): l is { label: string; href: string; icon: string } => !!l);

  return (
    <View>
      {data.enseignant ? <Text style={styles.specialite}>{data.enseignant.specialite || 'Enseignant'}</Text> : null}

      <View style={styles.statsGrid}>
        <View style={styles.statCard}>
          <Text style={styles.statValue}>{data.totalClasses}</Text>
          <Text style={styles.statLabel}>Mes classes</Text>
        </View>
        <View style={styles.statCard}>
          <Text style={styles.statValue}>{data.totalMatieres}</Text>
          <Text style={styles.statLabel}>Mes matières</Text>
        </View>
        <View style={styles.statCard}>
          <Text style={styles.statValue}>{data.totalEleves}</Text>
          <Text style={styles.statLabel}>Élèves concernés</Text>
        </View>
        <View style={styles.statCard}>
          <Text style={styles.statValue}>{data.heuresEmargees.toLocaleString('fr-FR', { minimumFractionDigits: 1, maximumFractionDigits: 1 })}</Text>
          <Text style={styles.statLabel}>Heures émargées</Text>
        </View>
      </View>

      <Text style={styles.sectionTitle}>Progression du cahier de présence</Text>
      {data.teacherPresenceProgressRows.length === 0 ? (
        <Text style={styles.muted}>Aucune progression de présence validée pour le moment.</Text>
      ) : (
        data.teacherPresenceProgressRows.map((row, index) => (
          <View key={`${row.classe}-${row.titre}-${index}`} style={styles.progressRow}>
            <View style={styles.progressHeader}>
              <Text style={styles.progressClasse}>{row.classe}</Text>
              <Text style={styles.progressHours}>{row.hours.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} h</Text>
            </View>
            <Text style={styles.progressTitre}>{row.titre}</Text>
            <View style={styles.progressBarTrack}>
              <View style={[styles.progressBarFill, { width: `${Math.min(100, Math.max(0, row.percent))}%` }]} />
            </View>
            <View style={styles.progressFooter}>
              <Text style={styles.muted}>{formatDate(row.date)}</Text>
              <Text style={styles.progressPercent}>{Math.round(row.percent)}%</Text>
            </View>
          </View>
        ))
      )}

      <Text style={styles.sectionTitle}>Mes affectations</Text>
      {data.assignments.length === 0 ? (
        <Text style={styles.muted}>Aucune affectation enregistrée.</Text>
      ) : (
        data.assignments.map((assignment) => (
          <View key={assignment.id_ligneclasse} style={styles.assignmentRow}>
            <Text style={styles.assignmentClasse}>{assignment.classe?.nom_classe ?? 'N/A'}</Text>
            <Text style={styles.assignmentMatiere}>{assignment.matiere?.nom_matiere ?? 'N/A'}</Text>
          </View>
        ))
      )}

      {data.teacherPermissions.evaluations ? (
        <>
          <Text style={styles.sectionTitle}>Dernières évaluations</Text>
          {data.recentEvaluations.length === 0 ? (
            <Text style={styles.muted}>Aucune évaluation récente.</Text>
          ) : (
            data.recentEvaluations.map((line) => (
              <View key={line.id_ligneEvaluation} style={styles.assignmentRow}>
                <Text style={styles.assignmentClasse}>{line.evaluation?.libeller ?? 'Évaluation'}</Text>
                <Text style={styles.assignmentMatiere}>
                  {line.classe?.nom_classe ?? 'N/A'} · {line.matiere?.nom_matiere ?? 'N/A'}
                </Text>
              </View>
            ))
          )}
        </>
      ) : null}

      <Text style={styles.sectionTitle}>Mes activités</Text>
      <View style={styles.activityCard}>
        <View style={styles.activityRow}>
          <Text style={styles.muted}>Émargements</Text>
          <Text style={styles.activityValue}>{data.totalEmargements}</Text>
        </View>
        <View style={styles.activityRow}>
          <Text style={styles.muted}>Présences</Text>
          <Text style={styles.activityValue}>{data.totalPresences}</Text>
        </View>
        <View style={[styles.activityRow, styles.activityRowLast]}>
          <Text style={styles.muted}>Évaluations</Text>
          <Text style={styles.activityValue}>{data.evaluationsCount}</Text>
        </View>
      </View>

      {links.length > 0 ? (
        <>
          <Text style={styles.sectionTitle}>Accès rapides</Text>
          <View style={styles.linksColumn}>
            {links.map((link) => (
              <Button
                key={link.href}
                mode="outlined"
                icon={link.icon}
                onPress={() => router.push(link.href as never)}
                contentStyle={styles.linkButtonContent}
                style={styles.linkButton}>
                {link.label}
              </Button>
            ))}
          </View>
        </>
      ) : null}

      {data.teacherPermissions.emargement ? (
        <>
          <Text style={styles.sectionTitle}>Derniers émargements</Text>
          {data.recentEmargements.length === 0 ? (
            <Text style={styles.muted}>Aucun émargement récent.</Text>
          ) : (
            data.recentEmargements.map((emargement) => (
              <View key={emargement.id_emargement} style={styles.assignmentRow}>
                <Text style={styles.assignmentClasse}>{emargement.matiere?.nom_matiere ?? 'Matière'}</Text>
                <Text style={styles.assignmentMatiere}>
                  {emargement.classe?.nom_classe ?? 'Classe'} · {formatDate(emargement.date_emargement)}
                </Text>
              </View>
            ))
          )}
        </>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  specialite: { fontSize: 13, color: SURFACE.muted, marginBottom: 12, textTransform: 'capitalize' },
  statsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  statCard: {
    width: '47%',
    backgroundColor: SURFACE.card,
    borderWidth: 1,
    borderColor: SURFACE.border,
    borderRadius: 12,
    paddingVertical: 14,
    alignItems: 'center',
  },
  statValue: { fontSize: 20, fontWeight: '700', color: SURFACE.text },
  statLabel: { fontSize: 11, color: SURFACE.muted, marginTop: 2, textAlign: 'center' },
  sectionTitle: { fontSize: 16, fontWeight: '700', color: SURFACE.text, marginTop: 20, marginBottom: 10 },
  muted: { color: SURFACE.muted },
  progressRow: {
    backgroundColor: SURFACE.card,
    borderWidth: 1,
    borderColor: SURFACE.border,
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
  },
  progressHeader: { flexDirection: 'row', justifyContent: 'space-between' },
  progressClasse: { fontWeight: '700', color: SURFACE.text },
  progressHours: { fontSize: 12, color: SURFACE.muted },
  progressTitre: { fontSize: 13, color: SURFACE.text, marginTop: 2, marginBottom: 8 },
  progressBarTrack: { height: 8, borderRadius: 4, backgroundColor: SURFACE.border, overflow: 'hidden' },
  progressBarFill: { height: 8, borderRadius: 4, backgroundColor: '#16a34a' },
  progressFooter: { flexDirection: 'row', justifyContent: 'space-between', marginTop: 6 },
  progressPercent: { fontWeight: '700', color: '#2563eb' },
  assignmentRow: {
    backgroundColor: SURFACE.card,
    borderWidth: 1,
    borderColor: SURFACE.border,
    borderRadius: 12,
    padding: 14,
    marginBottom: 8,
  },
  assignmentClasse: { fontWeight: '600', color: SURFACE.text },
  assignmentMatiere: { fontSize: 12, color: SURFACE.muted, marginTop: 2 },
  activityCard: {
    backgroundColor: SURFACE.card,
    borderWidth: 1,
    borderColor: SURFACE.border,
    borderRadius: 12,
    paddingHorizontal: 14,
  },
  activityRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 12,
    borderBottomWidth: 1,
    borderBottomColor: SURFACE.border,
  },
  activityRowLast: { borderBottomWidth: 0 },
  activityValue: { fontWeight: '700', color: SURFACE.text },
  linksColumn: { gap: 8 },
  linkButton: { borderRadius: 10 },
  linkButtonContent: { justifyContent: 'flex-start', paddingVertical: 4 },
});
