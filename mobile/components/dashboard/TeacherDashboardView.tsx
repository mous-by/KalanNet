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
  id_classe: number;
  id_matiere: number;
  classe?: Classe | null;
  matiere?: Matiere | null;
}

export interface TeacherDashboardData {
  enseignant: { nom_prenom_enseignant: string; matricule: string | null } | null;
  assignments: Assignment[];
  totalClasses: number;
  totalMatieres: number;
  totalEleves: number;
  totalEmargements: number;
  heuresEmargees: number;
  totalPresences: number;
  evaluationsCount: number;
  teacherPermissions: {
    emargement: boolean;
    presence: boolean;
    evaluations: boolean;
    programmes: boolean;
    timetable: boolean;
  };
}

export default function TeacherDashboardView({ data }: { data: TeacherDashboardData }) {
  const stats = [
    { value: data.totalClasses, label: 'Classe(s)' },
    { value: data.totalMatieres, label: 'Matière(s)' },
    { value: data.totalEleves, label: 'Élève(s)' },
    { value: data.evaluationsCount, label: 'Évaluations' },
    { value: data.totalEmargements, label: 'Émargements' },
    { value: data.totalPresences, label: 'Présences' },
  ];

  const classGroups = data.assignments.reduce<Record<number, { classe: Classe; matieres: Matiere[] }>>((acc, line) => {
    if (!line.classe) return acc;
    if (!acc[line.id_classe]) acc[line.id_classe] = { classe: line.classe, matieres: [] };
    if (line.matiere) acc[line.id_classe].matieres.push(line.matiere);
    return acc;
  }, {});

  const links = [
    data.teacherPermissions.evaluations && { label: 'Notes / Évaluations', href: '/plus/evaluations' },
    data.teacherPermissions.emargement && { label: 'Émargements', href: '/plus/emargements' },
    data.teacherPermissions.presence && { label: 'Présences', href: '/plus/presences' },
    data.teacherPermissions.timetable && { label: 'Emploi du temps', href: '/plus/timetable' },
  ].filter((l): l is { label: string; href: string } => !!l);

  return (
    <View>
      {data.enseignant ? (
        <View style={styles.identityCard}>
          <Text style={styles.identityName}>{data.enseignant.nom_prenom_enseignant}</Text>
          {data.enseignant.matricule ? <Text style={styles.identityMeta}>Matricule {data.enseignant.matricule}</Text> : null}
        </View>
      ) : null}

      <View style={styles.statsGrid}>
        {stats.map((stat) => (
          <View key={stat.label} style={styles.statCard}>
            <Text style={styles.statValue}>{stat.value}</Text>
            <Text style={styles.statLabel}>{stat.label}</Text>
          </View>
        ))}
      </View>

      {links.length > 0 ? (
        <View style={styles.linksRow}>
          {links.map((link) => (
            <Button key={link.href} mode="contained-tonal" compact onPress={() => router.push(link.href as never)} style={styles.linkButton}>
              {link.label}
            </Button>
          ))}
        </View>
      ) : null}

      <Text style={styles.sectionTitle}>Mes classes et matières</Text>
      {Object.keys(classGroups).length === 0 ? (
        <Text style={styles.muted}>Aucune classe assignée pour le moment.</Text>
      ) : (
        Object.values(classGroups).map(({ classe, matieres }) => (
          <View key={classe.id_classe} style={styles.classRow}>
            <Text style={styles.className}>{classe.nom_classe}</Text>
            <Text style={styles.classMeta}>{matieres.map((m) => m.nom_matiere).join(', ') || 'Aucune matière'}</Text>
          </View>
        ))
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  identityCard: { marginBottom: 16 },
  identityName: { fontSize: 18, fontWeight: '700', color: SURFACE.text },
  identityMeta: { fontSize: 12, color: SURFACE.muted, marginTop: 2 },
  statsGrid: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  statCard: {
    width: '31%',
    backgroundColor: SURFACE.card,
    borderWidth: 1,
    borderColor: SURFACE.border,
    borderRadius: 12,
    paddingVertical: 14,
    alignItems: 'center',
  },
  statValue: { fontSize: 20, fontWeight: '700', color: SURFACE.text },
  statLabel: { fontSize: 11, color: SURFACE.muted, marginTop: 2, textAlign: 'center' },
  linksRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 16 },
  linkButton: { marginBottom: 4 },
  sectionTitle: { fontSize: 16, fontWeight: '700', color: SURFACE.text, marginTop: 20, marginBottom: 10 },
  muted: { color: SURFACE.muted },
  classRow: {
    backgroundColor: SURFACE.card,
    borderWidth: 1,
    borderColor: SURFACE.border,
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
  },
  className: { fontWeight: '600', color: SURFACE.text },
  classMeta: { fontSize: 12, color: SURFACE.muted, marginTop: 4 },
});
