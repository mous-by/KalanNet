import { useState } from 'react';
import { router } from 'expo-router';
import * as ImagePicker from 'expo-image-picker';
import * as WebBrowser from 'expo-web-browser';
import { ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Dialog, Portal, Text, TextInput } from 'react-native-paper';

import requiredLabel from '@/components/RequiredLabel';
import SelectField from '@/components/SelectField';
import SuccessSnackbar from '@/components/SuccessSnackbar';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
import { useApiGet } from '@/lib/useApi';

const PROVIDERS = [
  { value: 'orange_money', label: 'Orange Money' },
  { value: 'mobile_money', label: 'Mobile Money' },
  { value: 'mobicash', label: 'Mobicash' },
  { value: 'wave', label: 'Wave' },
];

interface Offre {
  id: number;
  nom: string;
  montant: number;
  montant_effectif?: number;
  devise: string;
  duree_jours: number;
}

interface AbonnementInfo {
  statut: string;
  fin_at: string | null;
  offre?: Offre;
}

interface Paiement {
  id: number;
  reference: string;
  statut: string;
  montant: number;
  offre?: Offre;
}

interface AbonnementData {
  offres: Offre[];
  abonnement: AbonnementInfo | null;
  paiements: Paiement[];
  can_review: boolean;
  admin_paiements: Paiement[];
  // Numéros/canaux effectifs — ceux du revendeur de l'école si elle en a un,
  // sinon ceux par défaut de la plateforme (voir AbonnementPaymentService).
  manual_modes: Record<string, string>;
  manual_numbers: { orange_wave: string; mobicash: string };
}

export default function AbonnementScreen() {
  const { user } = useAuth();
  const { data, isLoading, error, reload } = useApiGet<AbonnementData>('/abonnements');

  const [onlineOffre, setOnlineOffre] = useState<Offre | null>(null);
  const [provider, setProvider] = useState<string | null>(null);
  const [numeroPayeur, setNumeroPayeur] = useState('');
  const [onlineError, setOnlineError] = useState<string | null>(null);
  const [isPaying, setIsPaying] = useState(false);

  const [manualOffre, setManualOffre] = useState<Offre | null>(null);
  const [modePaiement, setModePaiement] = useState<string | null>(null);
  const [transactionRef, setTransactionRef] = useState('');
  const [ownerNote, setOwnerNote] = useState('');
  const [receiptUri, setReceiptUri] = useState<string | null>(null);
  const [manualError, setManualError] = useState<string | null>(null);
  const [isSubmittingManual, setIsSubmittingManual] = useState(false);
  const [successVisible, setSuccessVisible] = useState(false);
  const [successMessage, setSuccessMessage] = useState('');

  async function handleOnlinePay() {
    if (!onlineOffre || !provider) {
      setOnlineError('Choisissez un moyen de paiement.');
      return;
    }
    setIsPaying(true);
    setOnlineError(null);
    try {
      const { data: response } = await api.post<{ checkout_url: string | null }>('/abonnements/payer', {
        offre_id: onlineOffre.id,
        fournisseur: provider,
        numero_payeur: numeroPayeur || undefined,
      });
      if (response.checkout_url) {
        await WebBrowser.openBrowserAsync(response.checkout_url);
      }
      setOnlineOffre(null);
      setSuccessMessage('Paiement initié avec succès.');
      setSuccessVisible(true);
      reload();
    } catch (err) {
      setOnlineError(apiErrorMessage(err, 'Paiement impossible.'));
    } finally {
      setIsPaying(false);
    }
  }

  async function pickReceipt() {
    const permission = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (!permission.granted) return;
    const result = await ImagePicker.launchImageLibraryAsync({ mediaTypes: ['images'], quality: 0.8 });
    if (!result.canceled && result.assets[0]) {
      setReceiptUri(result.assets[0].uri);
    }
  }

  async function handleManualSubmit() {
    if (!manualOffre || !modePaiement || !receiptUri) {
      setManualError('Choisissez le mode de paiement et une preuve de paiement.');
      return;
    }
    setManualError(null);
    setIsSubmittingManual(true);
    try {
      const form = new FormData();
      form.append('offre_id', String(manualOffre.id));
      form.append('mode_paiement', modePaiement);
      if (transactionRef) form.append('transaction_ref', transactionRef);
      if (ownerNote) form.append('owner_note', ownerNote);
      form.append('receipt', { uri: receiptUri, name: 'receipt.jpg', type: 'image/jpeg' } as unknown as Blob);

      await api.post('/abonnements/manuel', form, { headers: { 'Content-Type': 'multipart/form-data' } });
      setManualOffre(null);
      setReceiptUri(null);
      setTransactionRef('');
      setOwnerNote('');
      setSuccessMessage('Preuve de paiement envoyée avec succès.');
      setSuccessVisible(true);
      reload();
    } catch (err) {
      setManualError(apiErrorMessage(err, 'Envoi impossible.'));
    } finally {
      setIsSubmittingManual(false);
    }
  }

  async function handleReview(paiementId: number, approve: boolean) {
    try {
      await api.post(`/abonnements/paiements/${paiementId}/${approve ? 'approuver' : 'rejeter'}`);
      setSuccessMessage(approve ? 'Paiement approuvé avec succès.' : 'Paiement rejeté avec succès.');
      setSuccessVisible(true);
      reload();
    } catch {
      // the list simply won't update if this fails; user can retry
    }
  }

  if (isLoading) return <ActivityIndicator style={styles.spinner} size="large" />;
  if (error || !data) return <Text style={styles.error}>{error ?? 'Impossible de charger l’abonnement.'}</Text>;

  return (
    <ScrollView contentContainerStyle={styles.content}>
      <View style={styles.statusCard}>
        <Text style={styles.statusLabel}>Abonnement actuel</Text>
        <Text style={styles.statusValue}>{data.abonnement?.offre?.nom ?? 'Aucun abonnement actif'}</Text>
        {data.abonnement?.fin_at ? <Text style={styles.meta}>Expire le {data.abonnement.fin_at}</Text> : null}
        <Text style={styles.meta}>Statut : {data.abonnement?.statut ?? '—'}</Text>
      </View>

      {user?.droit === 'SupAdmin' ? (
        <Button mode="outlined" onPress={() => router.push('/plus/abonnement/offres')} style={styles.input}>
          Gérer les offres
        </Button>
      ) : null}

      <Text style={styles.sectionTitle}>Offres disponibles</Text>
      {data.offres.map((offre) => (
        <View key={offre.id} style={styles.offreRow}>
          <View style={styles.offreInfo}>
            <Text style={styles.offreName}>{offre.nom}</Text>
            <Text style={styles.meta}>
              {Number(offre.montant_effectif ?? offre.montant).toLocaleString('fr-FR')} {offre.devise} · {offre.duree_jours} jours
            </Text>
          </View>
          <View style={styles.offreActions}>
            <Button compact mode="outlined" onPress={() => setOnlineOffre(offre)}>
              Payer en ligne
            </Button>
            <Button compact mode="outlined" onPress={() => setManualOffre(offre)}>
              Paiement manuel
            </Button>
          </View>
        </View>
      ))}

      {data.can_review && data.admin_paiements.length > 0 ? (
        <>
          <Text style={styles.sectionTitle}>Paiements à valider</Text>
          {data.admin_paiements.map((paiement) => (
            <View key={paiement.id} style={styles.offreRow}>
              <View style={styles.offreInfo}>
                <Text style={styles.offreName}>{paiement.reference}</Text>
                <Text style={styles.meta}>
                  {paiement.offre?.nom ?? ''} · {Number(paiement.montant).toLocaleString('fr-FR')} · {paiement.statut}
                </Text>
              </View>
              {paiement.statut === 'en_attente' ? (
                <View style={styles.offreActions}>
                  <Button compact onPress={() => handleReview(paiement.id, true)}>
                    Approuver
                  </Button>
                  <Button compact textColor="#d33" onPress={() => handleReview(paiement.id, false)}>
                    Rejeter
                  </Button>
                </View>
              ) : null}
            </View>
          ))}
        </>
      ) : null}

      <Portal>
        <Dialog visible={onlineOffre !== null} onDismiss={() => setOnlineOffre(null)}>
          <Dialog.Title>Payer {onlineOffre?.nom}</Dialog.Title>
          <Dialog.Content>
            <SelectField label={requiredLabel('Fournisseur')} value={provider} options={PROVIDERS} onChange={(v) => setProvider(v as string)} />
            <TextInput mode="outlined" label="Numéro payeur (optionnel)" value={numeroPayeur} onChangeText={setNumeroPayeur} style={styles.input} />
            {onlineError ? <Text style={styles.error}>{onlineError}</Text> : null}
          </Dialog.Content>
          <Dialog.Actions>
            <Button onPress={() => setOnlineOffre(null)}>Annuler</Button>
            <Button loading={isPaying} onPress={handleOnlinePay}>
              Continuer
            </Button>
          </Dialog.Actions>
        </Dialog>

        <Dialog visible={manualOffre !== null} onDismiss={() => setManualOffre(null)}>
          <Dialog.Title>Paiement manuel — {manualOffre?.nom}</Dialog.Title>
          <Dialog.Content>
            <Text style={[styles.meta, styles.input]}>
              Effectuez le dépôt puis joignez la preuve ci-dessous.
            </Text>
            <SelectField
              label={requiredLabel('Mode de paiement')}
              value={modePaiement}
              options={Object.entries(data.manual_modes ?? {}).map(([value, label]) => ({ value, label }))}
              onChange={(v) => setModePaiement(v as string)}
            />
            <TextInput mode="outlined" label="Référence de transaction (optionnel)" value={transactionRef} onChangeText={setTransactionRef} style={styles.input} />
            <TextInput mode="outlined" label="Note (optionnel)" value={ownerNote} onChangeText={setOwnerNote} style={styles.input} />
            <Button mode="outlined" onPress={pickReceipt} style={styles.input}>
              {receiptUri ? 'Changer la preuve de paiement' : 'Choisir une preuve de paiement *'}
            </Button>
            {manualError ? <Text style={styles.error}>{manualError}</Text> : null}
          </Dialog.Content>
          <Dialog.Actions>
            <Button onPress={() => setManualOffre(null)}>Annuler</Button>
            <Button loading={isSubmittingManual} onPress={handleManualSubmit}>
              Envoyer
            </Button>
          </Dialog.Actions>
        </Dialog>
      </Portal>

      <SuccessSnackbar visible={successVisible} message={successMessage} onDismiss={() => setSuccessVisible(false)} />
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  content: { padding: 20 },
  spinner: { marginTop: 40 },
  error: { color: '#d33', textAlign: 'center', marginTop: 12 },
  statusCard: { alignItems: 'center', marginBottom: 24 },
  statusLabel: { opacity: 0.6 },
  statusValue: { fontSize: 20, fontWeight: 'bold', marginTop: 4 },
  meta: { opacity: 0.6, marginTop: 4 },
  sectionTitle: { fontWeight: '600', fontSize: 16, marginTop: 12, marginBottom: 8 },
  offreRow: {
    borderWidth: 1,
    borderColor: 'rgba(128,128,128,0.25)',
    borderRadius: 12,
    padding: 14,
    marginBottom: 10,
  },
  offreInfo: { marginBottom: 8 },
  offreName: { fontWeight: '600' },
  offreActions: { flexDirection: 'row', gap: 8 },
  input: { marginBottom: 8 },
});
