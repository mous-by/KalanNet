import { useState } from 'react';
import { Alert, ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Text, TextInput } from 'react-native-paper';

import SelectField from '@/components/SelectField';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';

interface AnnonceTerritoire {
  id_annonce: number | string;
  id?: number;
  titre: string;
  contenu: string;
  public_cible: string;
  date_publication: string | null;
  auteur?: string | null;
  source_label?: string;
}

interface AnnoncesReponse {
  annonces: AnnonceTerritoire[];
  annonces_academie: AnnonceTerritoire[];
  cibles: Record<string, string>;
}

/** Annonces du DAE (à l'académie) ou du DCAP (à son CAP), et annonces reçues de l'académie (DCAP). */
export default function AnnoncesTerritoireScreen() {
  const { user } = useAuth();
  const { data, isLoading, error, reload } = useApiGet<AnnoncesReponse>('/territoire/annonces', []);
  const [titre, setTitre] = useState('');
  const [contenu, setContenu] = useState('');
  const [cible, setCible] = useState<string | null>('tous');
  const [envoi, setEnvoi] = useState(false);
  const [message, setMessage] = useState<string | null>(null);

  async function publier() {
    if (!titre.trim() || !contenu.trim() || !cible) {
      Alert.alert('Annonce', 'Le titre, le message et le public sont obligatoires.');
      return;
    }
    setEnvoi(true);
    try {
      await api.post('/territoire/annonces', { titre: titre.trim(), contenu: contenu.trim(), public_cible: cible });
      setTitre('');
      setContenu('');
      setMessage('Annonce publiée.');
      reload();
    } catch (err) {
      Alert.alert('Annonce', apiErrorMessage(err, "Impossible de publier l'annonce."));
    } finally {
      setEnvoi(false);
    }
  }

  function supprimer(annonce: AnnonceTerritoire) {
    Alert.alert('Supprimer cette annonce ?', annonce.titre, [
      { text: 'Annuler', style: 'cancel' },
      {
        text: 'Supprimer',
        style: 'destructive',
        onPress: async () => {
          try {
            await api.delete(`/territoire/annonces/${annonce.id_annonce}`);
            setMessage('Annonce supprimée.');
            reload();
          } catch (err) {
            Alert.alert('Annonce', apiErrorMessage(err, "Impossible de supprimer l'annonce."));
          }
        },
      },
    ]);
  }

  const cibles = Object.entries(data?.cibles ?? {}).map(([value, label]) => ({ value, label }));

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <Text style={styles.titre}>Publier une annonce {user?.droit === 'DAE' ? "à l'académie" : 'à votre CAP'}</Text>
      <TextInput mode="outlined" label="Titre" value={titre} onChangeText={setTitre} style={styles.champ} />
      <TextInput mode="outlined" label="Message" value={contenu} onChangeText={setContenu} multiline numberOfLines={4} style={styles.champ} />
      <SelectField label="Public" value={cible} options={cibles} onChange={(v) => setCible(v as string)} />
      <Button mode="contained" loading={envoi} disabled={envoi} onPress={publier} style={styles.bouton}>
        Publier
      </Button>

      <Text style={styles.section}>Annonces publiées</Text>
      {isLoading && !data ? <ActivityIndicator /> : null}
      {error ? <Text style={styles.erreur}>{error}</Text> : null}
      {(data?.annonces ?? []).map((a) => (
        <View key={String(a.id_annonce)} style={styles.carte}>
          <Text style={styles.carteTitre}>{a.titre}</Text>
          <Text style={styles.meta}>
            {a.date_publication ?? ''} · {data?.cibles?.[a.public_cible] ?? a.public_cible}
          </Text>
          <Text style={styles.contenu}>{a.contenu}</Text>
          <Button compact textColor="#d33" onPress={() => supprimer(a)} style={styles.supprimer}>
            Supprimer
          </Button>
        </View>
      ))}
      {data && data.annonces.length === 0 ? <Text style={styles.vide}>Aucune annonce publiée.</Text> : null}

      {data?.annonces_academie?.length ? (
        <>
          <Text style={styles.section}>Annonces reçues de l'académie</Text>
          {data.annonces_academie.map((a) => (
            <View key={String(a.id_annonce)} style={styles.carte}>
              <Text style={styles.carteTitre}>{a.titre}</Text>
              <Text style={styles.meta}>{a.date_publication ?? ''}</Text>
              <Text style={styles.contenu}>{a.contenu}</Text>
            </View>
          ))}
        </>
      ) : null}

      <SuccessSnackbar visible={!!message} message={message ?? ''} onDismiss={() => setMessage(null)} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 16 },
  titre: { fontWeight: '700', fontSize: 15, marginBottom: 10 },
  champ: { marginBottom: 10 },
  bouton: { marginTop: 4 },
  section: { fontWeight: '700', fontSize: 15, marginTop: 24, marginBottom: 10 },
  erreur: { color: '#d33' },
  carte: { borderWidth: 1, borderColor: 'rgba(128,128,128,0.25)', borderRadius: 12, padding: 14, marginBottom: 10 },
  carteTitre: { fontWeight: '700' },
  meta: { opacity: 0.6, fontSize: 12, marginTop: 2 },
  contenu: { marginTop: 6 },
  supprimer: { alignSelf: 'flex-end', marginTop: 4 },
  vide: { opacity: 0.6 },
});
