import { useMemo, useState } from 'react';
import { useLocalSearchParams } from 'expo-router';
import { FlatList, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Text } from 'react-native-paper';

import SelectField from '@/components/SelectField';
import { useAuth } from '@/context/AuthContext';
import { useLocale } from '@/context/LocaleContext';
import { api, apiErrorMessage } from '@/lib/api';
import { downloadAndShare } from '@/lib/downloadFile';
import { hasPermission } from '@/lib/permissions';
import { useApiGet } from '@/lib/useApi';
import { AnneeScolaire, Trimestre } from '@/types/api';

interface BulletinOptions {
  annees: AnneeScolaire[];
  trimestres: Trimestre[];
  mois_options: Record<string, string>;
}

interface Student {
  id: number;
  nom: string;
  prenom: string;
  matricule: string | null;
  url: string;
}

export default function BulletinsClasseScreen() {
  const { idClasse } = useLocalSearchParams<{ idClasse: string }>();
  const { user } = useAuth();
  const { t } = useLocale();
  const { data: options } = useApiGet<BulletinOptions>(`/bulletins/classes/${idClasse}`, [idClasse]);

  const [idAnnee, setIdAnnee] = useState<number | null>(null);
  const [periodMode, setPeriodMode] = useState<'trimestre' | 'mois'>('trimestre');
  const [idTrimestre, setIdTrimestre] = useState<number | null>(null);
  const [mois, setMois] = useState<number | null>(null);
  const [actionMessage, setActionMessage] = useState<string | null>(null);
  const [downloadingId, setDownloadingId] = useState<number | null>(null);

  const canManage = hasPermission(user, 'bulletins_publication');

  const period = periodMode === 'trimestre' ? idTrimestre : mois;
  const studentsEndpoint = useMemo(() => {
    if (!idAnnee || !period) return null;
    const params = new URLSearchParams({ id_annee: String(idAnnee) });
    if (periodMode === 'trimestre') params.set('id_trimestre', String(period));
    else params.set('mois', String(period));
    return `/bulletins/classes/${idClasse}/students?${params.toString()}`;
  }, [idClasse, idAnnee, period, periodMode]);

  const { data: students, isLoading, error, reload } = useApiGet<Student[]>(studentsEndpoint, [studentsEndpoint]);

  async function handleDownload(student: Student) {
    setDownloadingId(student.id);
    try {
      await downloadAndShare(student.url, `Bulletin_${student.nom}_${student.prenom}.pdf`);
    } catch (err) {
      setActionMessage(apiErrorMessage(err, t('bulletins.download_error')));
    } finally {
      setDownloadingId(null);
    }
  }

  async function handlePublishToggle(publish: boolean) {
    if (!idAnnee || !period) return;
    setActionMessage(null);
    const body: Record<string, number> = { id_annee: idAnnee };
    if (periodMode === 'trimestre') body.id_trimestre = period;
    else body.mois = period;
    try {
      if (publish) {
        await api.post(`/bulletins/classes/${idClasse}/publish`, body);
      } else {
        await api.delete(`/bulletins/classes/${idClasse}/publish`, { data: body });
      }
      setActionMessage(publish ? t('bulletins.published_message') : t('bulletins.unpublished_message'));
    } catch (err) {
      setActionMessage(apiErrorMessage(err, t('bulletins.action_error')));
    }
  }

  const anneeOptions = (options?.annees ?? []).map((a) => ({ value: a.id_anneeScolaire, label: a.annee }));
  const trimestreOptions = (options?.trimestres ?? []).map((tr) => ({ value: tr.id_trimestre, label: t('bulletins.trimestre_option_label').replace(':n', String(tr.id_trimestre)) }));
  const moisOptions = Object.entries(options?.mois_options ?? {}).map(([value, label]) => ({ value: Number(value), label }));

  return (
    <View style={styles.container}>
      <View style={styles.filters}>
        <SelectField label={t('eleves.label_annee')} value={idAnnee} options={anneeOptions} onChange={(v) => setIdAnnee(v as number)} />
        <View style={styles.periodToggle}>
          <Button mode={periodMode === 'trimestre' ? 'contained' : 'outlined'} onPress={() => setPeriodMode('trimestre')} style={styles.toggleButton} compact>
            {t('bulletins.trimestre_toggle')}
          </Button>
          <Button mode={periodMode === 'mois' ? 'contained' : 'outlined'} onPress={() => setPeriodMode('mois')} style={styles.toggleButton} compact>
            {t('bulletins.mois_toggle')}
          </Button>
        </View>
        {periodMode === 'trimestre' ? (
          <SelectField label={t('bulletins.trimestre_toggle')} value={idTrimestre} options={trimestreOptions} onChange={(v) => setIdTrimestre(v as number)} />
        ) : (
          <SelectField label={t('bulletins.mois_toggle')} value={mois} options={moisOptions} onChange={(v) => setMois(v as number)} />
        )}

        {canManage && idAnnee && period ? (
          <View style={styles.publishRow}>
            <Button mode="outlined" onPress={() => handlePublishToggle(true)} style={styles.actionButton}>
              {t('bulletins.publish_button')}
            </Button>
            <Button mode="outlined" textColor="#d33" onPress={() => handlePublishToggle(false)} style={styles.actionButton}>
              {t('bulletins.unpublish_button')}
            </Button>
          </View>
        ) : null}
        {actionMessage ? <Text style={styles.info}>{actionMessage}</Text> : null}
      </View>

      {!idAnnee || !period ? (
        <Text style={styles.empty}>{t('bulletins.choose_year_period')}</Text>
      ) : isLoading ? (
        <ActivityIndicator style={styles.spinner} size="large" />
      ) : error ? (
        <Text style={styles.error}>{error}</Text>
      ) : (
        <FlatList
          data={students ?? []}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={styles.list}
          refreshing={false}
          onRefresh={reload}
          ListEmptyComponent={<Text style={styles.empty}>{t('bulletins.empty_students')}</Text>}
          renderItem={({ item }) => (
            <View style={styles.studentRow}>
              <View style={styles.studentInfo}>
                <Text style={styles.studentName}>
                  {item.prenom} {item.nom}
                </Text>
                <Text style={styles.meta}>{item.matricule ?? '—'}</Text>
              </View>
              <Button mode="outlined" loading={downloadingId === item.id} onPress={() => handleDownload(item)}>
                {t('bulletins.download_button')}
              </Button>
            </View>
          )}
        />
      )}
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1 },
  filters: { padding: 16, paddingBottom: 0 },
  periodToggle: { flexDirection: 'row', gap: 8, marginBottom: 12 },
  toggleButton: { flex: 1 },
  publishRow: { flexDirection: 'row', gap: 10, marginBottom: 12 },
  actionButton: { flex: 1 },
  info: { textAlign: 'center', opacity: 0.7, marginBottom: 8 },
  list: { padding: 16, paddingTop: 0, flexGrow: 1 },
  spinner: { marginTop: 40 },
  empty: { textAlign: 'center', opacity: 0.6, marginTop: 24 },
  error: { color: '#d33', textAlign: 'center', marginTop: 24 },
  studentRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 12,
    padding: 12,
    marginBottom: 10,
  },
  studentInfo: { flex: 1 },
  studentName: { fontWeight: '600' },
  meta: { opacity: 0.6, marginTop: 2 },
});
