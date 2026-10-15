import { router } from 'expo-router';
import { StyleSheet, View } from 'react-native';
import { Button, Text } from 'react-native-paper';

export interface TerritoireDashboardData {
  role: 'DAE' | 'DCAP';
  territoire: string | null;
  stats: {
    caps?: number;
    ecoles: number;
    ecoles_publiques?: number;
    ecoles_privees?: number;
    enseignants: number;
    eleves: number;
    classes: number;
    par_cap?: { id_cap: number; nom_cap: string; ecoles: number; eleves: number; enseignants: number; directeur: string | null }[];
    liste_ecoles?: { id: number; nom: string; type: string | null; publique: boolean; eleves: number; enseignants: number }[];
  };
}

/** Tableau de bord du DAE (académie) ou du DCAP (CAP) : chiffres du territoire, en lecture seule. */
export default function TerritoireDashboardView({ data }: { data: TerritoireDashboardData }) {
  const s = data.stats;
  const tuiles: [string, number | undefined][] = [
    ...(data.role === 'DAE' ? ([['CAP', s.caps]] as [string, number | undefined][]) : []),
    ['Écoles', s.ecoles],
    ['Élèves', s.eleves],
    ['Enseignants', s.enseignants],
    ['Classes', s.classes],
  ];

  return (
    <View>
      <Text style={styles.territoire}>{data.role === 'DAE' ? 'Académie' : 'CAP'} : {data.territoire ?? '—'}</Text>
      <View style={styles.grille}>
        {tuiles.map(([libelle, valeur]) => (
          <View key={libelle} style={styles.tuile}>
            <Text style={styles.valeur}>{valeur ?? 0}</Text>
            <Text style={styles.libelle}>{libelle}</Text>
          </View>
        ))}
      </View>

      <View style={styles.actions}>
        <Button mode="contained" icon="account-group-outline" onPress={() => router.push('/plus/territoire/effectifs')} style={styles.action}>
          Effectifs
        </Button>
        <Button mode="outlined" icon="bullhorn-outline" onPress={() => router.push('/plus/territoire/annonces')} style={styles.action}>
          Annonces
        </Button>
      </View>

      <Text style={styles.section}>{data.role === 'DAE' ? 'Par CAP' : 'Écoles du CAP'}</Text>
      {(s.par_cap ?? []).map((cap) => (
        <View key={cap.id_cap} style={styles.ligne}>
          <Text style={styles.nom}>{cap.nom_cap}</Text>
          <Text style={styles.meta}>
            {cap.ecoles} écoles · {cap.eleves} élèves · {cap.enseignants} enseignants{cap.directeur ? ` · DCAP : ${cap.directeur}` : ''}
          </Text>
        </View>
      ))}
      {(s.liste_ecoles ?? []).map((ecole) => (
        <View key={ecole.id} style={styles.ligne}>
          <Text style={styles.nom}>{ecole.nom}</Text>
          <Text style={styles.meta}>
            {ecole.publique ? 'Publique' : 'Privée'} · {ecole.eleves} élèves · {ecole.enseignants} enseignants
          </Text>
        </View>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  territoire: { fontWeight: '700', fontSize: 16, marginBottom: 12 },
  grille: { flexDirection: 'row', flexWrap: 'wrap', gap: 10 },
  tuile: { flexBasis: '47%', flexGrow: 1, borderWidth: 1, borderColor: 'rgba(128,128,128,0.25)', borderRadius: 12, padding: 14 },
  valeur: { fontSize: 22, fontWeight: '700' },
  libelle: { opacity: 0.7, marginTop: 2 },
  actions: { flexDirection: 'row', gap: 10, marginTop: 16 },
  action: { flex: 1 },
  section: { fontWeight: '700', fontSize: 15, marginTop: 22, marginBottom: 8 },
  ligne: { paddingVertical: 10, borderBottomWidth: StyleSheet.hairlineWidth, borderColor: 'rgba(128,128,128,0.3)' },
  nom: { fontWeight: '600' },
  meta: { opacity: 0.7, marginTop: 2, fontSize: 13 },
});
