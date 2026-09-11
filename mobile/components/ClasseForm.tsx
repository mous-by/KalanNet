import { useEffect, useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { IconButton, Text, TextInput } from 'react-native-paper';

import OfflineBanner from '@/components/OfflineBanner';
import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useOffline } from '@/context/OfflineContext';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';
import { Classe, Enseignant, Matiere } from '@/types/api';

interface LigneForm {
  id_matiere: number | null;
  id_enseignant: number | null;
  coefficient: string;
}

interface FormOptions {
  matieres: Matiere[];
  enseignants: Enseignant[];
  // Code interne -> libellé (ex: "fondamentale1" -> "Fondamentale I (1 à 6)"),
  // pas un tableau — voir ClasseController::ordresDisponibles() cote backend,
  // qui renvoie un tableau associatif dans toutes ses branches.
  ordres: Record<string, string>;
}

interface Props {
  classe?: Classe & { ligneClasses?: Array<{ id_matiere: number; id_enseignants: number | null; coefficient: number }> };
  onSaved: () => void;
}

export default function ClasseForm({ classe, onSaved }: Props) {
  const { isOnline, enqueueAction } = useOffline();
  const { data: options, error: optionsError } = useApiGet<FormOptions>('/classes/form-options', [], { cacheKey: 'classes-form-options' });

  const [nomClasse, setNomClasse] = useState(classe?.nom_classe ?? '');
  const [ordre, setOrdre] = useState<string | null>(classe?.ordreEnseignement ?? null);
  const [lignes, setLignes] = useState<LigneForm[]>([{ id_matiere: null, id_enseignant: null, coefficient: '1' }]);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);
  const [successMessage, setSuccessMessage] = useState(classe ? 'Classe modifiée avec succès.' : 'Classe créée avec succès.');

  useEffect(() => {
    if (classe?.ligneClasses?.length) {
      setLignes(
        classe.ligneClasses.map((l) => ({
          id_matiere: l.id_matiere,
          id_enseignant: l.id_enseignants,
          coefficient: String(l.coefficient),
        }))
      );
    }
  }, [classe]);

  function updateLigne(index: number, patch: Partial<LigneForm>) {
    setLignes((prev) => prev.map((l, i) => (i === index ? { ...l, ...patch } : l)));
  }

  function addLigne() {
    setLignes((prev) => [...prev, { id_matiere: null, id_enseignant: null, coefficient: '1' }]);
  }

  function removeLigne(index: number) {
    setLignes((prev) => prev.filter((_, i) => i !== index));
  }

  async function handleSubmit() {
    setError(null);
    const validLignes = lignes.filter((l) => l.id_matiere);
    if (!nomClasse.trim() || !ordre || validLignes.length === 0) {
      setError('Le nom, l’ordre d’enseignement et au moins une matière sont requis.');
      return;
    }

    setIsSubmitting(true);
    const payload = {
      nom_classe: nomClasse.trim(),
      ordre_enseignement: ordre,
      id_matiere: validLignes.map((l) => l.id_matiere),
      id_enseignants: validLignes.map((l) => l.id_enseignant),
      coefficient: validLignes.map((l) => Number(l.coefficient) || 0),
    };
    try {
      if (!isOnline) {
        await enqueueAction({
          kind: 'classe',
          label: nomClasse.trim(),
          endpoint: classe ? `/classes/${classe.id_classe}` : '/classes',
          method: classe ? 'put' : 'post',
          payload,
        });
        setSuccessMessage('Classe mise en attente, sera synchronisée au retour du réseau.');
        setSuccessVisible(true);
        setTimeout(onSaved, 900);
        return;
      }
      if (classe) {
        await api.put(`/classes/${classe.id_classe}`, payload);
      } else {
        await api.post('/classes', payload);
      }
      setSuccessMessage(classe ? 'Classe modifiée avec succès.' : 'Classe créée avec succès.');
      setSuccessVisible(true);
      setTimeout(onSaved, 900);
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible d’enregistrer cette classe.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  const ordreOptions = Object.entries(options?.ordres ?? {}).map(([value, label]) => ({ value, label }));
  const matiereOptions = (options?.matieres ?? []).map((m) => ({ value: m.id_matiere, label: m.nom_matiere }));
  const enseignantOptions = (options?.enseignants ?? []).map((e) => ({ value: e.id_enseignant, label: e.nom_prenom_enseignant }));

  return (
    <View>
      <OfflineBanner />
      {optionsError ? <Text style={styles.error}>{optionsError}</Text> : null}
      <TextInput mode="outlined" label={requiredLabel('Nom de la classe')} value={nomClasse} onChangeText={setNomClasse} style={styles.input} />
      <SelectField label={requiredLabel("Ordre d'enseignement")} value={ordre} options={ordreOptions} onChange={(v) => setOrdre(v as string)} />

      <Text style={styles.sectionTitle}>Matières</Text>
      {lignes.map((ligne, index) => (
        <View key={index} style={styles.ligneRow}>
          <View style={styles.ligneFields}>
            <SelectField
              label={requiredLabel('Matière')}
              value={ligne.id_matiere}
              options={matiereOptions}
              onChange={(v) => updateLigne(index, { id_matiere: v as number })}
            />
            <SelectField
              label="Enseignant (optionnel)"
              value={ligne.id_enseignant}
              options={enseignantOptions}
              onChange={(v) => updateLigne(index, { id_enseignant: v as number })}
            />
            <TextInput
              mode="outlined"
              label="Coefficient"
              keyboardType="numeric"
              value={ligne.coefficient}
              onChangeText={(v) => updateLigne(index, { coefficient: v })}
              style={styles.input}
            />
          </View>
          {lignes.length > 1 ? <IconButton icon="delete-outline" onPress={() => removeLigne(index)} /> : null}
        </View>
      ))}
      <IconButton icon="plus" mode="outlined" onPress={addLigne} style={styles.addButton} />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <SubmitButton label={classe ? 'Enregistrer' : 'Créer la classe'} onPress={handleSubmit} loading={isSubmitting} />

      <SuccessSnackbar visible={successVisible} message={successMessage} onDismiss={() => setSuccessVisible(false)} />
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
