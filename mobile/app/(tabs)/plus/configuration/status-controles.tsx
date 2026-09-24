import { useState } from 'react';
import { FlatList, StyleSheet, View } from 'react-native';
import { Button, Dialog, FAB, Portal, Text, TextInput } from 'react-native-paper';

import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useLocale } from '@/context/LocaleContext';
import { api, apiErrorMessage } from '@/lib/api';
import { usePaginatedApi } from '@/lib/useApi';

interface StatusControle {
  id_controle: number;
  type_controle: string;
  alertControle: string;
  penalite_conduite: number;
}

export default function StatusControlesScreen() {
  const { t } = useLocale();
  const ALERT_OPTIONS = [
    { value: 'oui', label: t('configuration.oui') },
    { value: 'non', label: t('configuration.non') },
  ];
  const list = usePaginatedApi<StatusControle>('/configuration/status-controles');
  const [editing, setEditing] = useState<StatusControle | null>(null);
  const [dialogVisible, setDialogVisible] = useState(false);
  const [controle, setControle] = useState('');
  const [alert, setAlert] = useState<string | null>(null);
  const [penalite, setPenalite] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);

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
      setError(t('configuration.an_tous_champs_requis'));
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
      setSuccessVisible(true);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err, t('configuration.sc_save_error')));
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
        ListEmptyComponent={<Text style={styles.empty}>{list.error ?? t('configuration.sc_empty_mobile')}</Text>}
        renderItem={({ item }) => (
          <View style={styles.row}>
            <View style={styles.rowInfo}>
              <Text style={styles.title}>{item.type_controle}</Text>
              <Text style={styles.meta}>
                {t('configuration.sc_alerte_mobile_prefix')} {item.alertControle} · {t('configuration.sc_penalite_mobile_prefix')} {item.penalite_conduite}
              </Text>
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
          <Dialog.Title>{editing ? t('configuration.sc_modal_edit_title_mobile') : t('configuration.sc_modal_create_title')}</Dialog.Title>
          <Dialog.Content>
            <TextInput mode="outlined" label={requiredLabel(t('configuration.an_libelle_mobile'))} value={controle} onChangeText={setControle} style={styles.input} />
            <SelectField label={requiredLabel(t('configuration.sc_alerte_label'))} value={alert} options={ALERT_OPTIONS} onChange={(v) => setAlert(v as string)} />
            <TextInput
              mode="outlined"
              label={requiredLabel(t('configuration.sc_penalite_label_mobile'))}
              keyboardType="numeric"
              value={penalite}
              onChangeText={setPenalite}
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
        message={editing ? t('configuration.sc_edit_success') : t('configuration.sc_create_success')}
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
