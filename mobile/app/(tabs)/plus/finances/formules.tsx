import { useCallback, useEffect, useState } from 'react';
import { router, useFocusEffect } from 'expo-router';
import { Alert, ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Dialog, FAB, Portal, Text, TextInput } from 'react-native-paper';

import DateField from '@/components/DateField';
import SelectField from '@/components/SelectField';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
import { formatMontant } from '@/lib/currency';
import { Formule, LIBELLES_MOTIF, PlanificationsReponse, Tranche } from '@/lib/formules';
import { useApiGet } from '@/lib/useApi';

const libelleMotif = (motif: string) => LIBELLES_MOTIF[motif] ?? motif.charAt(0).toUpperCase() + motif.slice(1);
const jour = (date: string | null) => (date ? String(date).slice(0, 10) : '');

// Formules de paiement (frais scolaires) d'une classe pour une année : même
// écran que Finances > Planifications sur le web.
export default function FormulesScreen() {
  const { user } = useAuth();
  const [idClasse, setIdClasse] = useState<number | null>(null);
  const [idAnnee, setIdAnnee] = useState<number | null>(null);
  const endpoint = `/finances/planifications${idClasse && idAnnee ? `?id_classe=${idClasse}&id_annee=${idAnnee}` : ''}`;
  const { data, isLoading, error, reload } = useApiGet<PlanificationsReponse>(endpoint, [endpoint]);
  const [message, setMessage] = useState('');
  const [erreurAction, setErreurAction] = useState<string | null>(null);

  // Édition : dates, montant (ou tranches).
  const [edition, setEdition] = useState<Formule | null>(null);
  const [debut, setDebut] = useState('');
  const [fin, setFin] = useState('');
  const [montant, setMontant] = useState('');
  const [tranches, setTranches] = useState<Tranche[]>([]);
  const [enregistrement, setEnregistrement] = useState(false);

  useEffect(() => {
    if (data && idAnnee === null) setIdAnnee(data.annee_courante ?? data.annees[0]?.id ?? null);
  }, [data, idAnnee]);

  useFocusEffect(useCallback(() => { reload(); }, [reload]));

  function ouvrirEdition(formule: Formule) {
    setErreurAction(null);
    setEdition(formule);
    setDebut(jour(formule.date_debut));
    setFin(jour(formule.date_fin));
    setMontant(String(formule.montant));
    setTranches(formule.tranches.map((t) => ({ ...t })));
  }

  async function enregistrerEdition() {
    if (!edition) return;
    setEnregistrement(true);
    setErreurAction(null);
    try {
      const corps = edition.tranches.length
        ? { date_debut: debut, tranche_montant: tranches.map((t) => t.montant), tranche_date: tranches.map((t) => t.date_limite) }
        : { date_debut: debut, date_fin: fin, montant_planification: Number(montant) };
      const { data: reponse } = await api.put<{ message: string }>(`/finances/planifications/${edition.id}`, corps);
      setEdition(null);
      setMessage(reponse.message);
      reload();
    } catch (err) {
      setErreurAction(apiErrorMessage(err, 'Impossible de modifier cette formule.'));
    } finally {
      setEnregistrement(false);
    }
  }

  function supprimer(formule: Formule) {
    Alert.alert('Supprimer la formule', `La formule « ${libelleMotif(formule.motif)} » sera supprimée.`, [
      { text: 'Annuler', style: 'cancel' },
      {
        text: 'Supprimer',
        style: 'destructive',
        onPress: async () => {
          setErreurAction(null);
          try {
            const { data: reponse } = await api.delete<{ message: string }>(`/finances/planifications/${formule.id}`);
            setMessage(reponse.message);
            reload();
          } catch (err) {
            setErreurAction(apiErrorMessage(err));
          }
        },
      },
    ]);
  }

  const peut = data?.peut;

  return (
    <View style={styles.page}>
      <ScrollView contentContainerStyle={styles.contenu}>
        <SelectField
          label="Classe"
          value={idClasse}
          options={(data?.classes ?? []).map((c) => ({ value: c.id_classe, label: c.nom_classe }))}
          onChange={(v) => setIdClasse(Number(v))}
        />
        <SelectField
          label="Année scolaire"
          value={idAnnee}
          options={(data?.annees ?? []).map((a) => ({ value: a.id, label: a.annee }))}
          onChange={(v) => setIdAnnee(Number(v))}
        />

        {isLoading && !data ? <ActivityIndicator style={styles.chargement} /> : null}
        {error ? <Text style={styles.erreur}>{error}</Text> : null}
        {erreurAction ? <Text style={styles.erreur}>{erreurAction}</Text> : null}

        {!idClasse ? (
          <Text style={styles.vide}>Choisissez une classe pour voir ses formules de paiement.</Text>
        ) : data && data.planifications.length === 0 ? (
          <Text style={styles.vide}>Aucune formule pour cette classe et cette année.</Text>
        ) : null}

        {data?.planifications.map((formule) => (
          <View key={formule.id} style={styles.carte}>
            <View style={styles.ligne}>
              <Text style={styles.titre}>{libelleMotif(formule.motif)}</Text>
              <Text style={styles.montant}>{formatMontant(formule.montant, user)}</Text>
            </View>
            <Text style={styles.meta}>
              Du {jour(formule.date_debut)} au {jour(formule.date_fin)} · {formule.eleves_lies} élève(s) lié(s)
            </Text>
            {formule.tranches.map((t) => (
              <Text key={t.libelle} style={styles.tranche}>
                • {t.libelle} : {formatMontant(t.montant, user)} avant le {t.date_limite}
              </Text>
            ))}
            <View style={styles.actions}>
              {peut?.modifier ? (
                <Button compact icon="pencil" onPress={() => ouvrirEdition(formule)}>
                  Modifier
                </Button>
              ) : null}
              {peut?.supprimer && formule.eleves_lies === 0 ? (
                <Button compact icon="delete" textColor="#d33" onPress={() => supprimer(formule)}>
                  Supprimer
                </Button>
              ) : null}
            </View>
          </View>
        ))}
      </ScrollView>

      {peut?.creer ? (
        <FAB
          icon="plus"
          label="Nouvelle formule"
          style={styles.fab}
          onPress={() => router.push({ pathname: '/plus/finances/formule-nouvelle', params: { classe: idClasse ? String(idClasse) : '', annee: idAnnee ? String(idAnnee) : '' } })}
        />
      ) : null}

      <Portal>
        <Dialog visible={!!edition} onDismiss={() => setEdition(null)}>
          <Dialog.Title>Modifier : {edition ? libelleMotif(edition.motif) : ''}</Dialog.Title>
          <Dialog.ScrollArea>
            <ScrollView contentContainerStyle={styles.dialogue}>
              <DateField label="Date de début" value={debut} onChange={setDebut} />
              {edition?.tranches.length ? (
                tranches.map((t, i) => (
                  <View key={t.libelle} style={styles.trancheEdition}>
                    <Text style={styles.trancheTitre}>{t.libelle}</Text>
                    <TextInput
                      mode="outlined"
                      label="Montant"
                      keyboardType="numeric"
                      value={String(t.montant)}
                      onChangeText={(v) => setTranches((liste) => liste.map((x, j) => (j === i ? { ...x, montant: Number(v.replace(/\D/g, '')) || 0 } : x)))}
                    />
                    <DateField label="Date limite" value={t.date_limite} onChange={(v) => setTranches((liste) => liste.map((x, j) => (j === i ? { ...x, date_limite: v } : x)))} />
                  </View>
                ))
              ) : (
                <>
                  <DateField label="Date de fin" value={fin} onChange={setFin} />
                  <TextInput mode="outlined" label="Montant" keyboardType="numeric" value={montant} onChangeText={(v) => setMontant(v.replace(/\D/g, ''))} />
                </>
              )}
              {erreurAction && edition ? <Text style={styles.erreur}>{erreurAction}</Text> : null}
            </ScrollView>
          </Dialog.ScrollArea>
          <Dialog.Actions>
            <Button onPress={() => setEdition(null)}>Annuler</Button>
            <Button mode="contained" loading={enregistrement} onPress={enregistrerEdition}>
              Enregistrer
            </Button>
          </Dialog.Actions>
        </Dialog>
      </Portal>

      <SuccessSnackbar visible={!!message} message={message} onDismiss={() => setMessage('')} />
    </View>
  );
}

const styles = StyleSheet.create({
  page: { flex: 1 },
  contenu: { padding: 16, paddingBottom: 96, gap: 4 },
  chargement: { marginTop: 24 },
  vide: { opacity: 0.6, marginTop: 16, textAlign: 'center' },
  erreur: { color: '#b3261e', marginVertical: 8 },
  carte: { borderWidth: 1, borderColor: 'rgba(128,128,128,0.25)', borderRadius: 12, padding: 14, marginTop: 10 },
  ligne: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
  titre: { fontSize: 16, fontWeight: '600' },
  montant: { fontWeight: '700', color: '#1f8a4c' },
  meta: { opacity: 0.6, marginTop: 4 },
  tranche: { marginTop: 4, fontSize: 13 },
  actions: { flexDirection: 'row', justifyContent: 'flex-end', marginTop: 6 },
  fab: { position: 'absolute', right: 16, bottom: 16 },
  dialogue: { paddingVertical: 8, gap: 8 },
  trancheEdition: { gap: 6, marginTop: 6 },
  trancheTitre: { fontWeight: '600' },
});
