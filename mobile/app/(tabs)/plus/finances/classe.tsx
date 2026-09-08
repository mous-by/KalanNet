import { useMemo, useState } from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Checkbox, Chip, Text, TextInput } from 'react-native-paper';

import DateField from '@/components/DateField';
import OfflineBanner from '@/components/OfflineBanner';
import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SubmitButton from '@/components/SubmitButton';
import { useOffline } from '@/context/OfflineContext';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';
import { AnneeScolaire, Classe, Trimestre } from '@/types/api';

interface ParentPayeur {
  id_parent: number;
  nom_prenom_parent: string;
  telephone_parent: string | null;
}

interface PaymentRow {
  eleve: { id_eleve: number; prenom_eleve: string; nom_eleve: string };
  id_planification: number;
  motif: string;
  montant_total: number;
  montant_deja_paye: number;
  reste_a_payer: number;
  parents: ParentPayeur[];
}

interface PaiementsContext {
  classes: Classe[];
  annees: AnneeScolaire[];
  trimestres: Trimestre[];
  caisse: { id_caisse: number } | null;
  is_public_school: boolean;
  rows: PaymentRow[] | null;
}

interface RowEntry {
  selected: boolean;
  motif: string;
  montant: string;
  parentId: number | null;
  autreNom: string;
  autreTelephone: string;
}

const TYPE_TABS_PRIVATE = [
  { value: '', label: 'Tous' },
  { value: 'trimestriel', label: 'Trimestriel' },
  { value: 'mensuel', label: 'Mensuel' },
  { value: 'annuel', label: 'Annuel' },
];

const TYPE_TABS_PUBLIC = [
  { value: '', label: 'Tous' },
  { value: 'cooperative', label: 'Coopérative' },
];

export default function PaiementClasseScreen() {
  const { isOnline, enqueueAction } = useOffline();
  const [idClasse, setIdClasse] = useState<number | null>(null);
  const [idAnnee, setIdAnnee] = useState<number | null>(null);
  const [idTrimestre, setIdTrimestre] = useState<number | null>(null);
  const [typePlanification, setTypePlanification] = useState('');
  const [date, setDate] = useState<string | null>(new Date().toISOString().slice(0, 10));
  const [entries, setEntries] = useState<Record<number, RowEntry>>({});
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const endpoint = useMemo(() => {
    const params = new URLSearchParams();
    if (idClasse) params.set('id_classe', String(idClasse));
    if (idAnnee) params.set('id_annee', String(idAnnee));
    if (typePlanification) params.set('type_planification', typePlanification);
    const query = params.toString();
    return query ? `/finances/paiements?${query}` : '/finances/paiements';
  }, [idClasse, idAnnee, typePlanification]);

  const { data, isLoading, error: loadError } = useApiGet<PaiementsContext>(endpoint, [endpoint], { cacheKey: endpoint });

  const classeOptions = (data?.classes ?? []).map((c) => ({ value: c.id_classe, label: c.nom_classe }));
  const anneeOptions = (data?.annees ?? []).map((a) => ({ value: a.id_anneeScolaire, label: a.annee }));
  const trimestreOptions = (data?.trimestres ?? []).map((t) => ({ value: t.id_trimestre, label: t.nom_trimestre ?? `Trimestre ${t.id_trimestre}` }));
  const typeTabs = data?.is_public_school ? TYPE_TABS_PUBLIC : TYPE_TABS_PRIVATE;

  function entryFor(row: PaymentRow): RowEntry {
    if (entries[row.eleve.id_eleve]) return entries[row.eleve.id_eleve];
    return {
      selected: false,
      motif: row.motif,
      montant: String(row.reste_a_payer),
      parentId: null,
      autreNom: '',
      autreTelephone: '',
    };
  }

  function updateEntry(idEleve: number, row: PaymentRow, patch: Partial<RowEntry>) {
    setEntries((prev) => ({ ...prev, [idEleve]: { ...entryFor(row), ...patch } }));
  }

  async function handleSubmit() {
    if (!idClasse || !idAnnee || !idTrimestre || !date || !data?.rows) {
      setError('Choisissez une classe, une année, un trimestre et une date.');
      return;
    }
    const rows = data.rows
      .filter((row) => entryFor(row).selected)
      .map((row) => {
        const entry = entryFor(row);
        return {
          id_eleve: row.eleve.id_eleve,
          id_planification: row.id_planification,
          motif: entry.motif,
          montant_recu: Number(entry.montant) || 0,
          parent_id: entry.parentId,
          autre_personne_nom: entry.parentId ? null : entry.autreNom || null,
          autre_personne_telephone: entry.parentId ? null : entry.autreTelephone || null,
        };
      });

    if (rows.length === 0) {
      setError('Sélectionnez au moins un élève à payer.');
      return;
    }

    const payload = {
      id_classe: idClasse,
      id_annee: idAnnee,
      id_trimestre: idTrimestre,
      date_paiement: date,
      type_planification: typePlanification || null,
      rows,
    };

    setError(null);
    setSuccess(null);
    setIsSubmitting(true);
    try {
      if (!isOnline) {
        const classeLabel = data?.classes.find((c) => c.id_classe === idClasse)?.nom_classe ?? '';
        await enqueueAction({
          kind: 'paiement_classe',
          label: `${classeLabel} · ${rows.length} élève(s) · ${date}`,
          endpoint: '/finances/paiements/groupes',
          method: 'post',
          payload,
        });
        setSuccess(`${rows.length} paiement(s) enregistré(s) hors ligne. Ils seront envoyés au retour du réseau.`);
        setEntries({});
        return;
      }
      const { data: response } = await api.post('/finances/paiements/groupes', payload);
      setSuccess(`${response.created} paiement(s) enregistré(s).${response.errors?.length ? ` ${response.errors.length} ligne(s) ignorée(s).` : ''}`);
      setEntries({});
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible d’enregistrer les paiements.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <OfflineBanner />
      <SelectField label={requiredLabel('Classe')} value={idClasse} options={classeOptions} onChange={(v) => setIdClasse(v as number)} />
      <SelectField label={requiredLabel('Année scolaire')} value={idAnnee} options={anneeOptions} onChange={(v) => setIdAnnee(v as number)} />
      <SelectField label={requiredLabel('Trimestre')} value={idTrimestre} options={trimestreOptions} onChange={(v) => setIdTrimestre(v as number)} />
      <DateField label={requiredLabel('Date de paiement')} value={date} onChange={setDate} />

      <View style={styles.tabsRow}>
        {typeTabs.map((tab) => (
          <Chip key={tab.value} selected={typePlanification === tab.value} onPress={() => setTypePlanification(tab.value)} style={styles.tabChip}>
            {tab.label}
          </Chip>
        ))}
      </View>

      {!data?.caisse ? <Text style={styles.warning}>Vous devez activer une caisse avant tout encaissement.</Text> : null}

      {isLoading ? (
        <ActivityIndicator style={styles.spinner} size="large" />
      ) : loadError ? (
        <Text style={styles.error}>{loadError}</Text>
      ) : !idClasse || !idAnnee ? (
        <Text style={styles.empty}>Choisissez une classe et une année pour afficher les élèves.</Text>
      ) : (data?.rows ?? []).length === 0 ? (
        <Text style={styles.empty}>Aucun élève à afficher pour cette sélection.</Text>
      ) : (
        <>
          {data!.rows!.map((row) => {
            const entry = entryFor(row);
            const parentOptions = [
              { value: 0, label: 'Autre personne' },
              ...row.parents.map((p) => ({ value: p.id_parent, label: p.nom_prenom_parent })),
            ];
            return (
              <View key={row.eleve.id_eleve} style={styles.studentRow}>
                <View style={styles.studentHeader}>
                  <Checkbox
                    status={entry.selected ? 'checked' : 'unchecked'}
                    onPress={() => updateEntry(row.eleve.id_eleve, row, { selected: !entry.selected })}
                  />
                  <View style={styles.studentHeaderText}>
                    <Text style={styles.studentName}>
                      {row.eleve.prenom_eleve} {row.eleve.nom_eleve}
                    </Text>
                    <Text style={styles.studentMeta}>
                      Total {row.montant_total.toLocaleString('fr-FR')} · Reste {row.reste_a_payer.toLocaleString('fr-FR')} FCFA
                    </Text>
                  </View>
                </View>

                {entry.selected ? (
                  <View style={styles.studentBody}>
                    <TextInput mode="outlined" label="Motif" value={entry.motif} onChangeText={(v) => updateEntry(row.eleve.id_eleve, row, { motif: v })} style={styles.input} />
                    <TextInput
                      mode="outlined"
                      label="Montant à payer"
                      keyboardType="numeric"
                      value={entry.montant}
                      onChangeText={(v) => updateEntry(row.eleve.id_eleve, row, { montant: v })}
                      style={styles.input}
                    />
                    <SelectField
                      label="Parent payeur"
                      value={entry.parentId ?? 0}
                      options={parentOptions}
                      onChange={(v) => updateEntry(row.eleve.id_eleve, row, { parentId: (v as number) || null })}
                    />
                    {!entry.parentId ? (
                      <>
                        <TextInput
                          mode="outlined"
                          label="Nom du payeur"
                          value={entry.autreNom}
                          onChangeText={(v) => updateEntry(row.eleve.id_eleve, row, { autreNom: v })}
                          style={styles.input}
                        />
                        <TextInput
                          mode="outlined"
                          label="Téléphone du payeur"
                          keyboardType="phone-pad"
                          value={entry.autreTelephone}
                          onChangeText={(v) => updateEntry(row.eleve.id_eleve, row, { autreTelephone: v })}
                          style={styles.input}
                        />
                      </>
                    ) : null}
                  </View>
                ) : null}
              </View>
            );
          })}

          {error ? <Text style={styles.error}>{error}</Text> : null}
          {success ? <Text style={styles.success}>{success}</Text> : null}

          <SubmitButton label="Valider les paiements" onPress={handleSubmit} loading={isSubmitting} disabled={!data?.caisse} />
        </>
      )}
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
  input: { marginBottom: 12 },
  spinner: { marginTop: 40 },
  empty: { textAlign: 'center', opacity: 0.6, marginTop: 24 },
  error: { color: '#d33', marginBottom: 12 },
  success: { color: '#1f8a4c', marginBottom: 12 },
  warning: { color: '#b8860b', marginBottom: 12 },
  tabsRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginBottom: 16 },
  tabChip: { marginRight: 4 },
  studentRow: {
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 12,
    padding: 10,
    marginBottom: 12,
  },
  studentHeader: {
    flexDirection: 'row',
    alignItems: 'center',
  },
  studentHeaderText: {
    flex: 1,
  },
  studentName: { fontWeight: '600' },
  studentMeta: { opacity: 0.6, fontSize: 12, marginTop: 2 },
  studentBody: {
    marginTop: 8,
    paddingLeft: 8,
  },
});
