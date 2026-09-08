import { useState } from 'react';
import * as ImagePicker from 'expo-image-picker';
import { Image, Pressable, StyleSheet, View } from 'react-native';
import { Text, TextInput } from 'react-native-paper';

import DateField from '@/components/DateField';
import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
import { api, apiErrorMessage } from '@/lib/api';
import { Enseignant } from '@/types/api';

const GENRE_OPTIONS = [
  { value: 'Feminin', label: 'Féminin' },
  { value: 'Masculin', label: 'Masculin' },
];

const CONTRAT_OPTIONS = [
  { value: 'CDI', label: 'CDI' },
  { value: 'CDD', label: 'CDD' },
  { value: 'VCT', label: 'VCT' },
  { value: 'FONCTIONNAIRE', label: 'Fonctionnaire' },
];

const SALAIRE_MOIS_OPTIONS = [
  { value: 9, label: '9 mois sur 12' },
  { value: 12, label: '12 mois sur 12' },
];

const STATUT_MATRIMONIAL_OPTIONS = [
  { value: 'Célibataire', label: 'Célibataire' },
  { value: 'Marié(e)', label: 'Marié(e)' },
  { value: 'Divorcé(e)', label: 'Divorcé(e)' },
  { value: 'Veuf(ve)', label: 'Veuf(ve)' },
];

interface Props {
  enseignant?: Enseignant;
  onSaved: () => void;
}

export default function EnseignantForm({ enseignant, onSaved }: Props) {
  // Informations personnelles
  const [nomPrenom, setNomPrenom] = useState(enseignant?.nom_prenom_enseignant ?? '');
  const [genre, setGenre] = useState<string | null>(enseignant?.genre_enseignant ?? null);
  const [email, setEmail] = useState(enseignant?.email_enseignant ?? '');
  const [telephone, setTelephone] = useState(enseignant?.telephone_enseignant ?? '');
  const [dateNaissance, setDateNaissance] = useState<string | null>((enseignant?.date_naissance_enseignant as string) ?? null);
  const [lieuNaissance, setLieuNaissance] = useState((enseignant?.lieu_naissance_enseignant as string) ?? '');

  // Informations professionnelles
  const [diplome, setDiplome] = useState((enseignant?.diplome_enseignant as string) ?? '');
  const [specialite, setSpecialite] = useState((enseignant?.specialite as string) ?? '');
  const [typeContrat, setTypeContrat] = useState<string | null>((enseignant?.type_contrat_enseignant as string) ?? null);
  const [salaire, setSalaire] = useState(enseignant?.salaire_enseignant != null ? String(enseignant.salaire_enseignant) : '');
  const [salaireMoisMode, setSalaireMoisMode] = useState<number>((enseignant?.salaire_mois_mode as number) ?? 12);
  const [dureeContrat, setDureeContrat] = useState((enseignant?.duree_contrat as string) ?? '');
  const [nombreHeure, setNombreHeure] = useState(enseignant?.nombre_heure != null ? String(enseignant.nombre_heure) : '');
  const [prixHeure, setPrixHeure] = useState(enseignant?.prix_heure != null ? String(enseignant.prix_heure) : '');

  // Informations fonctionnaire
  const [statutMatrimonial, setStatutMatrimonial] = useState<string | null>((enseignant?.statut_matrimonial as string) ?? null);
  const [nombreEnfants, setNombreEnfants] = useState(enseignant?.nombre_enfants != null ? String(enseignant.nombre_enfants) : '0');
  const [serviceEmployeur, setServiceEmployeur] = useState((enseignant?.service_employeur as string) ?? '');
  const [ancienneteAnnees, setAncienneteAnnees] = useState(enseignant?.anciennete_annees != null ? String(enseignant.anciennete_annees) : '0');
  const [pereNomPrenom, setPereNomPrenom] = useState((enseignant?.pere_nom_prenom as string) ?? '');
  const [mereNomPrenom, setMereNomPrenom] = useState((enseignant?.mere_nom_prenom as string) ?? '');

  // Matricule + avatar
  const [matricule, setMatricule] = useState(enseignant?.matricule ?? '');
  const [avatarUri, setAvatarUri] = useState<string | null>(null);

  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const isCdiOrCdd = typeContrat === 'CDI' || typeContrat === 'CDD';
  const isCdd = typeContrat === 'CDD';
  const isVct = typeContrat === 'VCT';
  const isFonctionnaire = typeContrat === 'FONCTIONNAIRE';

  const isValid =
    nomPrenom.trim() && genre && email.trim() && telephone.trim() && dateNaissance && lieuNaissance.trim() && diplome.trim() && typeContrat;

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
    setIsSubmitting(true);
    try {
      const form = new FormData();
      form.append('nom_prenom', nomPrenom.trim());
      form.append('genre', genre as string);
      form.append('email', email.trim());
      form.append('telephone', telephone.trim());
      form.append('date_naissance', dateNaissance as string);
      form.append('lieu_naissance', lieuNaissance.trim());
      form.append('diplome', diplome.trim());
      if (specialite) form.append('specialite', specialite);
      form.append('type_contrat', typeContrat as string);
      if (isCdiOrCdd) {
        if (salaire) form.append('salaire', salaire);
        form.append('salaire_mois_mode', String(salaireMoisMode));
      }
      if (isCdd && dureeContrat) form.append('duree_contrat', dureeContrat);
      if (isVct) {
        if (nombreHeure) form.append('nombre_heure', nombreHeure);
        if (prixHeure) form.append('prix_heure', prixHeure);
      }
      if (isFonctionnaire) {
        if (statutMatrimonial) form.append('statut_matrimonial', statutMatrimonial);
        form.append('nombre_enfants', nombreEnfants || '0');
        if (serviceEmployeur) form.append('service_employeur', serviceEmployeur);
        form.append('anciennete_annees', ancienneteAnnees || '0');
        if (pereNomPrenom) form.append('pere_nom_prenom', pereNomPrenom);
        if (mereNomPrenom) form.append('mere_nom_prenom', mereNomPrenom);
      }
      if (matricule.trim()) form.append('matricule', matricule.trim());
      if (avatarUri) {
        form.append('avatar', { uri: avatarUri, name: 'avatar.jpg', type: 'image/jpeg' } as unknown as Blob);
      }

      if (enseignant) {
        form.append('_method', 'PUT');
        await api.post(`/enseignants/${enseignant.id_enseignant}`, form, { headers: { 'Content-Type': 'multipart/form-data' } });
      } else {
        await api.post('/enseignants', form, { headers: { 'Content-Type': 'multipart/form-data' } });
      }
      onSaved();
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible d’enregistrer cet enseignant.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <View>
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

      <Text style={styles.sectionTitle}>Informations personnelles</Text>
      <TextInput mode="outlined" label="Nom et prénom" value={nomPrenom} onChangeText={setNomPrenom} style={styles.input} />
      <SelectField label="Genre" value={genre} options={GENRE_OPTIONS} onChange={(v) => setGenre(v as string)} />
      <TextInput mode="outlined" label="Email" value={email} onChangeText={setEmail} keyboardType="email-address" autoCapitalize="none" style={styles.input} />
      <TextInput mode="outlined" label="Téléphone" value={telephone} onChangeText={setTelephone} keyboardType="phone-pad" style={styles.input} />
      <DateField label="Date de naissance" value={dateNaissance} onChange={setDateNaissance} />
      <TextInput mode="outlined" label="Lieu de naissance" value={lieuNaissance} onChangeText={setLieuNaissance} style={styles.input} />

      <Text style={styles.sectionTitle}>Informations professionnelles</Text>
      <TextInput mode="outlined" label="Diplôme" value={diplome} onChangeText={setDiplome} style={styles.input} />
      <TextInput
        mode="outlined"
        label="Spécialité (optionnel)"
        value={specialite}
        onChangeText={setSpecialite}
        placeholder="Ex: Mathématiques, Français..."
        style={styles.input}
      />
      <SelectField label="Type de contrat" value={typeContrat} options={CONTRAT_OPTIONS} onChange={(v) => setTypeContrat(v as string)} />

      {isCdiOrCdd ? (
        <>
          <TextInput mode="outlined" label="Salaire (optionnel)" keyboardType="numeric" value={salaire} onChangeText={setSalaire} style={styles.input} />
          <SelectField
            label="Mois payés dans l'année scolaire"
            value={salaireMoisMode}
            options={SALAIRE_MOIS_OPTIONS}
            onChange={(v) => setSalaireMoisMode(v as number)}
          />
        </>
      ) : null}
      {isCdd ? (
        <TextInput mode="outlined" label="Durée du contrat (optionnel)" value={dureeContrat} onChangeText={setDureeContrat} style={styles.input} />
      ) : null}
      {isVct ? (
        <>
          <TextInput
            mode="outlined"
            label="Nombre d'heures/semaine (optionnel)"
            keyboardType="numeric"
            value={nombreHeure}
            onChangeText={setNombreHeure}
            style={styles.input}
          />
          <TextInput
            mode="outlined"
            label="Prix par heure (optionnel)"
            keyboardType="numeric"
            value={prixHeure}
            onChangeText={setPrixHeure}
            style={styles.input}
          />
        </>
      ) : null}

      {isFonctionnaire ? (
        <>
          <Text style={styles.sectionTitle}>Informations fonctionnaire</Text>
          <SelectField
            label="Statut matrimonial (optionnel)"
            value={statutMatrimonial}
            options={STATUT_MATRIMONIAL_OPTIONS}
            onChange={(v) => setStatutMatrimonial(v as string)}
          />
          <TextInput mode="outlined" label="Nombre d'enfants" keyboardType="numeric" value={nombreEnfants} onChangeText={setNombreEnfants} style={styles.input} />
          <TextInput mode="outlined" label="Service employeur (optionnel)" value={serviceEmployeur} onChangeText={setServiceEmployeur} style={styles.input} />
          <TextInput mode="outlined" label="Ancienneté (années)" keyboardType="numeric" value={ancienneteAnnees} onChangeText={setAncienneteAnnees} style={styles.input} />
          <TextInput mode="outlined" label="Nom et prénom du père (optionnel)" value={pereNomPrenom} onChangeText={setPereNomPrenom} style={styles.input} />
          <TextInput mode="outlined" label="Nom et prénom de la mère (optionnel)" value={mereNomPrenom} onChangeText={setMereNomPrenom} style={styles.input} />
        </>
      ) : null}

      <Text style={styles.sectionTitle}>Autres</Text>
      <TextInput mode="outlined" label="Matricule (auto si vide)" value={matricule ?? ''} onChangeText={setMatricule} style={styles.input} />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label={enseignant ? 'Enregistrer' : 'Créer l’enseignant'} onPress={handleSubmit} loading={isSubmitting} />
    </View>
  );
}

const styles = StyleSheet.create({
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
