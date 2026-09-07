import { useState } from 'react';
import { Image, KeyboardAvoidingView, Platform, ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Button, Card, List, Text, TextInput } from 'react-native-paper';

import { useAuth } from '@/context/AuthContext';
import { apiErrorMessage } from '@/lib/api';
import { SURFACE } from '@/lib/themes';
import { AccountChoice } from '@/types/api';

export default function LoginScreen() {
  const { login, selectSchool, pendingAccounts, cancelSchoolSelection } = useAuth();
  const [identifier, setIdentifier] = useState('');
  const [pwd, setPwd] = useState('');
  const [secure, setSecure] = useState(true);
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
      <ScrollView style={styles.container} contentContainerStyle={styles.content}>
        <Text variant="headlineSmall" style={styles.title}>
          Choisissez une école
        </Text>
        <Text style={styles.subtitle}>Ce compte est rattaché à plusieurs écoles.</Text>

        <Card style={styles.card}>
          {pendingAccounts.map((account, index) => (
            <List.Item
              key={`${account.id_utilisateur}-${account.id_ecole}`}
              title={account.nom_ecole ?? 'École sans nom'}
              description={account.droit}
              onPress={() => handleSelectAccount(account)}
              right={(props) => <List.Icon {...props} icon="chevron-right" />}
              disabled={isSubmitting}
              style={index > 0 ? styles.listItemBorder : undefined}
            />
          ))}
        </Card>

        {error ? <Text style={styles.error}>{error}</Text> : null}

        <Button mode="text" onPress={cancelSchoolSelection} style={styles.backButton}>
          Retour
        </Button>
      </ScrollView>
    );
  }

  return (
    <KeyboardAvoidingView style={styles.container} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
      <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
        <View style={styles.brand}>
          <Image source={require('../assets/images/icon.png')} style={styles.logo} resizeMode="contain" />
          <Text variant="headlineMedium" style={styles.title}>
            KalanNet
          </Text>
          <Text style={styles.subtitle}>Connectez-vous pour continuer</Text>
        </View>

        <TextInput
          mode="outlined"
          label="Email ou téléphone"
          autoCapitalize="none"
          keyboardType="email-address"
          value={identifier}
          onChangeText={setIdentifier}
          style={styles.input}
        />
        <TextInput
          mode="outlined"
          label="Mot de passe"
          secureTextEntry={secure}
          value={pwd}
          onChangeText={setPwd}
          style={styles.input}
          right={<TextInput.Icon icon={secure ? 'eye-off' : 'eye'} onPress={() => setSecure((prev) => !prev)} />}
        />

        {error ? <Text style={styles.error}>{error}</Text> : null}

        <Button
          mode="contained"
          onPress={handleSubmit}
          disabled={isSubmitting || !identifier || !pwd}
          style={styles.submitButton}
          contentStyle={styles.submitButtonContent}>
          {isSubmitting ? <ActivityIndicator color="#fff" /> : 'Se connecter'}
        </Button>
      </ScrollView>
    </KeyboardAvoidingView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: SURFACE.background,
  },
  content: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: 24,
  },
  brand: {
    alignItems: 'center',
    marginBottom: 32,
  },
  logo: {
    width: 72,
    height: 72,
    marginBottom: 12,
  },
  title: {
    fontWeight: 'bold',
    color: SURFACE.text,
  },
  subtitle: {
    fontSize: 15,
    color: SURFACE.muted,
    marginTop: 4,
  },
  input: {
    marginBottom: 14,
  },
  submitButton: {
    marginTop: 8,
    borderRadius: 10,
  },
  submitButtonContent: {
    paddingVertical: 6,
  },
  error: {
    color: '#d33',
    textAlign: 'center',
    marginBottom: 12,
  },
  card: {
    marginTop: 8,
  },
  listItemBorder: {
    borderTopWidth: StyleSheet.hairlineWidth,
    borderColor: SURFACE.border,
  },
  backButton: {
    marginTop: 16,
  },
});
