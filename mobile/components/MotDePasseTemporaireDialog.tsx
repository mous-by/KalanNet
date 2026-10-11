import { StyleSheet } from 'react-native';
import { Button, Dialog, Portal, Text } from 'react-native-paper';

// Mot de passe temporaire (création ou réinitialisation d'un compte) : affiché
// une seule fois, à transmettre en main propre. Même modèle que le web.
export default function MotDePasseTemporaireDialog({
  motDePasse,
  pour,
  onFermer,
}: {
  motDePasse: string | null;
  pour: string;
  onFermer: () => void;
}) {
  return (
    <Portal>
      <Dialog visible={!!motDePasse} dismissable={false}>
        <Dialog.Icon icon="key-variant" />
        <Dialog.Title style={styles.titre}>Mot de passe temporaire</Dialog.Title>
        <Dialog.Content>
          <Text>
            Pour <Text style={styles.gras}>{pour}</Text>, à transmettre en main propre. Il ne sera plus affiché ensuite.
          </Text>
          <Text selectable style={styles.motDePasse}>
            {motDePasse}
          </Text>
          <Text style={styles.aide}>Appui long sur le mot de passe pour le copier. À sa première connexion, la personne devra choisir son propre mot de passe.</Text>
        </Dialog.Content>
        <Dialog.Actions>
          <Button mode="contained" onPress={onFermer}>
            J'ai noté le mot de passe
          </Button>
        </Dialog.Actions>
      </Dialog>
    </Portal>
  );
}

const styles = StyleSheet.create({
  titre: { textAlign: 'center' },
  gras: { fontWeight: '700' },
  motDePasse: {
    marginTop: 16,
    marginBottom: 12,
    paddingVertical: 12,
    fontSize: 22,
    fontWeight: '700',
    letterSpacing: 2,
    textAlign: 'center',
    fontFamily: 'monospace',
    backgroundColor: '#f1f5f9',
    borderRadius: 8,
  },
  aide: { fontSize: 12, opacity: 0.7 },
});
