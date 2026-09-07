import { useMemo, useState } from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Dialog, FAB, IconButton, Portal, Text, TextInput } from 'react-native-paper';

import SelectField from '@/components/SelectField';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';
import { AnneeScolaire, Classe, Enseignant, LigneClasse, Matiere } from '@/types/api';

const JOURS = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];

interface Course {
  id: number;
  id_matiere: number;
  id_enseignant: number | null;
  heure_debut: string;
  heure_fin: string;
  matiere?: Matiere;
  enseignant?: Enseignant;
}

interface TimetableData {
  classes: Classe[];
  annees: AnneeScolaire[];
  lignes_classe: LigneClasse[];
  timetable: Record<string, Course[]>;
}

interface SlotForm {
  jour: string;
  id_matiere: number | null;
  id_enseignant: number | null;
  heure_debut: string;
  heure_fin: string;
}

const EMPTY_SLOT: SlotForm = { jour: JOURS[0], id_matiere: null, id_enseignant: null, heure_debut: '', heure_fin: '' };

export default function TimetableScreen() {
  const { user } = useAuth();
  const [idClasse, setIdClasse] = useState<number | null>(null);
  const [idAnnee, setIdAnnee] = useState<number | null>(null);
  const [dialogVisible, setDialogVisible] = useState(false);
  const [slot, setSlot] = useState<SlotForm>(EMPTY_SLOT);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const endpoint = useMemo(() => {
    if (idClasse && idAnnee) return `/timetable?id_classe=${idClasse}&id_annee=${idAnnee}`;
    return '/timetable';
  }, [idClasse, idAnnee]);

  const { data, isLoading, error: loadError, reload } = useApiGet<TimetableData>(endpoint, [endpoint]);
  const canManage = user?.droit && ['SupAdmin', 'Admin', 'Gestionnaire'].includes(user.droit);

  async function handleDeleteSlot(id: number) {
    try {
      await api.delete(`/timetable/${id}`);
      reload();
    } catch {
      // reload will simply show the slot still present if deletion failed
    }
  }

  function openNewSlotDialog() {
    setSlot(EMPTY_SLOT);
    setError(null);
    setDialogVisible(true);
  }

  async function handleSaveSlot() {
    if (!idClasse || !idAnnee || !slot.id_matiere || !slot.heure_debut || !slot.heure_fin) {
      setError('Tous les champs sont requis.');
      return;
    }
    setIsSubmitting(true);
    setError(null);
    try {
      await api.post('/timetable', {
        id_classe: idClasse,
        id_annee_scolaire: idAnnee,
        id_matiere: slot.id_matiere,
        id_enseignant: slot.id_enseignant,
        jour: slot.jour,
        heure_debut: slot.heure_debut,
        heure_fin: slot.heure_fin,
      });
      setDialogVisible(false);
      reload();
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible d’ajouter ce créneau.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  const classeOptions = (data?.classes ?? []).map((c) => ({ value: c.id_classe, label: c.nom_classe }));
  const anneeOptions = (data?.annees ?? []).map((a) => ({ value: a.id_anneeScolaire, label: a.annee }));
  const matiereOptions = (data?.lignes_classe ?? []).map((l) => ({ value: l.id_matiere, label: l.matiere?.nom_matiere ?? `Matière ${l.id_matiere}` }));
  const enseignantOptions = (data?.lignes_classe ?? [])
    .filter((l) => l.enseignant)
    .map((l) => ({ value: l.enseignant!.id_enseignant, label: l.enseignant!.nom_prenom_enseignant }));

  return (
    <>
      <ScrollView contentContainerStyle={styles.content}>
        <SelectField label="Classe" value={idClasse} options={classeOptions} onChange={(v) => setIdClasse(v as number)} />
        <SelectField label="Année scolaire" value={idAnnee} options={anneeOptions} onChange={(v) => setIdAnnee(v as number)} />

        {isLoading ? (
          <ActivityIndicator style={styles.spinner} size="large" />
        ) : loadError ? (
          <Text style={styles.error}>{loadError}</Text>
        ) : !idClasse || !idAnnee ? (
          <Text style={styles.empty}>Choisissez une classe et une année pour voir l'emploi du temps.</Text>
        ) : (
          JOURS.map((jour) => {
            const courses = (data?.timetable?.[jour] ?? []).slice().sort((a, b) => a.heure_debut.localeCompare(b.heure_debut));
            return (
              <View key={jour} style={styles.dayBlock}>
                <Text style={styles.dayTitle}>{jour}</Text>
                {courses.length === 0 ? (
                  <Text style={styles.emptyDay}>Aucun cours</Text>
                ) : (
                  courses.map((course) => (
                    <View key={course.id} style={styles.courseRow}>
                      <View style={styles.courseInfo}>
                        <Text style={styles.courseTime}>
                          {course.heure_debut} – {course.heure_fin}
                        </Text>
                        <Text style={styles.courseSubject}>{course.matiere?.nom_matiere ?? '—'}</Text>
                        <Text style={styles.meta}>{course.enseignant?.nom_prenom_enseignant ?? 'Sans enseignant'}</Text>
                      </View>
                      {canManage ? <IconButton icon="delete-outline" onPress={() => handleDeleteSlot(course.id)} /> : null}
                    </View>
                  ))
                )}
              </View>
            );
          })
        )}
      </ScrollView>

      {canManage && idClasse && idAnnee ? <FAB icon="plus" style={styles.fab} onPress={openNewSlotDialog} /> : null}

      <Portal>
        <Dialog visible={dialogVisible} onDismiss={() => setDialogVisible(false)}>
          <Dialog.Title>Nouveau créneau</Dialog.Title>
          <Dialog.Content>
            <SelectField
              label="Jour"
              value={slot.jour}
              options={JOURS.map((j) => ({ value: j, label: j }))}
              onChange={(v) => setSlot((prev) => ({ ...prev, jour: v as string }))}
            />
            <SelectField
              label="Matière"
              value={slot.id_matiere}
              options={matiereOptions}
              onChange={(v) => setSlot((prev) => ({ ...prev, id_matiere: v as number }))}
            />
            <SelectField
              label="Enseignant (optionnel)"
              value={slot.id_enseignant}
              options={enseignantOptions}
              onChange={(v) => setSlot((prev) => ({ ...prev, id_enseignant: v as number }))}
            />
            <TextInput
              mode="outlined"
              label="Heure de début (HH:MM)"
              value={slot.heure_debut}
              onChangeText={(v) => setSlot((prev) => ({ ...prev, heure_debut: v }))}
              placeholder="08:00"
              style={styles.input}
            />
            <TextInput
              mode="outlined"
              label="Heure de fin (HH:MM)"
              value={slot.heure_fin}
              onChangeText={(v) => setSlot((prev) => ({ ...prev, heure_fin: v }))}
              placeholder="09:00"
              style={styles.input}
            />
            {error ? <Text style={styles.error}>{error}</Text> : null}
          </Dialog.Content>
          <Dialog.Actions>
            <Button onPress={() => setDialogVisible(false)}>Annuler</Button>
            <Button loading={isSubmitting} onPress={handleSaveSlot}>
              Ajouter
            </Button>
          </Dialog.Actions>
        </Dialog>
      </Portal>
    </>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20, paddingBottom: 80 },
  input: { marginBottom: 8 },
  spinner: { marginTop: 40 },
  empty: { textAlign: 'center', opacity: 0.6, marginTop: 24 },
  emptyDay: { opacity: 0.5, fontStyle: 'italic' },
  error: { color: '#d33', marginBottom: 8 },
  dayBlock: { marginBottom: 20 },
  dayTitle: { fontWeight: '700', fontSize: 16, marginBottom: 8 },
  courseRow: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 10,
    padding: 10,
    marginBottom: 8,
  },
  courseInfo: { flex: 1 },
  courseTime: { fontWeight: '600' },
  courseSubject: { marginTop: 2 },
  meta: { opacity: 0.6, marginTop: 2 },
  fab: { position: 'absolute', right: 16, bottom: 16 },
});
