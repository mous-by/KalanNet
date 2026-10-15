import { useMemo, useState } from 'react';
import { FlatList, Pressable, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Searchbar, Text } from 'react-native-paper';

import SelectField from '@/components/SelectField';
import { useAuth } from '@/context/AuthContext';
import { useApiGet } from '@/lib/useApi';

interface ClasseEffectif {
  nom: string;
  niveau: string | null;
  filles: number;
  garcons: number;
  total: number;
}

interface EcoleEffectif {
  id: number;
  nom: string;
  cap: string | null;
  publique: boolean;
  classes: ClasseEffectif[];
  eleves: number;
  filles: number;
  garcons: number;
  enseignants: number;
  enseignantes: number;
}

interface EffectifsReponse {
  caps: { id_cap: number; nom_cap: string }[];
  effectifs: EcoleEffectif[];
}

/** Effectifs du territoire (DAE : académie ; DCAP : CAP), en lecture seule. */
export default function EffectifsTerritoireScreen() {
  const { user } = useAuth();
  const [cap, setCap] = useState<number | null>(null);
  const [recherche, setRecherche] = useState('');
  const [ouverte, setOuverte] = useState<number | null>(null);

  const endpoint = useMemo(() => {
    const params = new URLSearchParams();
    if (cap) params.set('cap', String(cap));
    if (recherche.trim()) params.set('recherche', recherche.trim());
    const query = params.toString();
    return `/territoire/effectifs${query ? `?${query}` : ''}`;
  }, [cap, recherche]);

  const { data, isLoading, error } = useApiGet<EffectifsReponse>(endpoint, [endpoint]);
  const ecoles = data?.effectifs ?? [];
  const totaux = ecoles.reduce((t, e) => ({ eleves: t.eleves + e.eleves, enseignants: t.enseignants + e.enseignants }), { eleves: 0, enseignants: 0 });

  return (
    <View style={styles.container}>
      <View style={styles.filtres}>
        {user?.droit === 'DAE' && data?.caps?.length ? (
          <SelectField
            label="CAP"
            value={cap}
            options={[{ value: 0, label: 'Tous les CAP' }, ...data.caps.map((c) => ({ value: c.id_cap, label: c.nom_cap }))]}
            onChange={(v) => setCap((v as number) || null)}
          />
        ) : null}
        <Searchbar placeholder="Rechercher une école" value={recherche} onChangeText={setRecherche} style={styles.recherche} />
        <Text style={styles.resume}>
          {ecoles.length} école(s) · {totaux.eleves} élève(s) · {totaux.enseignants} enseignant(s)
        </Text>
      </View>

      {isLoading && !data ? <ActivityIndicator style={styles.spinner} /> : null}
      {error ? <Text style={styles.erreur}>{error}</Text> : null}

      <FlatList
        data={ecoles}
        keyExtractor={(e) => String(e.id)}
        contentContainerStyle={styles.liste}
        renderItem={({ item }) => (
          <Pressable style={styles.carte} onPress={() => setOuverte(ouverte === item.id ? null : item.id)}>
            <Text style={styles.nom}>{item.nom}</Text>
            <Text style={styles.meta}>
              {item.cap ? `${item.cap} · ` : ''}
              {item.publique ? 'Publique' : 'Privée'}
            </Text>
            <Text style={styles.chiffres}>
              {item.eleves} élèves ({item.filles} F, {item.garcons} G) · {item.enseignants} enseignants ({item.enseignantes} F)
            </Text>
            {ouverte === item.id
              ? item.classes.map((c) => (
                  <View key={c.nom} style={styles.classe}>
                    <Text style={styles.classeNom}>{c.nom}</Text>
                    <Text style={styles.classeChiffres}>
                      {c.total} ({c.filles} F, {c.garcons} G)
                    </Text>
                  </View>
                ))
              : null}
          </Pressable>
        )}
        ListEmptyComponent={!isLoading ? <Text style={styles.vide}>Aucune école.</Text> : null}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  filtres: { padding: 16, paddingBottom: 4 },
  recherche: { marginBottom: 8 },
  resume: { opacity: 0.7, marginBottom: 4 },
  spinner: { marginTop: 24 },
  erreur: { color: '#d33', padding: 16 },
  liste: { padding: 16, paddingTop: 4 },
  carte: { borderWidth: 1, borderColor: 'rgba(128,128,128,0.25)', borderRadius: 12, padding: 14, marginBottom: 10 },
  nom: { fontWeight: '700', fontSize: 15 },
  meta: { opacity: 0.6, marginTop: 2 },
  chiffres: { marginTop: 6 },
  classe: { flexDirection: 'row', justifyContent: 'space-between', paddingVertical: 6, borderTopWidth: StyleSheet.hairlineWidth, borderColor: 'rgba(128,128,128,0.3)', marginTop: 6 },
  classeNom: { fontWeight: '600' },
  classeChiffres: { opacity: 0.8 },
  vide: { textAlign: 'center', opacity: 0.6, marginTop: 30 },
});
