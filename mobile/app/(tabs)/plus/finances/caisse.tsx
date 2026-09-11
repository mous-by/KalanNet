import { useState } from 'react';
import { FlatList, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Dialog, FAB, Portal, Text, TextInput } from 'react-native-paper';

import DateField from '@/components/DateField';
import OfflineBanner from '@/components/OfflineBanner';
import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useAuth } from '@/context/AuthContext';
import { useOffline } from '@/context/OfflineContext';
import { api, apiErrorMessage } from '@/lib/api';
import { removeQueueItem } from '@/lib/offlineQueue';
import { hasPermission } from '@/lib/permissions';
import { useApiGet } from '@/lib/useApi';
import { AnneeScolaire } from '@/types/api';

interface Mouvement {
  type: 'RECETTE' | 'DEPENSE';
  date: string;
  montant: number;
  motif: string;
}

interface Caisse {
  id_caisse: number;
  montant_net: number;
}

interface CaisseData {
  caisse: Caisse | null;
  mouvements: Mouvement[];
  annees: AnneeScolaire[];
}

type DialogKind = 'encaissement' | 'decaissement' | null;

export default function CaisseScreen() {
  const { user } = useAuth();
  const { isOnline, enqueueAction, queue } = useOffline();
  const { data, isLoading, error, reload } = useApiGet<CaisseData>('/finances/caisse', [], { cacheKey: 'finances-caisse' });
  const queuedMouvements = queue.filter((item) => item.kind === 'caisse');
  const [fabOpen, setFabOpen] = useState(false);
  const [dialogKind, setDialogKind] = useState<DialogKind>(null);
  const [motif, setMotif] = useState('');
  const [montant, setMontant] = useState('');
  const [date, setDate] = useState<string | null>(null);
  const [idAnnee, setIdAnnee] = useState<number | null>(null);
  const [formError, setFormError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  const canEncaisser = hasPermission(user, 'encaissement_creation');
  const canDecaisser = hasPermission(user, 'decaissements_creation');
  const canManage = canEncaisser || canDecaisser;

  function openDialog(kind: DialogKind) {
    setFabOpen(false);
    setMotif('');
    setMontant('');
    setDate(null);
    setIdAnnee(null);
    setFormError(null);
    setDialogKind(kind);
  }

  async function handleSubmit() {
    if (!data?.caisse || !motif.trim() || !montant || !date || !idAnnee) {
      setFormError('Tous les champs sont requis.');
      return;
    }
    setIsSubmitting(true);
    setFormError(null);
    const payload = {
      id_caisse: data.caisse.id_caisse,
      id_annee_scolaire: idAnnee,
      date_encaissement: date,
      date_decaissement: date,
      motif_encaissement: motif.trim(),
      motif_decaissement: motif.trim(),
      montant_encaissement: Number(montant),
      montant_decaissement: Number(montant),
      type_operation: 'Manuel',
    };
    const endpoint = dialogKind === 'encaissement' ? '/finances/encaissements' : '/finances/decaissements';
    try {
      if (!isOnline) {
        await enqueueAction({
          kind: 'caisse',
          label: `${dialogKind === 'encaissement' ? '+' : '-'}${Number(montant).toLocaleString('fr-FR')} · ${motif.trim()}`,
          endpoint,
          method: 'post',
          payload,
        });
        setSuccessMessage('Mouvement mis en attente, sera synchronisé au retour du réseau.');
        setDialogKind(null);
        setSuccessVisible(true);
        return;
      }
      await api.post(endpoint, payload);
      setSuccessMessage(dialogKind === 'decaissement' ? 'Décaissement enregistré avec succès.' : 'Encaissement enregistré avec succès.');
      setDialogKind(null);
      setSuccessVisible(true);
      reload();
    } catch (err) {
      setFormError(apiErrorMessage(err, 'Action impossible.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  if (isLoading) return <ActivityIndicator style={styles.spinner} size="large" />;
  if (error) return <Text style={styles.error}>{error}</Text>;

  return (
    <View style={styles.container}>
      <OfflineBanner />
      <View style={styles.balanceCard}>
        <Text style={styles.balanceLabel}>Solde</Text>
        <Text style={styles.balanceValue}>{Number(data?.caisse?.montant_net ?? 0).toLocaleString('fr-FR')} FCFA</Text>
      </View>

      {queuedMouvements.length > 0 ? (
        <View style={styles.queuedSection}>
          {queuedMouvements.map((item) => (
            <View key={item.id} style={[styles.movementRow, item.status === 'conflict' ? styles.conflictRow : styles.queuedRow]}>
              <View style={styles.movementInfo}>
                <Text style={styles.movementMotif}>{item.label}</Text>
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

      <FlatList
        data={data?.mouvements ?? []}
        keyExtractor={(item, index) => `${item.type}-${index}`}
        contentContainerStyle={styles.list}
        ListEmptyComponent={<Text style={styles.empty}>Aucun mouvement.</Text>}
        renderItem={({ item }) => (
          <View style={styles.movementRow}>
            <View style={styles.movementInfo}>
              <Text style={styles.movementMotif}>{item.motif}</Text>
              <Text style={styles.meta}>{item.date}</Text>
            </View>
            <Text style={item.type === 'RECETTE' ? styles.amountPositive : styles.amountNegative}>
              {item.type === 'RECETTE' ? '+' : '-'}
              {Number(item.montant).toLocaleString('fr-FR')}
            </Text>
          </View>
        )}
      />

      {canManage ? (
        <FAB.Group
          open={fabOpen}
          visible
          icon={fabOpen ? 'close' : 'plus'}
          onStateChange={({ open }) => setFabOpen(open)}
          actions={[
            ...(canEncaisser ? [{ icon: 'cash-plus', label: 'Encaissement', onPress: () => openDialog('encaissement') }] : []),
            ...(canDecaisser ? [{ icon: 'cash-minus', label: 'Décaissement', onPress: () => openDialog('decaissement') }] : []),
          ]}
        />
      ) : null}

      <Portal>
        <Dialog visible={dialogKind !== null} onDismiss={() => setDialogKind(null)}>
          <Dialog.Title>{dialogKind === 'encaissement' ? 'Nouvel encaissement' : 'Nouveau décaissement'}</Dialog.Title>
          <Dialog.Content>
            <SelectField
              label={requiredLabel('Année scolaire')}
              value={idAnnee}
              options={(data?.annees ?? []).map((a) => ({ value: a.id_anneeScolaire, label: a.annee }))}
              onChange={(v) => setIdAnnee(v as number)}
            />
            <TextInput mode="outlined" label={requiredLabel('Motif')} value={motif} onChangeText={setMotif} style={styles.input} />
            <TextInput mode="outlined" label={requiredLabel('Montant')} keyboardType="numeric" value={montant} onChangeText={setMontant} style={styles.input} />
            <DateField label={requiredLabel('Date')} value={date} onChange={setDate} />
            {formError ? <Text style={styles.error}>{formError}</Text> : null}
          </Dialog.Content>
          <Dialog.Actions>
            <Button onPress={() => setDialogKind(null)}>Annuler</Button>
            <Button loading={isSubmitting} onPress={handleSubmit}>
              Enregistrer
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
  spinner: { marginTop: 40 },
  error: { color: '#d33', textAlign: 'center', marginTop: 12 },
  balanceCard: { padding: 20, alignItems: 'center' },
  balanceLabel: { opacity: 0.6 },
  balanceValue: { fontSize: 28, fontWeight: 'bold', marginTop: 4 },
  list: { padding: 16, paddingTop: 0, flexGrow: 1 },
  empty: { textAlign: 'center', opacity: 0.6, marginTop: 24 },
  movementRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 12,
    padding: 12,
    marginBottom: 8,
  },
  movementInfo: { flex: 1 },
  movementMotif: { fontWeight: '500' },
  meta: { opacity: 0.6, marginTop: 2 },
  amountPositive: { color: '#1f8a4c', fontWeight: '700' },
  amountNegative: { color: '#d33', fontWeight: '700' },
  input: { marginBottom: 8 },
  queuedSection: { paddingHorizontal: 16, marginBottom: 4 },
  queuedRow: { borderColor: '#b8860b', backgroundColor: 'rgba(184,134,11,0.08)' },
  conflictRow: { borderColor: '#d33', backgroundColor: 'rgba(211,51,51,0.06)' },
  queuedText: { color: '#b8860b', marginTop: 6, fontSize: 12 },
  conflictText: { color: '#d33', marginTop: 6, fontSize: 12 },
});
