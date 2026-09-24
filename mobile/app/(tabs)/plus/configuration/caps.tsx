import { useState } from 'react';
import { FlatList, StyleSheet, View } from 'react-native';
import { Button, Dialog, FAB, Portal, Text, TextInput } from 'react-native-paper';

import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useLocale } from '@/context/LocaleContext';
import { api, apiErrorMessage } from '@/lib/api';
import { useAllPaginated, usePaginatedApi } from '@/lib/useApi';

interface Academie {
  id_academie: number;
  nom_academie: string;
}

interface Cap {
  id_cap: number;
  nom_cap: string;
  code_cap: string;
  localite_cap: string;
  id_academie: number;
  academie?: Academie;
}

export default function CapsScreen() {
  const { t } = useLocale();
  const list = usePaginatedApi<Cap>('/configuration/caps');
  const academies = useAllPaginated<Academie>('/configuration/academies');
  const [editing, setEditing] = useState<Cap | null>(null);
  const [dialogVisible, setDialogVisible] = useState(false);
  const [nom, setNom] = useState('');
  const [code, setCode] = useState('');
  const [localite, setLocalite] = useState('');
  const [idAcademie, setIdAcademie] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);

  function openDialog(item?: Cap) {
    setEditing(item ?? null);
    setNom(item?.nom_cap ?? '');
    setCode(item?.code_cap ?? '');
    setLocalite(item?.localite_cap ?? '');
    setIdAcademie(item?.id_academie ?? null);
    setError(null);
    setDialogVisible(true);
  }

  async function handleSubmit() {
    if (!nom.trim() || !code.trim() || !localite.trim() || !idAcademie) {
      setError(t('configuration.an_tous_champs_requis'));
      return;
    }
    setIsSubmitting(true);
    setError(null);
    try {
      const payload = { nom_cap: nom.trim(), code_cap: code.trim(), localite_cap: localite.trim(), id_academie: idAcademie };
      if (editing) {
        await api.put(`/configuration/caps/${editing.id_cap}`, payload);
      } else {
        await api.post('/configuration/caps', payload);
      }
      setDialogVisible(false);
      setSuccessVisible(true);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err, t('configuration.cap_save_error')));
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleDelete(item: Cap) {
    try {
      await api.delete(`/configuration/caps/${item.id_cap}`);
      list.refresh();
    } catch (err) {
      setError(apiErrorMessage(err));
    }
  }

  const academieOptions = academies.items.map((a) => ({ value: a.id_academie, label: a.nom_academie }));

  return (
    <View style={styles.container}>
      <FlatList
        data={list.items}
        keyExtractor={(item) => String(item.id_cap)}
        contentContainerStyle={styles.content}
        refreshing={list.isRefreshing}
        onRefresh={list.refresh}
        ListEmptyComponent={<Text style={styles.empty}>{list.error ?? t('configuration.cap_empty')}</Text>}
        renderItem={({ item }) => (
          <View style={styles.row}>
            <View style={styles.rowInfo}>
              <Text style={styles.title}>{item.nom_cap}</Text>
              <Text style={styles.meta}>
                {item.code_cap} · {item.localite_cap} · {item.academie?.nom_academie ?? ''}
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
          <Dialog.Title>{editing ? t('configuration.cap_modal_edit_title') : t('configuration.cap_modal_create_title')}</Dialog.Title>
          <Dialog.Content>
            <TextInput mode="outlined" label={requiredLabel(t('configuration.perm_nom_label'))} value={nom} onChangeText={setNom} style={styles.input} />
            <TextInput mode="outlined" label={requiredLabel(t('configuration.aca_th_code'))} value={code} onChangeText={setCode} style={styles.input} />
            <TextInput mode="outlined" label={requiredLabel(t('configuration.aca_th_localite'))} value={localite} onChangeText={setLocalite} style={styles.input} />
            <SelectField label={requiredLabel(t('configuration.menu_academies'))} value={idAcademie} options={academieOptions} onChange={(v) => setIdAcademie(v as number)} />
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
        message={editing ? t('configuration.cap_edit_success') : t('configuration.cap_create_success')}
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
