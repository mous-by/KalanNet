import { MaterialCommunityIcons } from '@expo/vector-icons';
import { useRef, useState } from 'react';
import { Dimensions, FlatList, NativeScrollEvent, NativeSyntheticEvent, Pressable, StyleSheet, View } from 'react-native';
import { Button, Text } from 'react-native-paper';

import BrandLogo from '@/components/BrandLogo';
import BrandTitle from '@/components/BrandTitle';
import { useOnboarding } from '@/context/OnboardingContext';
import { useLocale } from '@/context/LocaleContext';
import { TranslationKey } from '@/lib/i18n';

const { width } = Dimensions.get('window');

interface ModuleItem {
  icon: keyof typeof MaterialCommunityIcons.glyphMap;
  color: string;
  labelKey: TranslationKey;
}

const MODULES: ModuleItem[] = [
  { icon: 'account-group', color: '#16a34a', labelKey: 'onboarding.module.students' },
  { icon: 'clipboard-text', color: '#2563eb', labelKey: 'onboarding.module.grades' },
  { icon: 'calendar-check', color: '#d97706', labelKey: 'onboarding.module.attendance' },
  { icon: 'cash-multiple', color: '#7c3aed', labelKey: 'onboarding.module.finances' },
  { icon: 'message-text', color: '#dc2626', labelKey: 'onboarding.module.communication' },
];

function Slide1() {
  const { t } = useLocale();
  return (
    <View style={styles.slide}>
      <BrandLogo size={96} />
      <BrandTitle fontSize={32} />
      <Text style={styles.tagline}>{t('onboarding.tagline')}</Text>
    </View>
  );
}

function Slide2() {
  const { t } = useLocale();
  return (
    <View style={styles.slide}>
      <Text style={styles.slideTitle}>{t('onboarding.modules_title')}</Text>
      <View style={styles.moduleList}>
        {MODULES.map((item) => (
          <View key={item.labelKey} style={styles.moduleRow}>
            <View style={[styles.moduleIcon, { backgroundColor: item.color }]}>
              <MaterialCommunityIcons name={item.icon} size={22} color="#fff" />
            </View>
            <Text style={styles.moduleLabel}>{t(item.labelKey)}</Text>
          </View>
        ))}
      </View>
    </View>
  );
}

function Slide3() {
  const { t } = useLocale();
  return (
    <View style={styles.slide}>
      <View style={[styles.moduleIcon, styles.closingIcon]}>
        <MaterialCommunityIcons name="account-group" size={40} color="#fff" />
      </View>
      <Text style={styles.slideTitle}>{t('onboarding.closing_title')}</Text>
      <Text style={styles.tagline}>{t('onboarding.closing_tagline')}</Text>
    </View>
  );
}

const SLIDES = [Slide1, Slide2, Slide3];

export default function OnboardingScreen() {
  const [index, setIndex] = useState(0);
  const listRef = useRef<FlatList>(null);
  const { markOnboarded } = useOnboarding();
  const { t } = useLocale();

  function finish() {
    // No explicit navigation: the root layout's Stack.Protected guard
    // swaps "onboarding" for "login" as soon as hasOnboarded flips, the
    // same way logging in swaps "login" for "(tabs)" elsewhere in the app.
    markOnboarded();
  }

  function handleNext() {
    if (index < SLIDES.length - 1) {
      listRef.current?.scrollToIndex({ index: index + 1, animated: true });
    } else {
      finish();
    }
  }

  function handleScrollEnd(event: NativeSyntheticEvent<NativeScrollEvent>) {
    const newIndex = Math.round(event.nativeEvent.contentOffset.x / width);
    setIndex(newIndex);
  }

  return (
    <View style={styles.container}>
      <Pressable style={styles.skip} onPress={finish}>
        <Text style={styles.skipText}>{t('onboarding.skip')}</Text>
      </Pressable>

      <FlatList
        ref={listRef}
        style={styles.list}
        data={SLIDES}
        keyExtractor={(_, i) => String(i)}
        horizontal
        pagingEnabled
        showsHorizontalScrollIndicator={false}
        onMomentumScrollEnd={handleScrollEnd}
        renderItem={({ item: Slide }) => (
          <View style={{ width }}>
            <Slide />
          </View>
        )}
      />

      <View style={styles.footer}>
        <View style={styles.dots}>
          {SLIDES.map((_, i) => (
            <View key={i} style={[styles.dot, i === index && styles.dotActive]} />
          ))}
        </View>

        <Button mode="contained" onPress={handleNext} style={styles.nextButton} contentStyle={styles.nextButtonContent}>
          {index === SLIDES.length - 1 ? t('onboarding.start') : t('onboarding.next')}
        </Button>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: '#ffffff',
  },
  skip: {
    position: 'absolute',
    top: 56,
    right: 20,
    zIndex: 1,
    padding: 8,
  },
  skipText: {
    color: '#64748b',
    fontWeight: '600',
  },
  list: {
    flex: 1,
  },
  slide: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 32,
    paddingTop: 60,
  },
  slideTitle: {
    fontSize: 22,
    fontWeight: '800',
    color: '#0f172a',
    textAlign: 'center',
    marginBottom: 24,
  },
  tagline: {
    fontSize: 15,
    color: '#64748b',
    textAlign: 'center',
    marginTop: 16,
  },
  moduleList: {
    width: '100%',
  },
  moduleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 18,
  },
  moduleIcon: {
    width: 44,
    height: 44,
    borderRadius: 22,
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 14,
  },
  closingIcon: {
    backgroundColor: '#0f172a',
    width: 72,
    height: 72,
    borderRadius: 36,
    marginBottom: 24,
  },
  moduleLabel: {
    fontSize: 15,
    fontWeight: '600',
    color: '#1e293b',
    flexShrink: 1,
  },
  footer: {
    paddingHorizontal: 24,
    paddingBottom: 32,
  },
  dots: {
    flexDirection: 'row',
    justifyContent: 'center',
    gap: 8,
    marginBottom: 20,
  },
  dot: {
    width: 8,
    height: 8,
    borderRadius: 4,
    backgroundColor: '#e2e8f0',
  },
  dotActive: {
    backgroundColor: '#16a34a',
    width: 22,
  },
  nextButton: {
    borderRadius: 10,
  },
  nextButtonContent: {
    paddingVertical: 6,
  },
});
