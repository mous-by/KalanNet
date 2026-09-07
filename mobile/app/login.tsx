import { useState } from 'react';
import { LinearGradient } from 'expo-linear-gradient';
import { KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Card, List, Text, TextInput } from 'react-native-paper';

import BrandLogo from '@/components/BrandLogo';
import BrandTitle from '@/components/BrandTitle';
import ThemeDots from '@/components/ThemeDots';
import { useAuth } from '@/context/AuthContext';
import { apiErrorMessage } from '@/lib/api';
import { ThemeKey, getTheme } from '@/lib/themes';
import { AccountChoice } from '@/types/api';

export default function LoginScreen() {
  const { login, selectSchool, pendingAccounts, cancelSchoolSelection } = useAuth();
  const [identifier, setIdentifier] = useState('');
  const [pwd, setPwd] = useState('');
  const [secure, setSecure] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  // No account is signed in yet, so this only previews the look — the real
  // preference (saved server-side) takes over right after login.
  const [previewThemeKey, setPreviewThemeKey] = useState<ThemeKey>('vert');
  const previewTheme = getTheme(previewThemeKey);

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

  return (
    <View style={[styles.background, { backgroundColor: previewTheme.chrome }]}>
      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
          <View style={styles.card}>
            <View style={styles.topBar} />

            <View style={styles.brand}>
              <BrandLogo size={72} />
              <BrandTitle fontSize={26} />
              <Text style={styles.subtitle}>SYSTÈME DE GESTION SCOLAIRE</Text>
            </View>

            <ThemeDots value={previewThemeKey} onChange={setPreviewThemeKey} size={18} />

            {pendingAccounts ? (
              <View style={styles.schoolPicker}>
                <Text style={styles.schoolPickerTitle}>Choisissez une école</Text>
                <Card style={styles.schoolCard}>
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
                <Text style={styles.backLink} onPress={cancelSchoolSelection}>
                  Retour
                </Text>
              </View>
            ) : (
              <View style={styles.form}>
                <TextInput
                  mode="outlined"
                  label="Identifiant"
                  placeholder="Email ou téléphone"
                  autoCapitalize="none"
                  keyboardType="email-address"
                  value={identifier}
                  onChangeText={setIdentifier}
                  left={<TextInput.Icon icon="account-badge-outline" />}
                  style={styles.input}
                />
                <TextInput
                  mode="outlined"
                  label="Mot de passe"
                  secureTextEntry={secure}
                  value={pwd}
                  onChangeText={setPwd}
                  left={<TextInput.Icon icon="lock-outline" />}
                  right={<TextInput.Icon icon={secure ? 'eye-off' : 'eye'} onPress={() => setSecure((prev) => !prev)} />}
                  style={styles.input}
                />

                {error ? <Text style={styles.error}>{error}</Text> : null}

                <Pressable onPress={handleSubmit} disabled={isSubmitting || !identifier || !pwd}>
                  <LinearGradient
                    colors={[previewTheme.accent, '#0f172a']}
                    start={{ x: 0, y: 0 }}
                    end={{ x: 1, y: 1 }}
                    style={[styles.submitButton, (isSubmitting || !identifier || !pwd) && styles.submitButtonDisabled]}>
                    {isSubmitting ? (
                      <ActivityIndicator color="#fff" size="small" />
                    ) : (
                      <Text style={styles.submitButtonText}>Se connecter</Text>
                    )}
                  </LinearGradient>
                </Pressable>
              </View>
            )}

            <Text style={styles.footer}>
              © {new Date().getFullYear()} <Text style={styles.footerKal}>Kal</Text>
              <Text style={styles.footerAn}>an</Text>
              <Text style={styles.footerNet}>Net</Text>
            </Text>
          </View>
        </ScrollView>
      </KeyboardAvoidingView>
    </View>
  );
}

const styles = StyleSheet.create({
  background: {
    flex: 1,
  },
  flex: {
    flex: 1,
  },
  content: {
    flexGrow: 1,
    justifyContent: 'center',
    padding: 24,
  },
  card: {
    backgroundColor: '#ffffff',
    borderRadius: 20,
    padding: 24,
    maxWidth: 420,
    width: '100%',
    alignSelf: 'center',
    overflow: 'hidden',
    shadowColor: '#0a1223',
    shadowOpacity: 0.3,
    shadowRadius: 24,
    shadowOffset: { width: 0, height: 16 },
    elevation: 8,
  },
  topBar: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: 4,
    backgroundColor: '#2563eb',
  },
  brand: {
    alignItems: 'center',
    marginBottom: 16,
  },
  subtitle: {
    marginTop: 8,
    fontSize: 11,
    fontWeight: '700',
    letterSpacing: 0.5,
    color: '#64748b',
  },
  schoolPicker: {
    marginTop: 8,
  },
  schoolPickerTitle: {
    fontWeight: '600',
    textAlign: 'center',
    marginBottom: 8,
  },
  schoolCard: {
    marginTop: 8,
  },
  listItemBorder: {
    borderTopWidth: StyleSheet.hairlineWidth,
    borderColor: '#e2e8f0',
  },
  backLink: {
    textAlign: 'center',
    marginTop: 16,
    color: '#1f8a4c',
  },
  form: {
    marginTop: 20,
  },
  input: {
    marginBottom: 14,
  },
  submitButton: {
    borderRadius: 8,
    minHeight: 48,
    alignItems: 'center',
    justifyContent: 'center',
    marginTop: 4,
  },
  submitButtonDisabled: {
    opacity: 0.6,
  },
  submitButtonText: {
    color: '#ffffff',
    fontWeight: '800',
    fontSize: 15,
  },
  error: {
    color: '#d33',
    textAlign: 'center',
    marginBottom: 8,
  },
  footer: {
    textAlign: 'center',
    marginTop: 20,
    fontSize: 12,
    color: '#64748b',
  },
  footerKal: { color: '#16a34a', fontWeight: '800' },
  footerAn: { color: '#eab308', fontWeight: '800' },
  footerNet: { color: '#dc2626', fontWeight: '800' },
});
