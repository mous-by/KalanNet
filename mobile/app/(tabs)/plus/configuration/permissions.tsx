import { useState } from 'react';
import { FlatList, StyleSheet, View } from 'react-native';
import { Button, Dialog, FAB, Portal, Text, TextInput } from 'react-native-paper';

import requiredLabel from '@/components/RequiredLabel';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';

interface Permission {
  id: number;
  name: string;
  users_count: number;
}

export default function PermissionsScreen() {
  const { data, isLoading, error, reload } = useApiGet<{ data: Permission[] }>('/configuration/permissions');
  const [dialogVisible, setDialogVisible] = useState(false);
  const [name, setName] = useState('');
  const [formError, setFormError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);

  async function handleSubmit() {
    if (!name.trim()) {
      setFormError('Le nom est requis.');
      return;
    }
    setIsSubmitting(true);
    setFormError(null);
    try {
      await api.post('/configuration/permissions', { name: name.trim() });
      setDialogVisible(false);
      setName('');
      setSuccessVisible(true);
      reload();
    } catch (err) {
      setFormError(apiErrorMessage(err, 'Impossible de créer cette permission.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <View style={styles.container}>
      <FlatList
        data={data?.data ?? []}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={styles.content}
        refreshing={isLoading}
        onRefresh={reload}
        ListEmptyComponent={<Text style={styles.empty}>{error ?? 'Aucune permission.'}</Text>}
        renderItem={({ item }) => (
          <View style={styles.row}>
            <Text style={styles.title}>{item.name}</Text>
            <Text style={styles.meta}>{item.users_count} utilisateur(s)</Text>
          </View>
        )}
      />
      <FAB icon="plus" style={styles.fab} onPress={() => setDialogVisible(true)} />

      <Portal>
        <Dialog visible={dialogVisible} onDismiss={() => setDialogVisible(false)}>
          <Dialog.Title>Nouvelle permission</Dialog.Title>
          <Dialog.Content>
            <TextInput mode="outlined" label={requiredLabel('Nom')} value={name} onChangeText={setName} style={styles.input} />
            {formError ? <Text style={styles.error}>{formError}</Text> : null}
          </Dialog.Content>
          <Dialog.Actions>
            <Button onPress={() => setDialogVisible(false)}>Annuler</Button>
            <Button loading={isSubmitting} onPress={handleSubmit}>
              Créer
            </Button>
          </Dialog.Actions>
        </Dialog>
      </Portal>

      <SuccessSnackbar visible={successVisible} message="Permission créée avec succès." onDismiss={() => setSuccessVisible(false)} />
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
