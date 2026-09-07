import { useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { Text, TextInput } from 'react-native-paper';

import DateField from '@/components/DateField';
import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
import { api, apiErrorMessage } from '@/lib/api';
import { Enseignant } from '@/types/api';

const GENRE_OPTIONS = [
  { value: 'Masculin', label: 'Masculin' },
  { value: 'Féminin', label: 'Féminin' },
];

const CONTRAT_OPTIONS = [
  { value: 'CDI', label: 'CDI' },
  { value: 'CDD', label: 'CDD' },
  { value: 'VCT', label: 'VCT' },
  { value: 'FONCTIONNAIRE', label: 'Fonctionnaire' },
];

interface Props {
  enseignant?: Enseignant;
  onSaved: () => void;
}

export default function EnseignantForm({ enseignant, onSaved }: Props) {
  const [nomPrenom, setNomPrenom] = useState(enseignant?.nom_prenom_enseignant ?? '');
  const [genre, setGenre] = useState<string | null>(enseignant?.genre_enseignant ?? null);
  const [email, setEmail] = useState(enseignant?.email_enseignant ?? '');
  const [telephone, setTelephone] = useState(enseignant?.telephone_enseignant ?? '');
  const [dateNaissance, setDateNaissance] = useState<string | null>((enseignant?.date_naissance as string) ?? null);
  const [lieuNaissance, setLieuNaissance] = useState((enseignant?.lieu_naissance as string) ?? '');
  const [diplome, setDiplome] = useState((enseignant?.diplome as string) ?? '');
  const [typeContrat, setTypeContrat] = useState<string | null>((enseignant?.type_contrat as string) ?? null);
  const [matricule, setMatricule] = useState(enseignant?.matricule ?? '');
  const [salaire, setSalaire] = useState(enseignant?.salaire != null ? String(enseignant.salaire) : '');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const isValid = nomPrenom.trim() && genre && email.trim() && telephone.trim() && dateNaissance && lieuNaissance.trim() && diplome.trim() && typeContrat;

  async function handleSubmit() {
    setError(null);
    if (!isValid) {
      setError('Veuillez renseigner tous les champs obligatoires.');
      return;
    }
    setIsSubmitting(true);
    try {
      const payload = {
        nom_prenom: nomPrenom.trim(),
        genre,
        email: email.trim(),
        telephone: telephone.trim(),
        date_naissance: dateNaissance,
        lieu_naissance: lieuNaissance.trim(),
        diplome: diplome.trim(),
        type_contrat: typeContrat,
        matricule: matricule.trim() || undefined,
        salaire: salaire ? Number(salaire) : undefined,
      };

      if (enseignant) {
        await api.put(`/enseignants/${enseignant.id_enseignant}`, payload);
      } else {
        await api.post('/enseignants', payload);
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
      <TextInput mode="outlined" label="Nom et prénom" value={nomPrenom} onChangeText={setNomPrenom} style={styles.input} />
      <SelectField label="Genre" value={genre} options={GENRE_OPTIONS} onChange={(v) => setGenre(v as string)} />
      <TextInput mode="outlined" label="Email" value={email} onChangeText={setEmail} keyboardType="email-address" autoCapitalize="none" style={styles.input} />
      <TextInput mode="outlined" label="Téléphone" value={telephone} onChangeText={setTelephone} keyboardType="phone-pad" style={styles.input} />
      <DateField label="Date de naissance" value={dateNaissance} onChange={setDateNaissance} />
      <TextInput mode="outlined" label="Lieu de naissance" value={lieuNaissance} onChangeText={setLieuNaissance} style={styles.input} />
      <TextInput mode="outlined" label="Diplôme" value={diplome} onChangeText={setDiplome} style={styles.input} />
      <SelectField label="Type de contrat" value={typeContrat} options={CONTRAT_OPTIONS} onChange={(v) => setTypeContrat(v as string)} />
      <TextInput mode="outlined" label="Matricule (optionnel)" value={matricule ?? ''} onChangeText={setMatricule} style={styles.input} />
      <TextInput mode="outlined" label="Salaire (optionnel)" value={salaire} onChangeText={setSalaire} keyboardType="numeric" style={styles.input} />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label={enseignant ? 'Enregistrer' : 'Créer l’enseignant'} onPress={handleSubmit} loading={isSubmitting} />
    </View>
  );
}

const styles = StyleSheet.create({
  input: {
    marginBottom: 12,
  },
  error: {
    color: '#d33',
    marginBottom: 12,
  },
});
