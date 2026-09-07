import { useState } from 'react';
import { LinearGradient } from 'expo-linear-gradient';
import { Dimensions, KeyboardAvoidingView, Platform, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import { ActivityIndicator, Card, List, Text, TextInput } from 'react-native-paper';

import BrandLogo from '@/components/BrandLogo';
import BrandTitle from '@/components/BrandTitle';
import LanguageRow from '@/components/LanguageRow';
import LoginCarousel from '@/components/LoginCarousel';
import ThemeDots from '@/components/ThemeDots';
import { useAuth } from '@/context/AuthContext';
import { useLocale } from '@/context/LocaleContext';
import { apiErrorMessage } from '@/lib/api';
import { TranslationKey } from '@/lib/i18n';
import { ThemeKey, getTheme } from '@/lib/themes';
import { AccountChoice } from '@/types/api';

const { height: SCREEN_HEIGHT } = Dimensions.get('window');
const HERO_HEIGHT = Math.min(Math.round(SCREEN_HEIGHT * 0.42), 420);

// Captions matched to each background photo, one per slide index — mirrors
// the rotating quote shown on the web login page (lang/*/messages.php
// auth.slideN_title/quote), reusing the same three slides' wording.
const SLIDE_CAPTIONS: [TranslationKey, TranslationKey][] = [
  ['auth.slide1_title', 'auth.slide1_quote'],
  ['auth.slide2_title', 'auth.slide2_quote'],
  ['auth.slide3_title', 'auth.slide3_quote'],
];

export default function LoginScreen() {
  const { login, selectSchool, pendingAccounts, cancelSchoolSelection } = useAuth();
  const { t, locale, setLocale } = useLocale();
  const [identifier, setIdentifier] = useState('');
  const [pwd, setPwd] = useState('');
  const [secure, setSecure] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [slideIndex, setSlideIndex] = useState(0);
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
      setError(apiErrorMessage(err, t('auth.invalid_credentials')));
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
      setError(apiErrorMessage(err, t('auth.school_selection_error')));
    } finally {
      setIsSubmitting(false);
    }
  }

  const [captionTitleKey, captionQuoteKey] = SLIDE_CAPTIONS[slideIndex];

  return (
    <View style={styles.screen}>
      <View style={styles.hero}>
        <LoginCarousel onIndexChange={setSlideIndex} />
        <LinearGradient colors={['rgba(255,255,255,0)', '#ffffff']} style={styles.heroFade} />
      </View>

      <KeyboardAvoidingView style={styles.flex} behavior={Platform.OS === 'ios' ? 'padding' : undefined}>
        <ScrollView contentContainerStyle={styles.content} keyboardShouldPersistTaps="handled">
          <View style={styles.sheet}>
            <View style={styles.brandRow}>
              <BrandLogo size={40} />
              <BrandTitle fontSize={20} />
            </View>

            <ThemeDots value={previewThemeKey} onChange={setPreviewThemeKey} size={16} />
            <LanguageRow value={locale} onChange={setLocale} />

            {pendingAccounts ? (
              <View style={styles.schoolPicker}>
                <Text style={styles.schoolPickerTitle}>{t('auth.choose_school')}</Text>
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
                  {t('auth.back')}
                </Text>
              </View>
            ) : (
              <View style={styles.form}>
                <TextInput
                  mode="outlined"
                  label={t('auth.identifier')}
                  placeholder={t('auth.identifier_placeholder')}
                  autoCapitalize="none"
                  keyboardType="email-address"
                  value={identifier}
                  onChangeText={setIdentifier}
                  left={<TextInput.Icon icon="account-badge-outline" />}
                  style={styles.input}
                />
                <TextInput
                  mode="outlined"
                  label={t('auth.password')}
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
                      <Text style={styles.submitButtonText}>{t('auth.login')}</Text>
                    )}
                  </LinearGradient>
                </Pressable>
              </View>
            )}

            <View style={styles.caption}>
              <Text style={styles.captionTitle}>{t(captionTitleKey)}</Text>
              <Text style={styles.captionQuote}>{t(captionQuoteKey)}</Text>
            </View>

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
  screen: {
    flex: 1,
    backgroundColor: '#ffffff',
  },
  hero: {
    position: 'absolute',
    top: 0,
    left: 0,
    right: 0,
    height: HERO_HEIGHT,
    overflow: 'hidden',
  },
  heroFade: {
    position: 'absolute',
    left: 0,
    right: 0,
    bottom: 0,
    height: 120,
  },
  flex: {
    flex: 1,
  },
  content: {
    flexGrow: 1,
    paddingTop: HERO_HEIGHT - 40,
  },
  sheet: {
    backgroundColor: '#ffffff',
    borderTopLeftRadius: 28,
    borderTopRightRadius: 28,
    padding: 24,
    paddingBottom: 32,
    shadowColor: '#0a1223',
    shadowOpacity: 0.12,
    shadowRadius: 20,
    shadowOffset: { width: 0, height: -6 },
    elevation: 6,
  },
  brandRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 8,
    marginBottom: 16,
  },
  schoolPicker: {
    marginTop: 16,
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
    marginTop: 4,
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
  caption: {
    marginTop: 20,
    alignItems: 'center',
  },
  captionTitle: {
    fontWeight: '700',
    fontSize: 13,
    color: '#0f172a',
    textAlign: 'center',
  },
  captionQuote: {
    fontSize: 12,
    color: '#64748b',
    textAlign: 'center',
    marginTop: 4,
    fontStyle: 'italic',
  },
  footer: {
    textAlign: 'center',
    marginTop: 16,
    fontSize: 12,
    color: '#64748b',
  },
  footerKal: { color: '#16a34a', fontWeight: '800' },
  footerAn: { color: '#eab308', fontWeight: '800' },
  footerNet: { color: '#dc2626', fontWeight: '800' },
});
