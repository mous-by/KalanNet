import { useState } from 'react';
import * as ImagePicker from 'expo-image-picker';
import { FlatList, ScrollView, StyleSheet, View } from 'react-native';
import { Button, Dialog, FAB, Portal, Text, TextInput } from 'react-native-paper';

import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet, usePaginatedApi } from '@/lib/useApi';

interface Ecole {
  idEcole: number;
  nomEcole: string;
  typeEcole: string;
  statut: string;
  id_academie: number | null;
  id_cap: number | null;
  telephone: string | null;
  email: string | null;
  adresse: string | null;
  notification_sms: number;
  notification_email: number;
  nomComplexe: string | null;
  nomFondamental: string | null;
  nomLycee: string | null;
  nomProfessionnel: string | null;
}

interface Academie {
  id_academie: number;
  nom_academie: string;
}

interface Cap {
  id_cap: number;
  nom_cap: string;
  id_academie: number;
}

interface Offre {
  id: number;
  nom: string;
  montant: number;
  devise: string;
  duree_jours: number;
  actif: boolean;
}

const TYPE_OPTIONS = [
  'Complexe Scolaire',
  'Fondamentale I',
  'Fondamentale II',
  'Collège',
  'Secondaire Generale',
  'Secondaire Technique et Professionnel',
].map((t) => ({ value: t, label: t }));

const STATUT_OPTIONS = [
  { value: 'public', label: 'Public' },
  { value: 'prive', label: 'Privé' },
];

const OUI_NON_OPTIONS = [
  { value: '0', label: 'Non' },
  { value: '1', label: 'Oui' },
];

const CAP_REQUIRED_TYPES = ['Fondamentale I', 'Fondamentale II', 'Collège', 'Complexe Scolaire'];

export default function EcolesScreen() {
  const { user } = useAuth();
  const list = usePaginatedApi<Ecole>('/configuration/ecoles');
  const { data: academiesData } = useApiGet<{ data: Academie[] }>('/configuration/academies');
  const { data: capsData } = useApiGet<{ data: Cap[] }>('/configuration/caps');
  const { data: abonnementData } = useApiGet<{ all_offres: Offre[] }>('/abonnements');

  const [editing, setEditing] = useState<Ecole | null>(null);
  const [dialogVisible, setDialogVisible] = useState(false);
  const [nomEcole, setNomEcole] = useState('');
  const [typeEcole, setTypeEcole] = useState<string | null>(null);
  const [statut, setStatut] = useState('public');
  const [idAcademie, setIdAcademie] = useState<number | null>(null);
  const [idCap, setIdCap] = useState<number | null>(null);
  const [telephone, setTelephone] = useState('');
  const [email, setEmail] = useState('');
  const [notificationSms, setNotificationSms] = useState('0');
  const [notificationEmail, setNotificationEmail] = useState('1');
  const [abonnementOffreId, setAbonnementOffreId] = useState<number | null>(null);
  const [adresse, setAdresse] = useState('');
  const [nomComplexe, setNomComplexe] = useState('');
  const [nomFondamental, setNomFondamental] = useState('');
  const [nomLycee, setNomLycee] = useState('');
  const [nomProfessionnel, setNomProfessionnel] = useState('');
  const [logoUri, setLogoUri] = useState<string | null>(null);
  const [formError, setFormError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);

  function openDialog(item?: Ecole) {
    setEditing(item ?? null);
    setNomEcole(item?.nomEcole ?? '');
    setTypeEcole(item?.typeEcole ?? null);
    setStatut(item?.statut ?? 'public');
    setIdAcademie(item?.id_academie ?? null);
    setIdCap(item?.id_cap ?? null);
    setTelephone(item?.telephone ?? '');
    setEmail(item?.email ?? '');
    setNotificationSms(item ? String(item.notification_sms) : '0');
    setNotificationEmail(item ? String(item.notification_email) : '1');
    setAbonnementOffreId(null);
    setAdresse(item?.adresse ?? '');
    setNomComplexe(item?.nomComplexe ?? '');
    setNomFondamental(item?.nomFondamental ?? '');
    setNomLycee(item?.nomLycee ?? '');
    setNomProfessionnel(item?.nomProfessionnel ?? '');
    setLogoUri(null);
    setFormError(null);
    setDialogVisible(true);
  }

  async function pickLogo() {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) return;
    const result = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], quality: 0.8 });
    if (!result.canceled && result.assets[0]) setLogoUri(result.assets[0].uri);
  }

  const isComplexe = typeEcole === 'Complexe Scolaire';
  const needsCap = typeEcole ? CAP_REQUIRED_TYPES.includes(typeEcole) : false;

  async function handleSubmit() {
    if (!nomEcole.trim() || !typeEcole || !statut || !idAcademie || (needsCap && !idCap)) {
      setFormError('Veuillez remplir tous les champs obligatoires.');
      return;
    }
    setIsSubmitting(true);
    setFormError(null);
    try {
      const form = new FormData();
      form.append('nomEcole', nomEcole.trim());
      form.append('typeEcole', typeEcole);
      form.append('statut', statut);
      form.append('id_academie', String(idAcademie));
      if (idCap) form.append('id_cap', String(idCap));
      if (telephone.trim()) form.append('telephone', telephone.trim());
      if (email.trim()) form.append('email', email.trim());
      form.append('notification_sms', notificationSms);
      form.append('notification_email', notificationEmail);
      if (adresse.trim()) form.append('adresse', adresse.trim());
      if (isComplexe) {
        if (nomComplexe.trim()) form.append('nomComplexe', nomComplexe.trim());
        if (nomFondamental.trim()) form.append('nomFondamental', nomFondamental.trim());
        if (nomLycee.trim()) form.append('nomLycee', nomLycee.trim());
        if (nomProfessionnel.trim()) form.append('nomProfessionnel', nomProfessionnel.trim());
      }
      if (user?.droit === 'SupAdmin' && abonnementOffreId) {
        form.append('abonnement_offre_id', String(abonnementOffreId));
      }
      if (logoUri) form.append('logoEcole', { uri: logoUri, name: 'logo.jpg', type: 'image/jpeg' } as unknown as Blob);

      if (editing) {
        form.append('_method', 'PUT');
        await api.post(`/configuration/ecoles/${editing.idEcole}`, form, { headers: { 'Content-Type': 'multipart/form-data' } });
      } else {
        await api.post('/configuration/ecoles', form, { headers: { 'Content-Type': 'multipart/form-data' } });
      }
      setDialogVisible(false);
      setSuccessVisible(true);
      list.refresh();
    } catch (err) {
      setFormError(apiErrorMessage(err, 'Impossible d’enregistrer cette école.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleDelete(item: Ecole) {
    try {
      await api.delete(`/configuration/ecoles/${item.idEcole}`);
      list.refresh();
    } catch (err) {
      setFormError(apiErrorMessage(err));
    }
  }

  const academieOptions = (academiesData?.data ?? []).map((a) => ({ value: a.id_academie, label: a.nom_academie }));
  const capOptions = (capsData?.data ?? [])
    .filter((c) => !idAcademie || c.id_academie === idAcademie)
    .map((c) => ({ value: c.id_cap, label: c.nom_cap }));
  const offreOptions = (abonnementData?.all_offres ?? [])
    .filter((o) => o.actif)
    .map((o) => ({ value: o.id, label: `${o.nom} — ${Number(o.montant).toLocaleString('fr-FR')} ${o.devise}` }));

  if (list.isLoading) return <Text style={styles.empty}>Chargement…</Text>;

  return (
    <View style={styles.container}>
      <FlatList
        data={list.items}
        keyExtractor={(item) => String(item.idEcole)}
        contentContainerStyle={styles.content}
        refreshing={list.isRefreshing}
        onRefresh={list.refresh}
        onEndReached={list.loadMore}
        ListEmptyComponent={<Text style={styles.empty}>{list.error ?? 'Aucune école.'}</Text>}
        renderItem={({ item }) => (
          <View style={styles.row}>
            <View style={styles.rowInfo}>
              <Text style={styles.title}>{item.nomEcole}</Text>
              <Text style={styles.meta}>
                {item.typeEcole} · {item.statut === 'public' ? 'Public' : 'Privé'}
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
        <Dialog visible={dialogVisible} onDismiss={() => setDialogVisible(false)} style={styles.dialog}>
          <Dialog.Title>{editing ? 'Modifier l’école' : 'Nouvelle école'}</Dialog.Title>
          <Dialog.ScrollArea style={styles.dialogScroll}>
            <ScrollView contentContainerStyle={styles.dialogContent}>
              <TextInput mode="outlined" label={requiredLabel("Nom de l'école")} value={nomEcole} onChangeText={setNomEcole} style={styles.input} />
              <SelectField label={requiredLabel('Type')} value={typeEcole} options={TYPE_OPTIONS} onChange={(v) => setTypeEcole(v as string)} />
              <SelectField label={requiredLabel('Statut')} value={statut} options={STATUT_OPTIONS} onChange={(v) => setStatut(v as string)} />
              <SelectField label={requiredLabel('Académie')} value={idAcademie} options={academieOptions} onChange={(v) => setIdAcademie(v as number)} />
              {needsCap ? <SelectField label={requiredLabel('CAP')} value={idCap} options={capOptions} onChange={(v) => setIdCap(v as number)} /> : null}
              <TextInput mode="outlined" label="Téléphone (optionnel)" value={telephone} onChangeText={setTelephone} keyboardType="phone-pad" style={styles.input} />
              <TextInput mode="outlined" label="Email (optionnel)" value={email} onChangeText={setEmail} keyboardType="email-address" autoCapitalize="none" style={styles.input} />
              <SelectField label="Notification SMS" value={notificationSms} options={OUI_NON_OPTIONS} onChange={(v) => setNotificationSms(v as string)} />
              <SelectField label="Notification email parents" value={notificationEmail} options={OUI_NON_OPTIONS} onChange={(v) => setNotificationEmail(v as string)} />
              {user?.droit === 'SupAdmin' ? (
                <SelectField
                  label={editing ? "Changer/activer l'abonnement" : "Plan d'abonnement initial (optionnel)"}
                  value={abonnementOffreId}
                  options={offreOptions}
                  onChange={(v) => setAbonnementOffreId(v as number)}
                />
              ) : null}
              <TextInput mode="outlined" label="Adresse (optionnel)" value={adresse} onChangeText={setAdresse} multiline style={styles.input} />
              {isComplexe ? (
                <>
                  <TextInput mode="outlined" label="Nom du Complexe Scolaire" value={nomComplexe} onChangeText={setNomComplexe} style={styles.input} />
                  <TextInput mode="outlined" label="Nom école fondamentale du complexe (optionnel)" value={nomFondamental} onChangeText={setNomFondamental} style={styles.input} />
                  <TextInput mode="outlined" label="Nom Lycée (optionnel)" value={nomLycee} onChangeText={setNomLycee} style={styles.input} />
                  <TextInput mode="outlined" label="Nom Technique et Professionnelle (optionnel)" value={nomProfessionnel} onChangeText={setNomProfessionnel} style={styles.input} />
                </>
              ) : null}
              <Button mode="outlined" onPress={pickLogo} style={styles.input}>
                {logoUri ? 'Changer le logo' : "Choisir un logo (optionnel)"}
              </Button>
              {formError ? <Text style={styles.error}>{formError}</Text> : null}
            </ScrollView>
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
        message={editing ? 'École modifiée avec succès.' : 'École créée avec succès.'}
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
  dialog: { maxHeight: '85%' },
  dialogScroll: { paddingHorizontal: 0 },
  dialogContent: { paddingHorizontal: 24, paddingBottom: 8 },
  input: { marginBottom: 8 },
  error: { color: '#d33', marginTop: 4 },
});
