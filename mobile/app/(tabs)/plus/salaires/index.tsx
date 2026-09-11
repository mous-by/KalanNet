import { useMemo, useState } from 'react';
import { FlatList, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Dialog, Portal, Text, TextInput } from 'react-native-paper';

import DateField from '@/components/DateField';
import OfflineBanner from '@/components/OfflineBanner';
import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useOffline } from '@/context/OfflineContext';
import { api, apiErrorMessage } from '@/lib/api';
import { removeQueueItem } from '@/lib/offlineQueue';
import { useApiGet } from '@/lib/useApi';
import { Enseignant } from '@/types/api';

interface SalaryRow {
  enseignant: Enseignant;
  contract: string;
  source: string;
  amount_due: number;
  paid: number;
  remaining: number;
  status: string;
}

interface SalaryData {
  salaryRows: SalaryRow[];
  summary: { due: number; paid: number; remaining: number; teachers: number };
  filters: { mois: string; annee: string; source: string };
  months: Record<string, string>;
  sources: Record<string, string>;
}

export default function SalairesScreen() {
  const { isOnline, enqueueAction, queue } = useOffline();
  const queuedSalaires = queue.filter((item) => item.kind === 'salaire');
  const [mois, setMois] = useState<string | null>(null);
  const [annee, setAnnee] = useState('');
  const [source, setSource] = useState<string | null>(null);
  const [payingFor, setPayingFor] = useState<SalaryRow | null>(null);
  const [montant, setMontant] = useState('');
  const [date, setDate] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);
  const [successMessage, setSuccessMessage] = useState('Paiement enregistré avec succès.');

  const endpoint = useMemo(() => {
    const params = new URLSearchParams();
    if (mois) params.set('mois', mois);
    if (annee) params.set('annee', annee);
    if (source) params.set('source', source);
    const query = params.toString();
    return query ? `/salaires?${query}` : '/salaires';
  }, [mois, annee, source]);

  const { data, isLoading, error, reload } = useApiGet<SalaryData>(endpoint, [endpoint], { cacheKey: endpoint });

  function openPayDialog(row: SalaryRow) {
    setPayingFor(row);
    setMontant(String(row.remaining));
    setDate(null);
    setFormError(null);
  }

  async function handlePay() {
    if (!payingFor || !data || !montant || !date) {
      setFormError('Tous les champs sont requis.');
      return;
    }
    setIsSubmitting(true);
    setFormError(null);
    const payload = {
      id_enseignant: payingFor.enseignant.id_enseignant,
      mois: data.filters.mois,
      annee: Number(data.filters.annee),
      source: data.filters.source,
      montant_verse: Number(montant),
      date_paiement: date,
    };
    try {
      if (!isOnline) {
        await enqueueAction({
          kind: 'salaire',
          label: `${payingFor.enseignant.nom_prenom_enseignant} · ${Number(montant).toLocaleString('fr-FR')} FCFA`,
          endpoint: '/salaires/payer',
          method: 'post',
          payload,
        });
        setSuccessMessage('Paiement mis en attente, sera synchronisé au retour du réseau.');
        setPayingFor(null);
        setSuccessVisible(true);
        return;
      }
      await api.post('/salaires/payer', payload);
      setSuccessMessage('Paiement enregistré avec succès.');
      setPayingFor(null);
      setSuccessVisible(true);
      reload();
    } catch (err) {
      setFormError(apiErrorMessage(err, 'Impossible d’enregistrer ce paiement.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  const monthOptions = Object.entries(data?.months ?? {}).map(([value, label]) => ({ value, label }));
  const sourceOptions = Object.entries(data?.sources ?? {}).map(([value, label]) => ({ value, label }));

  return (
    <View style={styles.container}>
      <OfflineBanner />
      {queuedSalaires.length > 0 ? (
        <View style={styles.queuedSection}>
          {queuedSalaires.map((item) => (
            <View key={item.id} style={[styles.row, item.status === 'conflict' ? styles.conflictRow : styles.queuedRow]}>
              <View style={styles.rowInfo}>
                <Text style={styles.name}>{item.label}</Text>
                <Text style={item.status === 'conflict' ? styles.conflictText : styles.queuedText}>
                  {item.status === 'conflict' ? (item.message ?? 'Conflit à vérifier') : 'En attente de synchronisation'}
                </Text>
              </View>
              {item.status === 'conflict' ? (
                <Button compact textColor="#d33" onPress={() => removeQueueItem(item.id)}>
                  Abandonner
                </Button>
              ) : null}
            </View>
          ))}
        </View>
      ) : null}
      <View style={styles.filters}>
        <SelectField label="Mois" value={mois ?? data?.filters.mois ?? null} options={monthOptions} onChange={(v) => setMois(v as string)} />
        <TextInput
          mode="outlined"
          label="Année"
          keyboardType="numeric"
          value={annee || data?.filters.annee || ''}
          onChangeText={setAnnee}
          style={styles.input}
        />
        <SelectField label="Source" value={source ?? data?.filters.source ?? null} options={sourceOptions} onChange={(v) => setSource(v as string)} />
      </View>

      {data?.summary ? (
        <View style={styles.summaryRow}>
          <Text style={styles.summaryItem}>Dû: {Number(data.summary.due).toLocaleString('fr-FR')}</Text>
          <Text style={styles.summaryItem}>Payé: {Number(data.summary.paid).toLocaleString('fr-FR')}</Text>
          <Text style={styles.summaryItem}>Reste: {Number(data.summary.remaining).toLocaleString('fr-FR')}</Text>
        </View>
      ) : null}

      {isLoading ? (
        <ActivityIndicator style={styles.spinner} size="large" />
      ) : error ? (
        <Text style={styles.error}>{error}</Text>
      ) : (
        <FlatList
          data={data?.salaryRows ?? []}
          keyExtractor={(item) => String(item.enseignant.id_enseignant)}
          contentContainerStyle={styles.list}
          refreshing={false}
          onRefresh={reload}
          ListEmptyComponent={<Text style={styles.empty}>Aucun enseignant.</Text>}
          renderItem={({ item }) => (
            <View style={styles.row}>
              <View style={styles.rowInfo}>
                <Text style={styles.name}>{item.enseignant.nom_prenom_enseignant}</Text>
                <Text style={styles.meta}>
                  {item.contract} · dû {Number(item.amount_due).toLocaleString('fr-FR')} · reste {Number(item.remaining).toLocaleString('fr-FR')}
                </Text>
                <Text style={styles.status}>{item.status}</Text>
              </View>
              {item.remaining > 0 ? (
                <Button mode="outlined" compact onPress={() => openPayDialog(item)}>
                  Payer
                </Button>
              ) : null}
            </View>
          )}
        />
      )}

      <Portal>
        <Dialog visible={payingFor !== null} onDismiss={() => setPayingFor(null)}>
          <Dialog.Title>Payer {payingFor?.enseignant.nom_prenom_enseignant}</Dialog.Title>
          <Dialog.Content>
            <TextInput mode="outlined" label={requiredLabel('Montant versé')} keyboardType="numeric" value={montant} onChangeText={setMontant} style={styles.dialogInput} />
            <DateField label={requiredLabel('Date de paiement')} value={date} onChange={setDate} />
            {formError ? <Text style={styles.error}>{formError}</Text> : null}
          </Dialog.Content>
          <Dialog.Actions>
            <Button onPress={() => setPayingFor(null)}>Annuler</Button>
            <Button loading={isSubmitting} onPress={handlePay}>
              Confirmer
            </Button>
          </Dialog.Actions>
        </Dialog>
      </Portal>

      <SuccessSnackbar visible={successVisible} message={successMessage} onDismiss={() => setSuccessVisible(false)} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  filters: { padding: 16, paddingBottom: 0 },
  input: { marginBottom: 12 },
  dialogInput: { marginBottom: 8 },
  summaryRow: { flexDirection: 'row', justifyContent: 'space-around', paddingBottom: 12 },
  summaryItem: { fontWeight: '600' },
  spinner: { marginTop: 40 },
  error: { color: '#d33', textAlign: 'center', marginTop: 12 },
  list: { padding: 16, paddingTop: 0, flexGrow: 1 },
  empty: { textAlign: 'center', opacity: 0.6, marginTop: 24 },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 12,
    padding: 12,
    marginBottom: 10,
  },
  rowInfo: { flex: 1 },
  name: { fontWeight: '600' },
  meta: { opacity: 0.6, marginTop: 2 },
  status: { marginTop: 4, fontSize: 12, opacity: 0.8 },
  queuedSection: { paddingHorizontal: 16, paddingTop: 16 },
  queuedRow: { borderColor: '#b8860b', backgroundColor: 'rgba(184,134,11,0.08)' },
  conflictRow: { borderColor: '#d33', backgroundColor: 'rgba(211,51,51,0.06)' },
  queuedText: { color: '#b8860b', marginTop: 6, fontSize: 12 },
  conflictText: { color: '#d33', marginTop: 6, fontSize: 12 },
});
