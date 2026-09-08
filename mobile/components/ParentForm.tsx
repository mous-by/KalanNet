import { useEffect, useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { IconButton, Text, TextInput } from 'react-native-paper';

import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';
import { ParentEleve } from '@/types/api';

const GENRE_OPTIONS = [
  { value: 'Masculin', label: 'Masculin' },
  { value: 'Féminin', label: 'Féminin' },
];

const LIEN_OPTIONS = ['Parent', 'Père', 'Mère', 'Frère', 'Sœur', 'Tuteur', 'Tutrice', 'Autre'].map((v) => ({ value: v, label: v }));

const INFORMER_OPTIONS = [
  { value: 'Oui', label: 'Oui' },
  { value: 'Non', label: 'Non' },
];

interface EleveOption {
  id_eleve: number;
  nom: string;
  matricule: string | null;
  classe: string | null;
}

interface LinkedEleve {
  id_eleve: number;
  nom: string;
  matricule: string | null;
  classe: string | null;
  lien_parent: string;
  informer: string;
}

interface ParentDetail {
  id_parent: number;
  nom_prenom_parent: string;
  telephone_parent: string | null;
  email_parent: string | null;
  genre: string | null;
}

interface LigneForm {
  id_eleve: number | null;
  lien_parent: string;
  informer: string;
}

interface Props {
  parentId?: number;
  onSaved: () => void;
}

export default function ParentForm({ parentId, onSaved }: Props) {
  const { data: options } = useApiGet<{ eleves: EleveOption[] }>(
    parentId ? `/parents/form-options?id=${parentId}` : '/parents/form-options'
  );
  const { data: detail } = useApiGet<{ parent: ParentDetail; eleves: LinkedEleve[] }>(parentId ? `/parents/${parentId}` : null, [parentId]);

  const [nomPrenom, setNomPrenom] = useState('');
  const [telephone, setTelephone] = useState('');
  const [email, setEmail] = useState('');
  const [genre, setGenre] = useState<string | null>(null);
  const [lignes, setLignes] = useState<LigneForm[]>([{ id_eleve: null, lien_parent: 'Parent', informer: 'Oui' }]);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);

  useEffect(() => {
    if (!detail) return;
    setNomPrenom(detail.parent.nom_prenom_parent);
    setTelephone(detail.parent.telephone_parent ?? '');
    setEmail(detail.parent.email_parent ?? '');
    setGenre(detail.parent.genre ?? null);
    if (detail.eleves.length > 0) {
      setLignes(
        detail.eleves.map((e) => ({ id_eleve: e.id_eleve, lien_parent: e.lien_parent, informer: e.informer }))
      );
    }
  }, [detail]);

  function updateLigne(index: number, patch: Partial<LigneForm>) {
    setLignes((prev) => prev.map((l, i) => (i === index ? { ...l, ...patch } : l)));
  }

  function addLigne() {
    setLignes((prev) => [...prev, { id_eleve: null, lien_parent: 'Parent', informer: 'Oui' }]);
  }

  function removeLigne(index: number) {
    setLignes((prev) => prev.filter((_, i) => i !== index));
  }

  async function handleSubmit() {
    setError(null);
    const validLignes = lignes.filter((l) => l.id_eleve);
    if (!nomPrenom.trim() || !telephone.trim() || validLignes.length === 0) {
      setError('Le nom, le téléphone et au moins un élève rattaché sont requis.');
      return;
    }
    setIsSubmitting(true);
    try {
      const payload = {
        nom_prenom_parent: nomPrenom.trim(),
        telephone_parent: telephone.trim(),
        email_parent: email.trim() || null,
        genre,
        id_eleve: validLignes.map((l) => l.id_eleve),
        lien_parent: validLignes.map((l) => l.lien_parent),
        informer: validLignes.map((l) => l.informer),
      };

      if (parentId) {
        await api.put(`/parents/${parentId}`, payload);
      } else {
        await api.post('/parents', payload);
      }
      setSuccessVisible(true);
      setTimeout(onSaved, 900);
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible d’enregistrer ce parent.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  const eleveOptions = (options?.eleves ?? []).map((e) => ({
    value: e.id_eleve,
    label: `${e.nom}${e.classe ? ` — ${e.classe}` : ''}`,
  }));

  return (
    <View>
      <TextInput mode="outlined" label={requiredLabel('Nom et prénom')} value={nomPrenom} onChangeText={setNomPrenom} style={styles.input} />
      <TextInput mode="outlined" label={requiredLabel('Téléphone')} value={telephone} onChangeText={setTelephone} keyboardType="phone-pad" style={styles.input} />
      <TextInput mode="outlined" label="Email (optionnel)" value={email} onChangeText={setEmail} keyboardType="email-address" autoCapitalize="none" style={styles.input} />
      <SelectField label="Genre (optionnel)" value={genre} options={GENRE_OPTIONS} onChange={(v) => setGenre(v as string)} />

      <Text style={styles.sectionTitle}>Élèves rattachés</Text>
      {lignes.map((ligne, index) => (
        <View key={index} style={styles.ligneRow}>
          <View style={styles.ligneFields}>
            <SelectField
              label={requiredLabel('Élève')}
              value={ligne.id_eleve}
              options={eleveOptions}
              onChange={(v) => updateLigne(index, { id_eleve: v as number })}
            />
            <SelectField label="Lien de parenté" value={ligne.lien_parent} options={LIEN_OPTIONS} onChange={(v) => updateLigne(index, { lien_parent: v as string })} />
            <SelectField label="Informer" value={ligne.informer} options={INFORMER_OPTIONS} onChange={(v) => updateLigne(index, { informer: v as string })} />
          </View>
          {lignes.length > 1 ? <IconButton icon="delete-outline" onPress={() => removeLigne(index)} /> : null}
        </View>
      ))}
      <IconButton icon="plus" mode="outlined" onPress={addLigne} style={styles.addButton} />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label={parentId ? 'Enregistrer' : 'Créer le parent'} onPress={handleSubmit} loading={isSubmitting} />

      <SuccessSnackbar
        visible={successVisible}
        message={parentId ? 'Parent modifié avec succès.' : 'Parent créé avec succès.'}
        onDismiss={() => setSuccessVisible(false)}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  input: {
    marginBottom: 12,
  },
  sectionTitle: {
    fontWeight: '600',
    marginTop: 8,
    marginBottom: 8,
  },
  ligneRow: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 10,
    padding: 10,
    marginBottom: 10,
  },
  ligneFields: {
    flex: 1,
  },
  addButton: {
    alignSelf: 'flex-start',
    marginBottom: 12,
  },
  error: {
    color: '#d33',
    marginBottom: 12,
  },
});
