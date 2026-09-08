import { useState } from 'react';
import { FlatList, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Dialog, FAB, Menu, Portal, Text, TextInput } from 'react-native-paper';

import DateField from '@/components/DateField';
import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
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
  const { data, isLoading, error, reload } = useApiGet<CaisseData>('/finances/caisse');
  const [menuVisible, setMenuVisible] = useState(false);
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
    setMenuVisible(false);
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
    try {
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
      if (dialogKind === 'encaissement') {
        await api.post('/finances/encaissements', payload);
      } else {
        await api.post('/finances/decaissements', payload);
      }
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
      <View style={styles.balanceCard}>
        <Text style={styles.balanceLabel}>Solde</Text>
        <Text style={styles.balanceValue}>{Number(data?.caisse?.montant_net ?? 0).toLocaleString('fr-FR')} FCFA</Text>
      </View>

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
        <Menu
          visible={menuVisible}
          onDismiss={() => setMenuVisible(false)}
          anchor={<FAB icon="plus" style={styles.fab} onPress={() => setMenuVisible(true)} />}>
          {canEncaisser ? <Menu.Item title="Encaissement" onPress={() => openDialog('encaissement')} /> : null}
          {canDecaisser ? <Menu.Item title="Décaissement" onPress={() => openDialog('decaissement')} /> : null}
        </Menu>
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
  fab: { position: 'absolute', right: 16, bottom: 16 },
  input: { marginBottom: 8 },
});
