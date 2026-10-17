import { useState } from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';
import { Button, Dialog, Portal, Text } from 'react-native-paper';

import { useLocale } from '@/context/LocaleContext';
import { apiErrorMessage } from '@/lib/api';
import { downloadAndPrint } from '@/lib/downloadFile';

export interface RecuAImprimer {
  id: number;
  libelle: string;
}

/**
 * Après un paiement : proposer d'imprimer le reçu tout de suite (A4 ou ticket
 * thermique), comme sur le site. Plusieurs paiements : un reçu par ligne.
 */
export default function ImpressionRecusDialog({ recus, onClose }: { recus: RecuAImprimer[] | null; onClose: () => void }) {
  const { t } = useLocale();
  const [enCours, setEnCours] = useState<string | null>(null);
  const [erreur, setErreur] = useState<string | null>(null);

  async function imprimer(recu: RecuAImprimer, format: 'recu' | 'thermique') {
    setErreur(null);
    setEnCours(`${recu.id}-${format}`);
    try {
      await downloadAndPrint(`/finances/paiements/${recu.id}/${format}`, `${format === 'recu' ? 'Recu' : 'Ticket'}_${recu.id}.pdf`);
    } catch (err) {
      setErreur(apiErrorMessage(err, t('recu.erreur')));
    } finally {
      setEnCours(null);
    }
  }

  const plusieurs = (recus?.length ?? 0) > 1;

  return (
    <Portal>
      <Dialog visible={!!recus?.length} onDismiss={onClose}>
        <Dialog.Icon icon="receipt-text-check-outline" />
        <Dialog.Title style={styles.titre}>{t('recu.titre')}</Dialog.Title>
        <Dialog.Content>
          <Text style={styles.question}>{plusieurs ? t('recu.questions') : t('recu.question')}</Text>
          <ScrollView style={plusieurs ? styles.liste : undefined}>
            {(recus ?? []).map((recu) => (
              <View key={recu.id} style={styles.ligne}>
                {plusieurs ? <Text style={styles.libelle} numberOfLines={1}>{recu.libelle}</Text> : null}
                <View style={styles.boutons}>
                  <Button mode="contained" icon="printer" compact onPress={() => imprimer(recu, 'recu')} loading={enCours === `${recu.id}-recu`} disabled={enCours !== null}>
                    {t('recu.a4')}
                  </Button>
                  <Button mode="outlined" icon="printer-pos" compact onPress={() => imprimer(recu, 'thermique')} loading={enCours === `${recu.id}-thermique`} disabled={enCours !== null}>
                    {t('recu.thermique')}
                  </Button>
                </View>
              </View>
            ))}
          </ScrollView>
          {erreur ? <Text style={styles.erreur}>{erreur}</Text> : null}
        </Dialog.Content>
        <Dialog.Actions>
          <Button onPress={onClose} disabled={enCours !== null}>{t('recu.plus_tard')}</Button>
        </Dialog.Actions>
      </Dialog>
    </Portal>
  );
}

const styles = StyleSheet.create({
  titre: { textAlign: 'center' },
  question: { marginBottom: 12, textAlign: 'center' },
  liste: { maxHeight: 320 },
  ligne: { marginBottom: 10 },
  libelle: { fontWeight: '600', marginBottom: 4 },
  boutons: { flexDirection: 'row', gap: 8, flexWrap: 'wrap', justifyContent: 'center' },
  erreur: { color: '#d33', marginTop: 8, textAlign: 'center' },
});
