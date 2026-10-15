import { useState } from 'react';
import { ScrollView, StyleSheet, View } from 'react-native';
import { Button, Text, TextInput } from 'react-native-paper';

import ForceMotDePasse from '@/components/ForceMotDePasse';
import { useAuth } from '@/context/AuthContext';
import { api, apiErrorMessage } from '@/lib/api';
import { SURFACE } from '@/lib/themes';

function ChampMotDePasse({ label, value, onChangeText }: { label: string; value: string; onChangeText: (v: string) => void }) {
  const [visible, setVisible] = useState(false);
  return (
    <TextInput
      mode="outlined"
      label={label}
      secureTextEntry={!visible}
      value={value}
      onChangeText={onChangeText}
      autoCapitalize="none"
      style={styles.champ}
      right={<TextInput.Icon icon={visible ? 'eye-off' : 'eye'} onPress={() => setVisible((v) => !v)} accessibilityLabel={visible ? 'Masquer le mot de passe' : 'Afficher le mot de passe'} />}
    />
  );
}

// Première connexion avec un mot de passe temporaire (donné par un Admin) :
// rien d'autre n'est accessible tant qu'il n'est pas remplacé.
export default function ChangerMotDePasseObligatoire() {
  const { user, refreshUser, logout } = useAuth();
  const [actuel, setActuel] = useState('');
  const [nouveau, setNouveau] = useState('');
  const [confirmation, setConfirmation] = useState('');
  const [erreur, setErreur] = useState<string | null>(null);
  const [envoi, setEnvoi] = useState(false);

  // « Enregistrer » reste grisé tant que le nouveau mot de passe ne respecte pas les règles du serveur.
  const manque = !actuel
    ? 'Saisissez votre mot de passe actuel (le mot de passe temporaire).'
    : nouveau.length < 8 || !/\p{L}/u.test(nouveau) || !/\d/.test(nouveau)
      ? 'Le nouveau mot de passe doit avoir au moins 8 caractères, avec des lettres et des chiffres.'
      : nouveau === actuel
        ? 'Le nouveau mot de passe doit être différent du mot de passe temporaire.'
        : confirmation !== nouveau
          ? 'La confirmation ne correspond pas au nouveau mot de passe.'
          : '';

  async function enregistrer() {
    setErreur(null);
    if (nouveau !== confirmation) {
      setErreur('La confirmation ne correspond pas au nouveau mot de passe.');
      return;
    }
    setEnvoi(true);
    try {
      await api.put('/auth/password', { current_password: actuel, password: nouveau, password_confirmation: confirmation });
      await refreshUser();
    } catch (err) {
      setErreur(apiErrorMessage(err, 'Impossible de changer le mot de passe.'));
    } finally {
      setEnvoi(false);
    }
  }

  return (
    <ScrollView style={styles.page} contentContainerStyle={styles.contenu}>
      <Text variant="titleLarge" style={styles.titre}>Choisissez votre mot de passe</Text>
      <View style={styles.alerte}>
        <Text>
          Votre mot de passe est <Text style={styles.gras}>temporaire</Text> : choisissez-en un nouveau pour continuer.
        </Text>
        {user?.mdp_temporaire_valable_jusqu_au ? (
          <Text style={styles.validite}>Ce mot de passe temporaire est valable jusqu'au {user.mdp_temporaire_valable_jusqu_au}.</Text>
        ) : null}
      </View>
      <ChampMotDePasse label="Mot de passe actuel (celui qui vous a été donné)" value={actuel} onChangeText={setActuel} />
      <ChampMotDePasse label="Nouveau mot de passe" value={nouveau} onChangeText={setNouveau} />
      <ForceMotDePasse motDePasse={nouveau} />
      <ChampMotDePasse label="Confirmer le nouveau mot de passe" value={confirmation} onChangeText={setConfirmation} />
      {erreur ? <Text style={styles.erreur}>{erreur}</Text> : null}
      {manque && (nouveau || confirmation) ? <Text style={styles.manque}>{manque}</Text> : null}
      <Button mode="contained" onPress={enregistrer} loading={envoi} disabled={!!manque || envoi} style={styles.bouton}>
        Enregistrer mon mot de passe
      </Button>
      <Button onPress={logout} style={styles.deconnexion}>
        Se déconnecter
      </Button>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  page: { flex: 1, backgroundColor: SURFACE.background },
  contenu: { padding: 24, paddingTop: 48 },
  titre: { fontWeight: '700', marginBottom: 16 },
  manque: { opacity: 0.7, marginTop: 4 },
  validite: { marginTop: 6, fontWeight: '600' },
  alerte: { backgroundColor: '#fff4e5', borderRadius: 8, padding: 12, marginBottom: 16 },
  gras: { fontWeight: '700' },
  champ: { marginBottom: 12 },
  erreur: { color: '#b3261e', marginBottom: 12 },
  bouton: { marginTop: 8 },
  deconnexion: { marginTop: 12 },
});
