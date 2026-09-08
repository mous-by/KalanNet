import { useState } from 'react';
import { FlatList, KeyboardAvoidingView, Platform, StyleSheet, View } from 'react-native';
import { Button, Dialog, FAB, Portal, Text, TextInput } from 'react-native-paper';

import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { api, apiErrorMessage } from '@/lib/api';
import { useAllPaginated, usePaginatedApi } from '@/lib/useApi';

interface TypeNote {
  id_note: number;
  typeNote: string;
  codeNote: string;
  valeur: number;
}

const TYPE_OPTIONS = [
  { value: 'devoir', label: 'Devoir' },
  { value: 'composition', label: 'Composition' },
  { value: 'NT10', label: 'NT10' },
];

const TYPE_LABELS: Record<string, string> = { devoir: 'Devoir', composition: 'Comp', NT10: 'NT10' };

export default function TypesNotesScreen() {
  const list = usePaginatedApi<TypeNote>('/configuration/types-notes');
  const { items: allTypesNotes } = useAllPaginated<TypeNote>('/configuration/types-notes');
  const [editing, setEditing] = useState<TypeNote | null>(null);
  const [dialogVisible, setDialogVisible] = useState(false);
  const [typeNote, setTypeNote] = useState<string | null>(null);
  const [codeNote, setCodeNote] = useState('');
  const [valeur, setValeur] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);

  // Mirrors the web "Ajouter un type" modal: picking a type suggests the
  // next free code for it (Devoir 1, Comp 1…) — still editable afterward.
  function suggestedCode(type: string): string {
    if (type === 'NT10') return 'NT10';
    const count = allTypesNotes.filter((n) => n.typeNote === type).length;
    return `${TYPE_LABELS[type] ?? type} ${count + 1}`;
  }

  function openDialog(item?: TypeNote) {
    setEditing(item ?? null);
    setTypeNote(item?.typeNote ?? null);
    setCodeNote(item?.codeNote ?? '');
    setValeur(item?.valeur != null ? String(item.valeur) : '');
    setError(null);
    setDialogVisible(true);
  }

  function handleTypeChange(value: string) {
    setTypeNote(value);
    if (!editing) setCodeNote(suggestedCode(value));
  }

  async function handleSubmit() {
    if (!typeNote || !codeNote.trim() || !valeur) {
      setError('Tous les champs sont requis.');
      return;
    }
    setIsSubmitting(true);
    setError(null);
    try {
      const payload = { typeNote, codeNote: codeNote.trim(), valeur: Number(valeur) };
      if (editing) {
        await api.put(`/configuration/types-notes/${editing.id_note}`, payload);
      } else {
        await api.post('/configuration/types-notes', payload);
      }
      setDialogVisible(false);
      setSuccessVisible(true);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible d’enregistrer ce type de note.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleDelete(item: TypeNote) {
    try {
      await api.delete(`/configuration/types-notes/${item.id_note}`);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err));
    }
  }

  return (
    <View style={styles.container}>
      <FlatList
        data={list.items}
        keyExtractor={(item) => String(item.id_note)}
        contentContainerStyle={styles.content}
        refreshing={list.isRefreshing}
        onRefresh={list.refresh}
        ListEmptyComponent={<Text style={styles.empty}>{list.error ?? 'Aucun type de note.'}</Text>}
        renderItem={({ item }) => (
          <View style={styles.row}>
            <View style={styles.rowInfo}>
              <Text style={styles.title}>
                {item.typeNote} — {item.codeNote}
              </Text>
              <Text style={styles.meta}>Note maximale : {item.valeur}</Text>
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
          <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : 'height'}>
            <Dialog.Title>{editing ? 'Modifier le type de note' : 'Nouveau type de note'}</Dialog.Title>
            <Dialog.Content>
              <SelectField label={requiredLabel('Type')} value={typeNote} options={TYPE_OPTIONS} onChange={(v) => handleTypeChange(v as string)} />
              <TextInput mode="outlined" label={requiredLabel('Code')} value={codeNote} onChangeText={setCodeNote} style={styles.input} />
              <TextInput mode="outlined" label={requiredLabel('Note maximale')} keyboardType="numeric" value={valeur} onChangeText={setValeur} style={styles.input} />
              {error ? <Text style={styles.error}>{error}</Text> : null}
            </Dialog.Content>
            <Dialog.Actions>
              <Button onPress={() => setDialogVisible(false)}>Annuler</Button>
              <Button loading={isSubmitting} onPress={handleSubmit}>
                Enregistrer
              </Button>
            </Dialog.Actions>
          </KeyboardAvoidingView>
        </Dialog>
      </Portal>

      <SuccessSnackbar
        visible={successVisible}
        message={editing ? 'Type de note modifié avec succès.' : 'Type de note créé avec succès.'}
        onDismiss={() => setSuccessVisible(false)}
      />
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
