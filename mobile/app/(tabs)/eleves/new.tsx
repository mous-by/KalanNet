import { useMemo, useState } from 'react';
import { router } from 'expo-router';
import * as ImagePicker from 'expo-image-picker';
import { Image, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { Text, TextInput } from 'react-native-paper';

import DateField from '@/components/DateField';
import OfflineBanner from '@/components/OfflineBanner';
import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useAuth } from '@/context/AuthContext';
import { useOffline } from '@/context/OfflineContext';
import { api, apiErrorMessage } from '@/lib/api';
import { formatMontant } from '@/lib/currency';
import { useApiGet } from '@/lib/useApi';
import { AnneeScolaire, Classe, Matiere, ParentEleve, Planification } from '@/types/api';

const GENRE_OPTIONS = [
  { value: 'Masculin', label: 'Masculin' },
  { value: 'Féminin', label: 'Féminin' },
];

const CAS_SOCIAL_OPTIONS = [
  { value: 'normal', label: 'Normal' },
  { value: 'Dipenser', label: 'Dispensé' },
  { value: 'Malade', label: 'Malade' },
];

const MODE_PAIEMENT_OPTIONS = [
  { value: '', label: 'Non défini' },
  { value: 'Mensuel', label: 'Mensuel' },
  { value: 'Trimestriel', label: 'Trimestriel' },
  { value: 'Annuel', label: 'Annuel' },
];

const LIEN_PARENT_OPTIONS = ['Parent', 'Père', 'Mère', 'Frère', 'Sœur', 'Tuteur', 'Tutrice', 'Autre'].map((v) => ({ value: v, label: v }));

const INFORMER_OPTIONS = [
  { value: 'Oui', label: 'Oui' },
  { value: 'Non', label: 'Non' },
];

interface InscriptionOptions {
  classes: Classe[];
  annees: AnneeScolaire[];
  parents: ParentEleve[];
  planifications: Planification[];
  planification_required: boolean;
  planification_label: string;
  matieres_lv2: Matiere[];
}

export default function NewEleveScreen() {
  const { user } = useAuth();
  const { isOnline, enqueueAction } = useOffline();
  const { data: options, error: optionsError } = useApiGet<InscriptionOptions>('/eleves/inscription-options', [], {
    cacheKey: 'eleves-inscription-options',
  });

  const [prenom, setPrenom] = useState('');
  const [nom, setNom] = useState('');
  const [dateNaissance, setDateNaissance] = useState<string | null>(null);
  const [lieuNaissance, setLieuNaissance] = useState('');
  const [adresse, setAdresse] = useState('');
  const [genre, setGenre] = useState<string | null>(null);
  const [matricule, setMatricule] = useState('');
  const [dateInscription, setDateInscription] = useState<string | null>(new Date().toISOString().slice(0, 10));
  const [casSocial, setCasSocial] = useState<string>('normal');
  const [idClasse, setIdClasse] = useState<number | null>(null);
  const [modePaiement, setModePaiement] = useState<string>('');
  const [idAnnee, setIdAnnee] = useState<number | null>(null);
  const [idPlanification, setIdPlanification] = useState<number | null>(null);
  const [idMatiereLv2, setIdMatiereLv2] = useState<number | null>(null);
  const [avatarUri, setAvatarUri] = useState<string | null>(null);

  const [parentId, setParentId] = useState<number | null>(null);
  const [lienParent, setLienParent] = useState<string>('Parent');
  const [informer, setInformer] = useState<string>('Oui');

  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);
  const [successMessage, setSuccessMessage] = useState('Élève inscrit avec succès.');

  const planificationRequired = options?.planification_required ?? false;
  const planificationLabel = options?.planification_label ?? 'Formule de paiement';

  const classeOptions = (options?.classes ?? []).map((c) => ({ value: c.id_classe, label: `${c.nom_classe} - ${c.ordreEnseignement}` }));
  const anneeOptions = (options?.annees ?? []).map((a) => ({ value: a.id_anneeScolaire, label: a.annee }));
  const parentOptions = [{ value: 0, label: 'Aucun rattachement maintenant' }, ...(options?.parents ?? []).map((p) => ({ value: p.id_parent, label: p.nom_prenom_parent }))];
  const matiereLv2Options = [
    { value: 0, label: 'Non applicable / pas encore choisie' },
    ...(options?.matieres_lv2 ?? []).map((m) => ({ value: m.id_matiere, label: m.nom_matiere })),
  ];

  const planificationOptions = useMemo(() => {
    const filtered = (options?.planifications ?? []).filter((p) => p.id_classe === idClasse && p.id_annee === idAnnee);
    const items = filtered.map((p) => ({
      value: p.id_planification,
      label: `${planificationRequired ? p.motif : 'Coopérative'} - ${formatMontant(Number(p.montant_planification), user)}`,
    }));
    return [{ value: 0, label: planificationRequired ? 'Veuillez choisir' : 'Sans coopérative / sans frais' }, ...items];
  }, [options?.planifications, idClasse, idAnnee, planificationRequired, user]);

  const isValid = prenom.trim() && nom.trim() && genre && idClasse && idAnnee && (!planificationRequired || idPlanification);

  async function pickAvatar() {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) return;
    const result = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], quality: 0.8, aspect: [1, 1] });
    if (!result.canceled && result.assets[0]) {
      setAvatarUri(result.assets[0].uri);
    }
  }

  async function handleSubmit() {
    setError(null);
    if (!isValid) {
      setError('Veuillez renseigner tous les champs obligatoires.');
      return;
    }
    if (!isOnline && avatarUri) {
      setError('Une photo ne peut pas être envoyée hors ligne. Retirez-la ou connectez-vous pour l’ajouter.');
      return;
    }
    setIsSubmitting(true);
    try {
      const classeLabel = classeOptions.find((c) => c.value === idClasse)?.label ?? '';
      const jsonPayload: Record<string, unknown> = {
        prenom_eleve: prenom.trim(),
        nom_eleve: nom.trim(),
        genre_eleve: genre,
        date_naissance: dateNaissance || undefined,
        lieu_naiss: lieuNaissance.trim() || undefined,
        adresse_eleve: adresse.trim() || undefined,
        matricule: matricule.trim() || undefined,
        date_inscription: dateInscription || undefined,
        cas_social: casSocial,
        id_classe: idClasse,
        mode_paiement: modePaiement || undefined,
        id_annee: idAnnee,
        id_planification: idPlanification || undefined,
        id_matiere_lv2: idMatiereLv2 || undefined,
        ...(parentId ? { parent_id: parentId, lien_parent: lienParent, informer } : {}),
      };

      if (!isOnline) {
        await enqueueAction({
          kind: 'eleve',
          label: `${prenom.trim()} ${nom.trim()} · ${classeLabel}`,
          endpoint: '/eleves',
          method: 'post',
          payload: jsonPayload,
        });
        setSuccessMessage('Élève mis en attente, sera synchronisé au retour du réseau.');
        setSuccessVisible(true);
        setTimeout(() => router.back(), 900);
        return;
      }

      const form = new FormData();
      form.append('prenom_eleve', prenom.trim());
      form.append('nom_eleve', nom.trim());
      form.append('genre_eleve', genre as string);
      if (dateNaissance) form.append('date_naissance', dateNaissance);
      if (lieuNaissance.trim()) form.append('lieu_naiss', lieuNaissance.trim());
      if (adresse.trim()) form.append('adresse_eleve', adresse.trim());
      if (matricule.trim()) form.append('matricule', matricule.trim());
      if (dateInscription) form.append('date_inscription', dateInscription);
      form.append('cas_social', casSocial);
      form.append('id_classe', String(idClasse));
      if (modePaiement) form.append('mode_paiement', modePaiement);
      form.append('id_annee', String(idAnnee));
      if (idPlanification) form.append('id_planification', String(idPlanification));
      if (idMatiereLv2) form.append('id_matiere_lv2', String(idMatiereLv2));
      if (avatarUri) form.append('image', { uri: avatarUri, name: 'eleve.jpg', type: 'image/jpeg' } as unknown as Blob);
      if (parentId) {
        form.append('parent_id', String(parentId));
        form.append('lien_parent', lienParent);
        form.append('informer', informer);
      }

      await api.post('/eleves', form, { headers: { 'Content-Type': 'multipart/form-data' } });
      setSuccessMessage('Élève inscrit avec succès.');
      setSuccessVisible(true);
      setTimeout(() => router.back(), 900);
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible d’inscrire cet élève.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <OfflineBanner />
      {optionsError ? <Text style={styles.error}>{optionsError}</Text> : null}
      <View style={styles.avatarRow}>
        <Pressable onPress={pickAvatar}>
          {avatarUri ? (
            <Image source={{ uri: avatarUri }} style={styles.avatar} />
          ) : (
            <View style={[styles.avatar, styles.avatarPlaceholder]}>
              <Text style={styles.avatarPlaceholderText}>Photo</Text>
            </View>
          )}
        </Pressable>
        <Text style={styles.avatarHint}>Touchez pour choisir une photo</Text>
      </View>

      <TextInput mode="outlined" label={requiredLabel('Prénom')} value={prenom} onChangeText={setPrenom} style={styles.input} />
      <TextInput mode="outlined" label={requiredLabel('Nom')} value={nom} onChangeText={setNom} style={styles.input} />
      <DateField label="Date de naissance" value={dateNaissance} onChange={setDateNaissance} />
      <TextInput mode="outlined" label="Lieu de naissance" value={lieuNaissance} onChangeText={setLieuNaissance} placeholder="Ex: Kayes" style={styles.input} />
      <TextInput mode="outlined" label="Adresse / Quartier" value={adresse} onChangeText={setAdresse} style={styles.input} />
      <SelectField label={requiredLabel('Genre')} value={genre} options={GENRE_OPTIONS} onChange={(v) => setGenre(v as string)} />
      <TextInput mode="outlined" label="Matricule (auto si vide)" value={matricule} onChangeText={setMatricule} style={styles.input} />
      <DateField label="Date d'inscription" value={dateInscription} onChange={setDateInscription} />
      <SelectField label="Cas social" value={casSocial} options={CAS_SOCIAL_OPTIONS} onChange={(v) => setCasSocial(v as string)} />
      <SelectField
        label={requiredLabel('Classe')}
        value={idClasse}
        options={classeOptions}
        onChange={(v) => {
          setIdClasse(v as number);
          setIdPlanification(null);
        }}
      />
      <SelectField label="Mode de paiement" value={modePaiement} options={MODE_PAIEMENT_OPTIONS} onChange={(v) => setModePaiement(v as string)} />
      <SelectField
        label="Langue LV2"
        value={idMatiereLv2 ?? 0}
        options={matiereLv2Options}
        onChange={(v) => setIdMatiereLv2((v as number) || null)}
      />
      <SelectField
        label={requiredLabel('Année scolaire')}
        value={idAnnee}
        options={anneeOptions}
        onChange={(v) => {
          setIdAnnee(v as number);
          setIdPlanification(null);
        }}
      />
      <SelectField
        label={planificationRequired ? requiredLabel(planificationLabel) : planificationLabel}
        value={idPlanification ?? 0}
        options={planificationOptions}
        onChange={(v) => setIdPlanification((v as number) || null)}
        disabled={!idClasse || !idAnnee}
      />

      <Text style={styles.sectionTitle}>Parent déjà inscrit</Text>
      <SelectField label="Parent" value={parentId ?? 0} options={parentOptions} onChange={(v) => setParentId((v as number) || null)} />
      {parentId ? (
        <>
          <SelectField label="Lien de parenté" value={lienParent} options={LIEN_PARENT_OPTIONS} onChange={(v) => setLienParent(v as string)} />
          <SelectField label="Informer" value={informer} options={INFORMER_OPTIONS} onChange={(v) => setInformer(v as string)} />
        </>
      ) : null}

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label="Valider l'inscription" onPress={handleSubmit} loading={isSubmitting} />

      <SuccessSnackbar visible={successVisible} message={successMessage} onDismiss={() => setSuccessVisible(false)} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: {
    padding: 20,
  },
  avatarRow: {
    alignItems: 'center',
    marginBottom: 20,
  },
  avatar: {
    width: 88,
    height: 88,
    borderRadius: 44,
  },
  avatarPlaceholder: {
    backgroundColor: 'rgba(128,128,128,0.15)',
    alignItems: 'center',
    justifyContent: 'center',
  },
  avatarPlaceholderText: {
    fontSize: 12,
    opacity: 0.6,
  },
  avatarHint: {
    fontSize: 12,
    opacity: 0.6,
    marginTop: 6,
  },
  sectionTitle: {
    fontWeight: '700',
    fontSize: 15,
    marginTop: 8,
    marginBottom: 12,
  },
  input: {
    marginBottom: 12,
  },
  error: {
    color: '#d33',
    marginBottom: 12,
  },
});
