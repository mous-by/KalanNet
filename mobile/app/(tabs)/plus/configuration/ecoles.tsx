import { useState } from 'react';
import * as ImagePicker from 'expo-image-picker';
import { FlatList, ScrollView, StyleSheet, View } from 'react-native';
import { Button, Dialog, FAB, Portal, Text, TextInput } from 'react-native-paper';

import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useAuth } from '@/context/AuthContext';
import { useLocale } from '@/context/LocaleContext';
import { api, apiErrorMessage } from '@/lib/api';
import { useAllPaginated, useApiGet, usePaginatedApi } from '@/lib/useApi';

interface Ecole {
  idEcole: number;
  nomEcole: string;
  typeEcole: string;
  statut: string;
  id_pays: number | null;
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
  id_pays: number | null;
}

interface Cap {
  id_cap: number;
  nom_cap: string;
  id_academie: number;
  id_pays: number | null;
}

interface Pays {
  id: number;
  nom: string;
  code_iso: string;
}

interface Offre {
  id: number;
  nom: string;
  montant: number;
  devise: string;
  duree_jours: number;
  actif: boolean;
}

// Le Mali decoupe le fondamental/secondaire en 6 types distincts ; les autres
// pays utilisent un decoupage plus simple (Primaire + Secondaire Generale
// couvrant a elle seule tout le secondaire) -- voir ClasseController::
// ordresDisponibles() cote backend pour la meme logique.
const MALI_TYPE_OPTIONS = [
  'Complexe Scolaire',
  'Fondamentale I',
  'Fondamentale II',
  'Collège',
  'Secondaire Generale',
  'Secondaire Technique et Professionnel',
];
const NON_MALI_TYPE_OPTIONS = ['Complexe Scolaire', 'Primaire', 'Secondaire Generale', 'Secondaire Technique et Professionnel'];
// École de Santé est independante du pays (filiere/annee, pas de cycle
// Fondamentale/Secondaire) -- proposee quel que soit le pays choisi.
const SANTE_TYPE = 'École de Santé';

const STATUT_OPTIONS = [
  { value: 'public', label: 'Public' },
  { value: 'prive', label: 'Privé' },
];

// Le CAP n'existe qu'au Mali : obligatoire pour Fondamentale I/II/Collège, et
// pour un Complexe Scolaire seulement s'il contient une fondamentale (meme
// regle que ConfigurationController::validateEcole() cote backend).
const CAP_ALWAYS_REQUIRED_TYPES = ['Fondamentale I', 'Fondamentale II', 'Collège'];
const MALI_CODE_ISO = 'ML';

export default function EcolesScreen() {
  const { t } = useLocale();
  const OUI_NON_OPTIONS = [
    { value: '0', label: t('configuration.non') },
    { value: '1', label: t('configuration.oui') },
  ];
  const { user } = useAuth();
  const isSupAdmin = user?.droit === 'SupAdmin';
  const list = usePaginatedApi<Ecole>('/configuration/ecoles');
  const { items: academies } = useAllPaginated<Academie>('/configuration/academies');
  const { items: caps } = useAllPaginated<Cap>('/configuration/caps');
  const { data: abonnementData } = useApiGet<{ all_offres: Offre[] }>('/abonnements');
  const { data: paysListe } = useApiGet<Pays[]>('/pays');

  const [editing, setEditing] = useState<Ecole | null>(null);
  const [dialogVisible, setDialogVisible] = useState(false);
  const [nomEcole, setNomEcole] = useState('');
  const [typeEcole, setTypeEcole] = useState<string | null>(null);
  const [statut, setStatut] = useState('public');
  const [idPays, setIdPays] = useState<number | null>(null);
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

  const maliPays = (paysListe ?? []).find((p) => p.code_iso === MALI_CODE_ISO) ?? null;

  function openDialog(item?: Ecole) {
    setEditing(item ?? null);
    setNomEcole(item?.nomEcole ?? '');
    setTypeEcole(item?.typeEcole ?? null);
    setStatut(item?.statut ?? 'public');
    setIdPays(item?.id_pays ?? maliPays?.id ?? null);
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

  // Pas de pays choisi = Mali par defaut, meme regle que
  // ConfigurationController::validateEcole() cote backend.
  const selectedPays = (paysListe ?? []).find((p) => p.id === idPays) ?? null;
  const estMali = !selectedPays || selectedPays.code_iso === MALI_CODE_ISO;
  const estSante = typeEcole === SANTE_TYPE;
  const isComplexe = typeEcole === 'Complexe Scolaire';
  const needsCap =
    estMali && typeEcole
      ? CAP_ALWAYS_REQUIRED_TYPES.includes(typeEcole) || (typeEcole === 'Complexe Scolaire' && nomFondamental.trim() !== '')
      : false;
  const typeOptions = [...(estMali ? MALI_TYPE_OPTIONS : NON_MALI_TYPE_OPTIONS), SANTE_TYPE].map((t) => ({ value: t, label: t }));

  async function handleSubmit() {
    if (!nomEcole.trim() || !typeEcole || !statut || (estMali && !estSante && !idAcademie) || (needsCap && !idCap)) {
      setFormError(t('configuration.uf_champs_obligatoires_mobile'));
      return;
    }
    setIsSubmitting(true);
    setFormError(null);
    try {
      const form = new FormData();
      form.append('nomEcole', nomEcole.trim());
      form.append('typeEcole', typeEcole);
      form.append('statut', statut);
      if (idPays) form.append('id_pays', String(idPays));
      if (idAcademie) form.append('id_academie', String(idAcademie));
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
      setFormError(apiErrorMessage(err, t('configuration.eco_save_error_mobile')));
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

  // Le referentiel academie/CAP est propre a chaque pays (cf. Phase 5 backend)
  // -- ne proposer que celles du pays choisi dans le formulaire.
  const academieOptions = academies
    .filter((a) => !idPays || a.id_pays === idPays)
    .map((a) => ({ value: a.id_academie, label: a.nom_academie }));
  const capOptions = caps
    .filter((c) => (!idAcademie || c.id_academie === idAcademie) && (!idPays || c.id_pays === idPays))
    .map((c) => ({ value: c.id_cap, label: c.nom_cap }));
  const paysOptions = (paysListe ?? []).map((p) => ({ value: p.id, label: p.nom }));
  const offreOptions = (abonnementData?.all_offres ?? [])
    .filter((o) => o.actif)
    .map((o) => ({ value: o.id, label: `${o.nom} — ${Number(o.montant).toLocaleString('fr-FR')} ${o.devise}` }));

  if (list.isLoading) return <Text style={styles.empty}>{t('configuration.loading_mobile')}</Text>;

  return (
    <View style={styles.container}>
      <FlatList
        data={list.items}
        keyExtractor={(item) => String(item.idEcole)}
        contentContainerStyle={styles.content}
        refreshing={list.isRefreshing}
        onRefresh={list.refresh}
        onEndReached={list.loadMore}
        ListEmptyComponent={<Text style={styles.empty}>{list.error ?? t('configuration.eco_empty')}</Text>}
        renderItem={({ item }) => (
          <View style={styles.row}>
            <View style={styles.rowInfo}>
              <Text style={styles.title}>{item.nomEcole}</Text>
              <Text style={styles.meta}>
                {item.typeEcole} · {item.statut === 'public' ? 'Public' : 'Privé'}
              </Text>
            </View>
            {isSupAdmin ? (
              <View style={styles.actions}>
                <Button compact onPress={() => openDialog(item)}>
                  {t('configuration.modifier')}
                </Button>
                <Button compact textColor="#d33" onPress={() => handleDelete(item)}>
                  {t('configuration.supprimer')}
                </Button>
              </View>
            ) : null}
          </View>
        )}
      />
      {isSupAdmin ? <FAB icon="plus" style={styles.fab} onPress={() => openDialog()} /> : null}

      <Portal>
        <Dialog visible={dialogVisible} onDismiss={() => setDialogVisible(false)} style={styles.dialog}>
          <Dialog.Title>{editing ? t('configuration.eco_modal_edit_title') : t('configuration.eco_modal_create_title')}</Dialog.Title>
          <Dialog.ScrollArea style={styles.dialogScroll}>
            <ScrollView contentContainerStyle={styles.dialogContent}>
              <TextInput mode="outlined" label={requiredLabel(t('configuration.eco_modal_nom_label'))} value={nomEcole} onChangeText={setNomEcole} style={styles.input} />
              <SelectField
                label={requiredLabel(t('configuration.menu_pays'))}
                value={idPays}
                options={paysOptions}
                onChange={(v) => {
                  setIdPays(v as number);
                  setTypeEcole(null);
                  setIdAcademie(null);
                  setIdCap(null);
                }}
              />
              <SelectField label={requiredLabel(t('configuration.eco_th_type'))} value={typeEcole} options={typeOptions} onChange={(v) => setTypeEcole(v as string)} />
              <SelectField label={requiredLabel(t('configuration.th_statut'))} value={statut} options={STATUT_OPTIONS} onChange={(v) => setStatut(v as string)} />
              <SelectField
                label={estMali && !estSante ? requiredLabel(t('configuration.menu_academies')) : t('configuration.eco_academie_optionnelle_mobile')}
                value={idAcademie}
                options={academieOptions}
                onChange={(v) => {
                  setIdAcademie(v as number);
                  setIdCap(null);
                }}
              />
              {estSante ? (
                <Text style={styles.helperText}>{t('configuration.eco_sante_helper_mobile')}</Text>
              ) : !estMali ? (
                <Text style={styles.helperText}>{t('configuration.eco_academie_helper_mobile')}</Text>
              ) : null}
              {needsCap ? <SelectField label={requiredLabel(t('configuration.menu_caps'))} value={idCap} options={capOptions} onChange={(v) => setIdCap(v as number)} /> : null}
              <TextInput mode="outlined" label={t('configuration.eco_telephone_optionnel_mobile')} value={telephone} onChangeText={setTelephone} keyboardType="phone-pad" style={styles.input} />
              <TextInput mode="outlined" label={t('configuration.eco_email_optionnel_mobile')} value={email} onChangeText={setEmail} keyboardType="email-address" autoCapitalize="none" style={styles.input} />
              <SelectField label={t('configuration.eco_modal_notif_sms_label')} value={notificationSms} options={OUI_NON_OPTIONS} onChange={(v) => setNotificationSms(v as string)} />
              <SelectField label={t('configuration.eco_modal_notif_email_label')} value={notificationEmail} options={OUI_NON_OPTIONS} onChange={(v) => setNotificationEmail(v as string)} />
              {user?.droit === 'SupAdmin' ? (
                <SelectField
                  label={editing ? t('configuration.eco_modal_abonnement_label_edit') : t('configuration.eco_modal_abonnement_label_create_mobile')}
                  value={abonnementOffreId}
                  options={offreOptions}
                  onChange={(v) => setAbonnementOffreId(v as number)}
                />
              ) : null}
              <TextInput mode="outlined" label={t('configuration.eco_adresse_optionnel_mobile')} value={adresse} onChangeText={setAdresse} multiline style={styles.input} />
              {isComplexe ? (
                <>
                  <TextInput mode="outlined" label={t('configuration.eco_modal_nomComplexe_label')} value={nomComplexe} onChangeText={setNomComplexe} style={styles.input} />
                  <TextInput mode="outlined" label={t('configuration.eco_modal_nomFondamental_label_mobile')} value={nomFondamental} onChangeText={setNomFondamental} style={styles.input} />
                  <TextInput mode="outlined" label={t('configuration.eco_modal_nomLycee_label_mobile')} value={nomLycee} onChangeText={setNomLycee} style={styles.input} />
                  <TextInput mode="outlined" label={t('configuration.eco_modal_nomProfessionnel_label_mobile')} value={nomProfessionnel} onChangeText={setNomProfessionnel} style={styles.input} />
                </>
              ) : null}
              <Button mode="outlined" onPress={pickLogo} style={styles.input}>
                {logoUri ? t('configuration.eco_changer_logo_mobile') : t('configuration.eco_choisir_logo_mobile')}
              </Button>
              {formError ? <Text style={styles.error}>{formError}</Text> : null}
            </ScrollView>
          </Dialog.ScrollArea>
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
        message={editing ? t('configuration.eco_edit_success_mobile') : t('configuration.eco_create_success_mobile')}
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
  helperText: { opacity: 0.6, fontSize: 12, marginTop: -4, marginBottom: 8 },
});
