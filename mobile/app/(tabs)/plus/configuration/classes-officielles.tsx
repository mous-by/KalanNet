import { useState } from 'react';
import { FlatList, StyleSheet, View } from 'react-native';
import { Button, Dialog, FAB, Portal, Text, TextInput } from 'react-native-paper';

import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useLocale } from '@/context/LocaleContext';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet, usePaginatedApi } from '@/lib/useApi';

interface ClasseOfficielle {
  id_classe_officielle: number;
  nom_classe_officielle: string;
  ordre_enseignement: string;
}

export default function ClassesOfficiellesScreen() {
  const { t } = useLocale();
  const list = usePaginatedApi<ClasseOfficielle>('/configuration/classes-officielles', {}, 'data');
  // "ordres" vient de l'API, deja scope au pays de l'ecole active (les cles
  // sont les memes 4 slugs partout, seuls les libelles s'adaptent -- voir
  // ConfigurationController::ordresClassesOfficielles() cote backend).
  const { data: ordresData } = useApiGet<{ ordres?: Record<string, string> }>('/configuration/classes-officielles');
  const ordreLabels = ordresData?.ordres ?? {};
  const ordreOptions = Object.entries(ordreLabels).map(([value, label]) => ({ value, label }));
  const [editing, setEditing] = useState<ClasseOfficielle | null>(null);
  const [dialogVisible, setDialogVisible] = useState(false);
  const [nom, setNom] = useState('');
  const [ordre, setOrdre] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);

  function openDialog(item?: ClasseOfficielle) {
    setEditing(item ?? null);
    setNom(item?.nom_classe_officielle ?? '');
    setOrdre(item?.ordre_enseignement ?? null);
    setError(null);
    setDialogVisible(true);
  }

  async function handleSubmit() {
    if (!nom.trim() || !ordre) {
      setError(t('configuration.an_tous_champs_requis'));
      return;
    }
    setIsSubmitting(true);
    setError(null);
    try {
      const payload = { nom_classe_officielle: nom.trim(), ordre_enseignement: ordre };
      if (editing) {
        await api.put(`/configuration/classes-officielles/${editing.id_classe_officielle}`, payload);
      } else {
        await api.post('/configuration/classes-officielles', payload);
      }
      setDialogVisible(false);
      setSuccessVisible(true);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err, t('configuration.classe_off_save_error')));
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleDelete(item: ClasseOfficielle) {
    try {
      await api.delete(`/configuration/classes-officielles/${item.id_classe_officielle}`);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err));
    }
  }

  return (
    <View style={styles.container}>
      <FlatList
        data={list.items}
        keyExtractor={(item) => String(item.id_classe_officielle)}
        contentContainerStyle={styles.content}
        refreshing={list.isRefreshing}
        onRefresh={list.refresh}
        ListEmptyComponent={<Text style={styles.empty}>{list.error ?? t('configuration.classe_off_empty')}</Text>}
        renderItem={({ item }) => (
          <View style={styles.row}>
            <View style={styles.rowInfo}>
              <Text style={styles.title}>{item.nom_classe_officielle}</Text>
              <Text style={styles.meta}>{ordreLabels[item.ordre_enseignement] ?? item.ordre_enseignement}</Text>
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
          <Dialog.Title>{editing ? t('configuration.classe_off_modal_edit_title') : t('configuration.classe_off_modal_create_title')}</Dialog.Title>
          <Dialog.Content>
            <TextInput mode="outlined" label={requiredLabel(t('configuration.perm_nom_label'))} value={nom} onChangeText={setNom} style={styles.input} />
            <SelectField label={requiredLabel(t('configuration.classe_off_ordre_label'))} value={ordre} options={ordreOptions} onChange={(v) => setOrdre(v as string)} />
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
        message={editing ? t('configuration.classe_off_edit_success') : t('configuration.classe_off_create_success')}
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
