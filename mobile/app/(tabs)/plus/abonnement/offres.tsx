import { useState } from 'react';
import { FlatList, StyleSheet, View } from 'react-native';
import { Button, Dialog, FAB, Portal, Switch, Text, TextInput } from 'react-native-paper';

import requiredLabel from '@/components/RequiredLabel';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';

interface Offre {
  id: number;
  code: string;
  nom: string;
  description: string | null;
  montant: number;
  devise: string;
  duree_jours: number;
  actif: boolean;
}

export default function OffresAbonnementScreen() {
  const { data, isLoading, error, reload } = useApiGet<{ all_offres: Offre[] }>('/abonnements');
  const [editing, setEditing] = useState<Offre | null>(null);
  const [dialogVisible, setDialogVisible] = useState(false);
  const [code, setCode] = useState('');
  const [nom, setNom] = useState('');
  const [description, setDescription] = useState('');
  const [montant, setMontant] = useState('');
  const [devise, setDevise] = useState('XOF');
  const [dureeJours, setDureeJours] = useState('30');
  const [actif, setActif] = useState(true);
  const [formError, setFormError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);

  function openDialog(item?: Offre) {
    setEditing(item ?? null);
    setCode(item?.code ?? '');
    setNom(item?.nom ?? '');
    setDescription(item?.description ?? '');
    setMontant(item ? String(item.montant) : '');
    setDevise(item?.devise ?? 'XOF');
    setDureeJours(item ? String(item.duree_jours) : '30');
    setActif(item?.actif ?? true);
    setFormError(null);
    setDialogVisible(true);
  }

  async function handleSubmit() {
    if (!code.trim() || !nom.trim() || !montant || !devise.trim() || !dureeJours) {
      setFormError('Tous les champs obligatoires doivent être remplis.');
      return;
    }
    setIsSubmitting(true);
    setFormError(null);
    try {
      const payload = {
        code: code.trim(),
        nom: nom.trim(),
        description: description.trim() || null,
        montant: Number(montant),
        devise: devise.trim(),
        duree_jours: Number(dureeJours),
        actif,
      };
      if (editing) {
        await api.put(`/abonnements/offres/${editing.id}`, payload);
      } else {
        await api.post('/abonnements/offres', payload);
      }
      setDialogVisible(false);
      setSuccessVisible(true);
      reload();
    } catch (err) {
      setFormError(apiErrorMessage(err, 'Impossible d’enregistrer cette offre.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleToggle(offre: Offre) {
    try {
      await api.patch(`/abonnements/offres/${offre.id}/toggle`);
      reload();
    } catch (err) {
      setFormError(apiErrorMessage(err));
    }
  }

  if (isLoading) return <Text style={styles.empty}>Chargement…</Text>;
  if (error || !data) return <Text style={styles.error}>{error ?? 'Impossible de charger les offres.'}</Text>;

  return (
    <View style={styles.container}>
      <FlatList
        data={data.all_offres}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={styles.content}
        ListEmptyComponent={<Text style={styles.empty}>Aucune offre.</Text>}
        renderItem={({ item }) => (
          <View style={styles.row}>
            <View style={styles.rowInfo}>
              <Text style={styles.title}>
                {item.nom} {item.actif ? '' : '(inactive)'}
              </Text>
              <Text style={styles.meta}>
                {item.code} · {Number(item.montant).toLocaleString('fr-FR')} {item.devise} ·{' '}
                {item.duree_jours > 0 ? `${item.duree_jours} jours` : 'à vie'}
              </Text>
            </View>
            <View style={styles.actions}>
              <Button compact onPress={() => openDialog(item)}>
                Modifier
              </Button>
              <Button compact textColor={item.actif ? '#d33' : '#1f8a4c'} onPress={() => handleToggle(item)}>
                {item.actif ? 'Désactiver' : 'Activer'}
              </Button>
            </View>
          </View>
        )}
      />
      <FAB icon="plus" style={styles.fab} onPress={() => openDialog()} />

      <Portal>
        <Dialog visible={dialogVisible} onDismiss={() => setDialogVisible(false)}>
          <Dialog.Title>{editing ? 'Modifier l’offre' : 'Nouvelle offre'}</Dialog.Title>
          <Dialog.ScrollArea>
            <Dialog.Content>
              <TextInput mode="outlined" label={requiredLabel('Code')} value={code} onChangeText={setCode} autoCapitalize="none" style={styles.input} />
              <TextInput mode="outlined" label={requiredLabel('Nom')} value={nom} onChangeText={setNom} style={styles.input} />
              <TextInput mode="outlined" label="Description (optionnel)" value={description} onChangeText={setDescription} style={styles.input} />
              <TextInput mode="outlined" label={requiredLabel('Montant')} keyboardType="numeric" value={montant} onChangeText={setMontant} style={styles.input} />
              <TextInput mode="outlined" label={requiredLabel('Devise')} value={devise} onChangeText={setDevise} autoCapitalize="characters" style={styles.input} />
              <TextInput
                mode="outlined"
                label={requiredLabel('Durée (jours, 0 = à vie)')}
                keyboardType="numeric"
                value={dureeJours}
                onChangeText={setDureeJours}
                style={styles.input}
              />
              <View style={styles.switchRow}>
                <Text>Active</Text>
                <Switch value={actif} onValueChange={setActif} />
              </View>
              {formError ? <Text style={styles.error}>{formError}</Text> : null}
            </Dialog.Content>
          </Dialog.ScrollArea>
          <Dialog.Actions>
            <Button onPress={() => setDialogVisible(false)}>Annuler</Button>
            <Button loading={isSubmitting} onPress={handleSubmit}>
              Enregistrer
            </Button>
          </Dialog.Actions>
        </Dialog>
      </Portal>

      <SuccessSnackbar
        visible={successVisible}
        message={editing ? 'Offre modifiée avec succès.' : 'Offre créée avec succès.'}
        onDismiss={() => setSuccessVisible(false)}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  content: { padding: 16, flexGrow: 1 },
  empty: { textAlign: 'center', opacity: 0.6, marginTop: 32 },
  error: { color: '#d33', textAlign: 'center', marginTop: 32 },
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
  switchRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginTop: 4 },
});
