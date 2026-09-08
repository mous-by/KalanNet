import { useState } from 'react';
import { FlatList, StyleSheet, View } from 'react-native';
import { Button, Dialog, FAB, Portal, Text, TextInput } from 'react-native-paper';

import DateField from '@/components/DateField';
import requiredLabel from '@/components/RequiredLabel';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { api, apiErrorMessage } from '@/lib/api';
import { usePaginatedApi } from '@/lib/useApi';

interface Annee {
  id_anneeScolaire: number;
  annee: string;
  date_debut: string;
  date_fin: string;
}

export default function AnneesScreen() {
  const list = usePaginatedApi<Annee>('/configuration/annees', {}, 'annees');
  const [dialogVisible, setDialogVisible] = useState(false);
  const [annee, setAnnee] = useState('');
  const [dateDebut, setDateDebut] = useState<string | null>(null);
  const [dateFin, setDateFin] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);

  function openDialog() {
    setAnnee('');
    setDateDebut(null);
    setDateFin(null);
    setError(null);
    setDialogVisible(true);
  }

  async function handleSubmit() {
    if (!annee.trim() || !dateDebut || !dateFin) {
      setError('Tous les champs sont requis.');
      return;
    }
    setIsSubmitting(true);
    setError(null);
    try {
      await api.post('/configuration/annees', { annee: annee.trim(), date_debut: dateDebut, date_fin: dateFin });
      setDialogVisible(false);
      setSuccessVisible(true);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible de créer cette année scolaire.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <View style={styles.container}>
      <FlatList
        data={list.items}
        keyExtractor={(item) => String(item.id_anneeScolaire)}
        contentContainerStyle={styles.content}
        refreshing={list.isRefreshing}
        onRefresh={list.refresh}
        ListEmptyComponent={<Text style={styles.empty}>{list.error ?? 'Aucune année scolaire.'}</Text>}
        renderItem={({ item }) => (
          <View style={styles.row}>
            <Text style={styles.title}>{item.annee}</Text>
            <Text style={styles.meta}>
              {item.date_debut} → {item.date_fin}
            </Text>
          </View>
        )}
      />
      <FAB icon="plus" style={styles.fab} onPress={openDialog} />

      <Portal>
        <Dialog visible={dialogVisible} onDismiss={() => setDialogVisible(false)}>
          <Dialog.Title>Nouvelle année scolaire</Dialog.Title>
          <Dialog.Content>
            <TextInput mode="outlined" label={requiredLabel('Libellé (ex: 2026-2027)')} value={annee} onChangeText={setAnnee} style={styles.input} />
            <DateField label={requiredLabel('Date de début')} value={dateDebut} onChange={setDateDebut} />
            <DateField label={requiredLabel('Date de fin')} value={dateFin} onChange={setDateFin} />
            {error ? <Text style={styles.error}>{error}</Text> : null}
          </Dialog.Content>
          <Dialog.Actions>
            <Button onPress={() => setDialogVisible(false)}>Annuler</Button>
            <Button loading={isSubmitting} onPress={handleSubmit}>
              Créer
            </Button>
          </Dialog.Actions>
        </Dialog>
      </Portal>

      <SuccessSnackbar visible={successVisible} message="Année scolaire créée avec succès." onDismiss={() => setSuccessVisible(false)} />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { padding: 16, flexGrow: 1 },
  empty: { textAlign: 'center', opacity: 0.6, marginTop: 32 },
  row: {
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
  },
  title: { fontSize: 16, fontWeight: '600' },
  meta: { opacity: 0.6, marginTop: 4 },
  fab: { position: 'absolute', right: 16, bottom: 16 },
  input: { marginBottom: 8 },
  error: { color: '#d33', marginTop: 4 },
});
