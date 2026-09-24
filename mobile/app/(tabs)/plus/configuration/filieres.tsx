import { useState } from 'react';
import { FlatList, StyleSheet, View } from 'react-native';
import { Button, Dialog, FAB, Portal, Text, TextInput } from 'react-native-paper';

import requiredLabel from '@/components/RequiredLabel';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useLocale } from '@/context/LocaleContext';
import { api, apiErrorMessage } from '@/lib/api';
import { usePaginatedApi } from '@/lib/useApi';
import { Filiere } from '@/types/api';

export default function FilieresScreen() {
  const { t } = useLocale();
  const list = usePaginatedApi<Filiere>('/configuration/filieres');
  const [editing, setEditing] = useState<Filiere | null>(null);
  const [dialogVisible, setDialogVisible] = useState(false);
  const [nom, setNom] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);

  function openDialog(item?: Filiere) {
    setEditing(item ?? null);
    setNom(item?.nom_filiere ?? '');
    setError(null);
    setDialogVisible(true);
  }

  async function handleSubmit() {
    if (!nom.trim()) {
      setError(t('configuration.fil_nom_requis'));
      return;
    }
    setIsSubmitting(true);
    setError(null);
    try {
      const payload = { nom_filiere: nom.trim() };
      if (editing) {
        await api.put(`/configuration/filieres/${editing.id_filiere}`, payload);
      } else {
        await api.post('/configuration/filieres', payload);
      }
      setDialogVisible(false);
      setSuccessVisible(true);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err, t('configuration.fil_save_error')));
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleDelete(item: Filiere) {
    try {
      await api.delete(`/configuration/filieres/${item.id_filiere}`);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err));
    }
  }

  return (
    <View style={styles.container}>
      <FlatList
        data={list.items}
        keyExtractor={(item) => String(item.id_filiere)}
        contentContainerStyle={styles.content}
        refreshing={list.isRefreshing}
        onRefresh={list.refresh}
        onEndReached={list.loadMore}
        ListEmptyComponent={<Text style={styles.empty}>{list.error ?? t('configuration.fil_empty')}</Text>}
        renderItem={({ item }) => (
          <View style={styles.row}>
            <View style={styles.rowInfo}>
              <Text style={styles.title}>{item.nom_filiere}</Text>
            </View>
            <View style={styles.actions}>
              <Button compact onPress={() => openDialog(item)}>
                {t('configuration.modifier')}
              </Button>
              <Button compact textColor="#d33" onPress={() => handleDelete(item)}>
                {t('configuration.supprimer')}
              </Button>
            </View>
          </View>
        )}
      />
      <FAB icon="plus" style={styles.fab} onPress={() => openDialog()} />

      <Portal>
        <Dialog visible={dialogVisible} onDismiss={() => setDialogVisible(false)}>
          <Dialog.Title>{editing ? t('configuration.fil_modal_edit_title') : t('configuration.fil_modal_create_title')}</Dialog.Title>
          <Dialog.Content>
            <TextInput
              mode="outlined"
              label={requiredLabel(t('configuration.fil_nom_label'))}
              value={nom}
              onChangeText={setNom}
              placeholder={t('configuration.fil_nom_placeholder')}
              style={styles.input}
            />
            {error ? <Text style={styles.error}>{error}</Text> : null}
          </Dialog.Content>
          <Dialog.Actions>
            <Button onPress={() => setDialogVisible(false)}>{t('configuration.annuler')}</Button>
            <Button loading={isSubmitting} onPress={handleSubmit}>
              {t('configuration.enregistrer')}
            </Button>
          </Dialog.Actions>
        </Dialog>
      </Portal>

      <SuccessSnackbar
        visible={successVisible}
        message={editing ? t('configuration.fil_edit_success') : t('configuration.fil_create_success')}
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
  actions: { flexDirection: 'row' },
  fab: { position: 'absolute', right: 16, bottom: 16 },
  input: { marginBottom: 8 },
  error: { color: '#d33', marginTop: 4 },
});
