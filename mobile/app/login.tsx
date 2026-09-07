import { useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  StyleSheet,
  TextInput,
} from 'react-native';

import { Text, View } from '@/components/Themed';
import { useAuth } from '@/context/AuthContext';
import { apiErrorMessage } from '@/lib/api';
import { AccountChoice } from '@/types/api';

export default function LoginScreen() {
  const { login, selectSchool, pendingAccounts, cancelSchoolSelection } = useAuth();
  const [identifier, setIdentifier] = useState('');
  const [pwd, setPwd] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit() {
    setError(null);
    setIsSubmitting(true);
    try {
      await login(identifier.trim(), pwd);
    } catch (err) {
      setError(apiErrorMessage(err, 'Identifiant ou mot de passe incorrect.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleSelectAccount(account: AccountChoice) {
    setError(null);
    setIsSubmitting(true);
    try {
      await selectSchool(account.id_utilisateur, account.id_ecole!);
    } catch (err) {
      setError(apiErrorMessage(err, 'Impossible de se connecter à cette école.'));
    } finally {
      setIsSubmitting(false);
    }
  }

  if (pendingAccounts) {
    return (
      <View style={styles.container}>
        <Text style={styles.title}>Choisissez une école</Text>
        <Text style={styles.subtitle}>Ce compte est rattaché à plusieurs écoles.</Text>
        <FlatList
          data={pendingAccounts}
          keyExtractor={(item) => `${item.id_utilisateur}-${item.id_ecole}`}
          style={styles.accountList}
          renderItem={({ item }) => (
            <Pressable
              style={styles.accountRow}
              disabled={isSubmitting}
              onPress={() => handleSelectAccount(item)}>
              <Text style={styles.accountName}>{item.nom_ecole ?? 'École sans nom'}</Text>
              <Text style={styles.accountRole}>{item.droit}</Text>
            </Pressable>
          )}
        />
        {error ? <Text style={styles.error}>{error}</Text> : null}
        <Pressable onPress={cancelSchoolSelection} style={styles.linkButton}>
          <Text style={styles.linkText}>Retour</Text>
        </Pressable>
      </View>
    );
  }

  return (
    <KeyboardAvoidingView
      style={styles.container}
      behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <Text style={styles.title}>KalanNet</Text>
      <Text style={styles.subtitle}>Connectez-vous pour continuer</Text>

      <TextInput
        style={styles.input}
        placeholder="Email ou téléphone"
        placeholderTextColor="#888"
        autoCapitalize="none"
        keyboardType="email-address"
        value={identifier}
        onChangeText={setIdentifier}
      />
      <TextInput
        style={styles.input}
        placeholder="Mot de passe"
        placeholderTextColor="#888"
        secureTextEntry
        value={pwd}
        onChangeText={setPwd}
      />

      {error ? <Text style={styles.error}>{error}</Text> : null}

      <Pressable
        style={[styles.button, (isSubmitting || !identifier || !pwd) && styles.buttonDisabled]}
        disabled={isSubmitting || !identifier || !pwd}
        onPress={handleSubmit}>
        {isSubmitting ? <ActivityIndicator color="#fff" /> : <Text style={styles.buttonText}>Se connecter</Text>}
      </Pressable>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    justifyContent: 'center',
    padding: 24,
  },
  title: {
    fontSize: 32,
    fontWeight: 'bold',
    textAlign: 'center',
    marginBottom: 8,
  },
  subtitle: {
    fontSize: 15,
    textAlign: 'center',
    opacity: 0.6,
    marginBottom: 32,
  },
  input: {
    borderWidth: 1,
    borderColor: '#ccc',
    borderRadius: 10,
    paddingHorizontal: 16,
    paddingVertical: 14,
    fontSize: 16,
    marginBottom: 12,
  },
  button: {
    backgroundColor: '#1f8a4c',
    borderRadius: 10,
    paddingVertical: 14,
    alignItems: 'center',
    marginTop: 8,
  },
  buttonDisabled: {
    opacity: 0.5,
  },
  buttonText: {
    color: '#fff',
    fontSize: 16,
    fontWeight: '600',
  },
  error: {
    color: '#d33',
    textAlign: 'center',
    marginBottom: 8,
  },
  accountList: {
    maxHeight: 320,
  },
  accountRow: {
    paddingVertical: 14,
    paddingHorizontal: 16,
    borderWidth: 1,
    borderColor: '#ccc',
    borderRadius: 10,
    marginBottom: 10,
  },
  accountName: {
    fontSize: 16,
    fontWeight: '600',
  },
  accountRole: {
    fontSize: 13,
    opacity: 0.6,
    marginTop: 2,
  },
  linkButton: {
    alignItems: 'center',
    marginTop: 16,
  },
  linkText: {
    color: '#1f8a4c',
    fontSize: 15,
  },
});
