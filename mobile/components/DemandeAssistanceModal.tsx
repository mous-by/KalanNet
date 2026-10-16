import { useCallback, useEffect, useRef, useState } from 'react';
import { AppState, StyleSheet } from 'react-native';
import { Button, Dialog, Portal, Snackbar, Text } from 'react-native-paper';

import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';

interface Demande {
  id: number;
  compte: string;
  motif: string;
  restant: number;
}

const INTERVALLE_MS = 10000;

/**
 * Accès d'assistance KalanNet : quand le support demande l'accès à un compte de
 * l'école, l'Admin accepte ou refuse ici, sans passer par le site (2 minutes).
 */
export default function DemandeAssistanceModal() {
  const { user } = useAuth();
  const [demande, setDemande] = useState<Demande | null>(null);
  const [restant, setRestant] = useState(0);
  const [envoi, setEnvoi] = useState<'accepter' | 'refuser' | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const vues = useRef(new Set<number>());
  const estAdmin = user?.droit === 'Admin';

  const verifier = useCallback(async () => {
    if (!estAdmin || AppState.currentState !== 'active') return;
    try {
      const { data } = await api.get<{ demande: Demande | null }>('/assistance/demande');
      const nouvelle = data.demande;
      if (nouvelle && nouvelle.restant > 0 && !vues.current.has(nouvelle.id)) {
        vues.current.add(nouvelle.id);
        setDemande(nouvelle);
        setRestant(nouvelle.restant);
      }
    } catch {
      // Réseau ou serveur plus ancien : on réessaie au prochain passage.
    }
  }, [estAdmin]);

  useEffect(() => {
    if (!estAdmin) return;
    verifier();
    const minuterie = setInterval(verifier, INTERVALLE_MS);
    const abonnement = AppState.addEventListener('change', (etat) => etat === 'active' && verifier());
    return () => {
      clearInterval(minuterie);
      abonnement.remove();
    };
  }, [estAdmin, verifier]);

  // Compte à rebours ; la fenêtre se ferme à l'expiration de la demande.
  useEffect(() => {
    if (!demande) return;
    const chrono = setInterval(() => {
      setRestant((r) => {
        if (r <= 1) {
          setDemande(null);
          return 0;
        }
        return r - 1;
      });
    }, 1000);
    return () => clearInterval(chrono);
  }, [demande]);

  async function repondre(decision: 'accepter' | 'refuser') {
    if (!demande) return;
    setEnvoi(decision);
    try {
      const { data } = await api.post<{ ok: boolean; message: string }>(`/assistance/${demande.id}/repondre`, { decision });
      setMessage(data.message);
    } catch (err) {
      setMessage(apiErrorMessage(err, 'Cette demande n’est plus en attente (expirée ou déjà traitée).'));
    } finally {
      setEnvoi(null);
      setDemande(null);
    }
  }

  if (!estAdmin) return null;

  const minutes = Math.floor(restant / 60);
  const secondes = String(restant % 60).padStart(2, '0');

  return (
    <Portal>
      <Dialog visible={demande !== null} dismissable={false}>
        <Dialog.Icon icon="lifebuoy" />
        <Dialog.Title style={styles.titre}>Demande d’assistance KalanNet</Dialog.Title>
        <Dialog.Content>
          <Text style={styles.paragraphe}>
            Le support KalanNet demande à accéder au compte de <Text style={styles.gras}>{demande?.compte}</Text> pour résoudre un problème.
          </Text>
          <Text style={styles.paragraphe}>
            <Text style={styles.gras}>Motif : </Text>
            {demande?.motif}
          </Text>
          <Text style={styles.delai}>
            Cette demande expire dans {minutes}:{secondes}
          </Text>
        </Dialog.Content>
        <Dialog.Actions>
          <Button textColor="#d33" onPress={() => repondre('refuser')} loading={envoi === 'refuser'} disabled={envoi !== null}>
            Refuser
          </Button>
          <Button mode="contained" onPress={() => repondre('accepter')} loading={envoi === 'accepter'} disabled={envoi !== null}>
            Accepter
          </Button>
        </Dialog.Actions>
      </Dialog>
      <Snackbar visible={message !== null} onDismiss={() => setMessage(null)} duration={4000}>
        {message ?? ''}
      </Snackbar>
    </Portal>
  );
}

const styles = StyleSheet.create({
  titre: { textAlign: 'center' },
  paragraphe: { marginBottom: 8 },
  gras: { fontWeight: '700' },
  delai: { opacity: 0.7, marginTop: 4 },
});
