import { useState } from 'react';
import { FlatList, StyleSheet, View } from 'react-native';
import { Button, Dialog, FAB, Portal, Text, TextInput } from 'react-native-paper';

import SelectField from '@/components/SelectField';
import { api, apiErrorMessage } from '@/lib/api';
import { usePaginatedApi } from '@/lib/useApi';

interface StatusControle {
  id_controle: number;
  type_controle: string;
  alertControle: string;
  penalite_conduite: number;
}

const ALERT_OPTIONS = [
  { value: 'oui', label: 'Oui' },
  { value: 'non', label: 'Non' },
];

export default function StatusControlesScreen() {
  const list = usePaginatedApi<StatusControle>('/configuration/status-controles');
  const [editing, setEditing] = useState<StatusControle | null>(null);
  const [dialogVisible, setDialogVisible] = useState(false);
  const [controle, setControle] = useState('');
  const [alert, setAlert] = useState<string | null>(null);
  const [penalite, setPenalite] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  function openDialog(item?: StatusControle) {
    setEditing(item ?? null);
    setControle(item?.type_controle ?? '');
    setAlert(item?.alertControle ?? 'non');
    setPenalite(item?.penalite_conduite != null ? String(item.penalite_conduite) : '');
    setError(null);
    setDialogVisible(true);
  }

  async function handleSubmit() {
    if (!controle.trim() || !alert || !penalite) {
      setError('Tous les champs sont requis.');
      return;
    }
    setIsSubmitting(true);
    setError(null);
    try {
      const payload = { controle: controle.trim(), alert, penalite_conduite: Number(penalite) };
      if (editing) {
        await api.put(`/configuration/status-controles/${editing.id_controle}`, payload);
      } else {
        await api.post('/configuration/status-controles', payload);
      }
      setDialogVisible(false);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible d’enregistrer ce statut.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleDelete(item: StatusControle) {
    try {
      await api.delete(`/configuration/status-controles/${item.id_controle}`);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err));
    }
  }

  return (
    <View style={styles.container}>
      <FlatList
        data={list.items}
        keyExtractor={(item) => String(item.id_controle)}
        contentContainerStyle={styles.content}
        refreshing={list.isRefreshing}
        onRefresh={list.refresh}
        ListEmptyComponent={<Text style={styles.empty}>{list.error ?? 'Aucun statut.'}</Text>}
        renderItem={({ item }) => (
          <View style={styles.row}>
            <View style={styles.rowInfo}>
              <Text style={styles.title}>{item.type_controle}</Text>
              <Text style={styles.meta}>
                Alerte : {item.alertControle} · Pénalité conduite : {item.penalite_conduite}
              </Text>
            </View>
            <View style={styles.actions}>
              <Button compact onPress={() => openDialog(item)}>
                Modifier
              </Button>
              <Button compact textColor="#d33" onPress={() => handleDelete(item)}>
                Supprimer
              </Button>
            </View>
          </View>
        )}
      />
      <FAB icon="plus" style={styles.fab} onPress={() => openDialog()} />

      <Portal>
        <Dialog visible={dialogVisible} onDismiss={() => setDialogVisible(false)}>
          <Dialog.Title>{editing ? 'Modifier le statut' : 'Nouveau statut de contrôle'}</Dialog.Title>
          <Dialog.Content>
            <TextInput mode="outlined" label="Libellé" value={controle} onChangeText={setControle} style={styles.input} />
            <SelectField label="Alerte" value={alert} options={ALERT_OPTIONS} onChange={(v) => setAlert(v as string)} />
            <TextInput
              mode="outlined"
              label="Pénalité sur la note de conduite (0-18)"
              keyboardType="numeric"
              value={penalite}
              onChangeText={setPenalite}
              style={styles.input}
            />
            {error ? <Text style={styles.error}>{error}</Text> : null}
          </Dialog.Content>
          <Dialog.Actions>
            <Button onPress={() => setDialogVisible(false)}>Annuler</Button>
            <Button loading={isSubmitting} onPress={handleSubmit}>
              Enregistrer
            </Button>
          </Dialog.Actions>
        </Dialog>
      </Portal>
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
  rowInfo: { marginBottom: 6 },
  title: { fontSize: 16, fontWeight: '600' },
  meta: { opacity: 0.6, marginTop: 4 },
  actions: { flexDirection: 'row' },
  fab: { position: 'absolute', right: 16, bottom: 16 },
  input: { marginBottom: 8 },
  error: { color: '#d33', marginTop: 4 },
});
