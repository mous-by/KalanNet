import { useEffect, useMemo, useState } from 'react';
import { router, useLocalSearchParams } from 'expo-router';
import { ScrollView, StyleSheet, View } from 'react-native';
import { Button, Chip, IconButton, Text, TextInput } from 'react-native-paper';

import DateField from '@/components/DateField';
import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
import { formatMontant } from '@/lib/currency';
import { LIBELLES_MOTIF, PlanificationsReponse } from '@/lib/formules';
import { useApiGet } from '@/lib/useApi';

interface LigneTranche {
  montant: string;
  date_limite: string;
}

const chiffres = (v: string) => v.replace(/\D/g, '');

// Nouvelle formule de paiement pour une ou plusieurs classes (mêmes règles que le web).
export default function NouvelleFormuleScreen() {
  const { user } = useAuth();
  const params = useLocalSearchParams<{ classe?: string; annee?: string }>();
  const { data } = useApiGet<PlanificationsReponse>('/finances/planifications', []);

  const [classes, setClasses] = useState<number[]>(params.classe ? [Number(params.classe)] : []);
  const [idAnnee, setIdAnnee] = useState<number | null>(params.annee ? Number(params.annee) : null);
  const [type, setType] = useState<string | null>(null);
  const [debut, setDebut] = useState('');
  const [fin, setFin] = useState('');
  const [montant, setMontant] = useState('');
  const [tranches, setTranches] = useState<LigneTranche[]>([
    { montant: '', date_limite: '' },
    { montant: '', date_limite: '' },
  ]);
  const [erreurs, setErreurs] = useState<string[]>([]);
  const [envoi, setEnvoi] = useState(false);

  const publique = !!data?.est_publique;
  const parTranche = type === 'par_tranche';
  const min = data?.tranches_min ?? 2;
  const max = data?.tranches_max ?? 6;

  // Année : courante par défaut, et dates proposées d'après elle.
  useEffect(() => {
    if (data && idAnnee === null) setIdAnnee(data.annee_courante ?? data.annees[0]?.id ?? null);
  }, [data, idAnnee]);
  useEffect(() => {
    if (publique) setType('cooperative');
  }, [publique]);
  useEffect(() => {
    const annee = data?.annees.find((a) => a.id === idAnnee);
    if (annee?.date_debut && !debut) setDebut(String(annee.date_debut).slice(0, 10));
    if (annee?.date_fin && !fin) setFin(String(annee.date_fin).slice(0, 10));
  }, [data, idAnnee, debut, fin]);

  const types = useMemo(
    () =>
      publique
        ? [{ value: 'cooperative', label: LIBELLES_MOTIF.cooperative }]
        : [
            { value: 'mensuelle', label: LIBELLES_MOTIF.mensuelle },
            { value: 'trimestrielle', label: LIBELLES_MOTIF.trimestrielle },
            { value: 'annuelle', label: LIBELLES_MOTIF.annuelle },
            { value: 'par_tranche', label: 'Par tranches' },
          ],
    [publique],
  );

  const totalTranches = tranches.reduce((somme, t) => somme + (Number(t.montant) || 0), 0);
  const valide =
    classes.length > 0 &&
    !!idAnnee &&
    !!type &&
    !!debut &&
    (parTranche ? tranches.every((t) => Number(t.montant) > 0 && t.date_limite) : !!fin && Number(montant) > 0);

  function basculerClasse(id: number) {
    setClasses((liste) => (liste.includes(id) ? liste.filter((x) => x !== id) : [...liste, id]));
  }

  async function enregistrer() {
    setErreurs([]);
    setEnvoi(true);
    try {
      const derniere = tranches[tranches.length - 1]?.date_limite;
      const corps = {
        id_classes: classes,
        id_annee: idAnnee,
        motif: [type],
        date_debut: [debut],
        // Par tranches : la date de fin et le total viennent des tranches (calculés par le serveur).
        date_fin: [parTranche ? derniere : fin],
        montant: [parTranche ? totalTranches : Number(montant)],
        ...(parTranche
          ? {
              row_key: ['r0'],
              tranches: { r0: { montant: tranches.map((t) => Number(t.montant)), date_limite: tranches.map((t) => t.date_limite) } },
            }
          : {}),
      };
      const { data: reponse } = await api.post<{ created: number; errors: string[]; message: string }>('/finances/planifications', corps);
      if (reponse.errors?.length) {
        setErreurs([reponse.message, ...reponse.errors]);
      } else {
        router.back();
      }
    } catch (err) {
      const details = (err as { response?: { data?: { errors?: unknown } } })?.response?.data?.errors;
      setErreurs(Array.isArray(details) && details.length ? (details as string[]) : [apiErrorMessage(err, 'Impossible d’enregistrer la formule.')]);
    } finally {
      setEnvoi(false);
    }
  }

  return (
    <ScrollView contentContainerStyle={styles.contenu}>
      <Text style={styles.libelle}>Classe(s)</Text>
      <View style={styles.puces}>
        {(data?.classes ?? []).map((c) => (
          <Chip key={c.id_classe} selected={classes.includes(c.id_classe)} showSelectedCheck onPress={() => basculerClasse(c.id_classe)} style={styles.puce}>
            {c.nom_classe}
          </Chip>
        ))}
      </View>

      <SelectField label="Année scolaire" value={idAnnee} options={(data?.annees ?? []).map((a) => ({ value: a.id, label: a.annee }))} onChange={(v) => setIdAnnee(Number(v))} />
      <SelectField label="Type de formule" value={type} options={types} onChange={(v) => setType(String(v))} disabled={publique} />
      <DateField label="Date de début" value={debut} onChange={setDebut} />

      {parTranche ? (
        <View style={styles.bloc}>
          <Text style={styles.libelle}>
            Tranches ({min} à {max})
          </Text>
          {tranches.map((t, i) => (
            <View key={i} style={styles.tranche}>
              <View style={styles.trancheEntete}>
                <Text style={styles.trancheTitre}>{i === 0 ? '1ère tranche' : `${i + 1}e tranche`}</Text>
                {tranches.length > min ? (
                  <IconButton icon="close" size={18} onPress={() => setTranches((l) => l.filter((_, j) => j !== i))} accessibilityLabel="Retirer cette tranche" />
                ) : null}
              </View>
              <TextInput
                mode="outlined"
                label="Montant"
                keyboardType="numeric"
                value={t.montant}
                onChangeText={(v) => setTranches((l) => l.map((x, j) => (j === i ? { ...x, montant: chiffres(v) } : x)))}
              />
              <DateField label="Date limite" value={t.date_limite || null} onChange={(v) => setTranches((l) => l.map((x, j) => (j === i ? { ...x, date_limite: v } : x)))} />
            </View>
          ))}
          {tranches.length < max ? (
            <Button icon="plus" onPress={() => setTranches((l) => [...l, { montant: '', date_limite: '' }])}>
              Ajouter une tranche
            </Button>
          ) : null}
          <Text style={styles.total}>Total : {formatMontant(totalTranches, user)}</Text>
          <Text style={styles.aide}>Les dates limites doivent se suivre ; la dernière devient la date de fin de la formule.</Text>
        </View>
      ) : (
        <>
          <DateField label="Date de fin" value={fin} onChange={setFin} />
          <TextInput mode="outlined" label="Montant" keyboardType="numeric" value={montant} onChangeText={(v) => setMontant(chiffres(v))} style={styles.champ} />
        </>
      )}

      {erreurs.map((e) => (
        <Text key={e} style={styles.erreur}>
          {e}
        </Text>
      ))}

      <SubmitButton label="Enregistrer la formule" onPress={enregistrer} loading={envoi} disabled={!valide} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  contenu: { padding: 16, paddingBottom: 48, gap: 8 },
  libelle: { fontWeight: '600', marginTop: 4 },
  puces: { flexDirection: 'row', flexWrap: 'wrap', gap: 6, marginBottom: 4 },
  puce: { marginBottom: 2 },
  bloc: { gap: 6 },
  tranche: { borderWidth: 1, borderColor: 'rgba(128,128,128,0.25)', borderRadius: 10, padding: 10, gap: 6 },
  trancheEntete: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  trancheTitre: { fontWeight: '600' },
  total: { fontWeight: '700', marginTop: 4 },
  aide: { fontSize: 12, opacity: 0.7 },
  champ: { marginTop: 4 },
  erreur: { color: '#b3261e' },
});
