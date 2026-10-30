import { useEffect, useState } from 'react';
import { FlatList, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Text } from 'react-native-paper';

import OfflineBanner from '@/components/OfflineBanner';
import PhotoEleve from '@/components/PhotoEleve';
import SelectField from '@/components/SelectField';
import { useLocale } from '@/context/LocaleContext';
import { useApiGet } from '@/lib/useApi';
import { Classe } from '@/types/api';

type ElevePhoto = {
  id_eleve: number;
  prenom_eleve: string;
  nom_eleve: string;
  matricule: string | null;
  sans_photo: boolean;
  image_url: string | null;
};

/**
 * « Photos de la classe » : on choisit une classe, les élèves sans photo viennent en premier, et on les prend en
 * photo un par un (appareil photo ou galerie). Chaque photo est envoyée aussitôt.
 */
export default function PhotosClasseScreen() {
  const { t } = useLocale();
  const [idClasse, setIdClasse] = useState<number | null>(null);
  const { data, isLoading, error } = useApiGet<{ classes: Pick<Classe, 'id_classe' | 'nom_classe'>[]; eleves: ElevePhoto[] }>(
    `/eleves/photos${idClasse ? `?id_classe=${idClasse}` : ''}`,
    [idClasse]
  );
  const [eleves, setEleves] = useState<ElevePhoto[]>([]);

  useEffect(() => {
    setEleves(data?.eleves ?? []);
  }, [data]);

  const sansPhoto = eleves.filter((e) => e.sans_photo).length;
  const classeOptions = (data?.classes ?? []).map((c) => ({ value: c.id_classe, label: c.nom_classe }));

  function marquer(idEleve: number, imageUrl: string | null) {
    setEleves((prev) => prev.map((e) => (e.id_eleve === idEleve ? { ...e, image_url: imageUrl, sans_photo: !imageUrl } : e)));
  }

  return (
    <FlatList
      contentContainerStyle={styles.content}
      data={eleves}
      keyExtractor={(item) => String(item.id_eleve)}
      ListHeaderComponent={
        <View>
          <OfflineBanner />
          <Text style={styles.intro}>{t('eleves.photos_intro')}</Text>
          <SelectField label={t('eleves.photos_choisir_classe')} value={idClasse} options={classeOptions} onChange={(v) => setIdClasse(v as number)} />
          {isLoading ? <ActivityIndicator style={styles.spinner} /> : null}
          {error ? <Text style={styles.error}>{error}</Text> : null}
          {idClasse && !isLoading && eleves.length > 0 ? (
            <Text style={[styles.counter, sansPhoto ? styles.counterTodo : styles.counterDone]}>
              {sansPhoto ? t('eleves.photos_restantes').replace(':count', String(sansPhoto)).replace(':total', String(eleves.length)) : t('eleves.photos_toutes')}
            </Text>
          ) : null}
          {idClasse && !isLoading && !error && eleves.length === 0 ? <Text style={styles.empty}>{t('eleves.photos_aucun_eleve')}</Text> : null}
        </View>
      }
      renderItem={({ item }) => (
        <View style={styles.row}>
          <PhotoEleve compact idEleve={item.id_eleve} imageUrl={item.image_url} onChange={(url) => marquer(item.id_eleve, url)}>
            <Text style={styles.name} numberOfLines={1}>
              {item.prenom_eleve} {item.nom_eleve}
            </Text>
            <Text style={item.sans_photo ? styles.missing : styles.matricule} numberOfLines={1}>
              {item.sans_photo ? t('eleves.photo_manquante') : (item.matricule ?? '')}
            </Text>
          </PhotoEleve>
        </View>
      )}
    />
  );
}

const styles = StyleSheet.create({
  content: { padding: 16 },
  intro: { color: '#607d8b', marginBottom: 12 },
  spinner: { marginVertical: 16 },
  error: { color: '#d33', marginVertical: 8 },
  counter: { padding: 10, borderRadius: 8, marginVertical: 8, overflow: 'hidden' },
  counterTodo: { backgroundColor: '#fff4e5', color: '#8a5300' },
  counterDone: { backgroundColor: '#e8f5e9', color: '#1b5e20' },
  empty: { color: '#607d8b', marginVertical: 12 },
  row: { paddingVertical: 8, borderBottomWidth: StyleSheet.hairlineWidth, borderBottomColor: '#cfd8dc' },
  name: { fontWeight: '600' },
  matricule: { color: '#607d8b', fontSize: 12 },
  missing: { color: '#b26a00', fontSize: 12 },
});
