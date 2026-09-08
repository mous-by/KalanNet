import { useMemo } from 'react';
import { router } from 'expo-router';
import { ImageBackground, Pressable, StyleSheet, View } from 'react-native';
import { Text } from 'react-native-paper';
import { MaterialCommunityIcons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';

import { SURFACE } from '@/lib/themes';
import { AnneeScolaire, Eleve } from '@/types/api';

interface ClasseDataRow {
  nom: string;
  total: number;
  filles: number;
  garcons: number;
}

export interface AdminDashboardData {
  totalEleves: number;
  totalGarcons: number;
  totalFilles: number;
  totalEnseignants: number;
  totalClasses: number;
  totalRecettes: string | number;
  soldeCaisse: string | number;
  recentEleves: Eleve[];
  anneeEnCours: AnneeScolaire | null;
  classesData: ClasseDataRow[];
}

const QUICK_LINKS: { icon: keyof typeof MaterialCommunityIcons.glyphMap; color: string; label: string; href: string }[] = [
  { icon: 'account-group', color: '#16a34a', label: 'Élèves', href: '/eleves' },
  { icon: 'clipboard-text', color: '#2563eb', label: 'Notes', href: '/plus/evaluations' },
  { icon: 'calendar-check', color: '#d97706', label: 'Présences', href: '/plus/presences' },
  { icon: 'cash-multiple', color: '#7c3aed', label: 'Finances', href: '/plus/finances' },
];

const TIPS = [
  "L'éducation est la clé d'un meilleur avenir.",
  'Une bonne communication avec les parents renforce la réussite des élèves.',
  'Un suivi régulier des présences améliore les résultats scolaires.',
];

// Compact form for the narrow stat card (e.g. "224 k") — the full amount
// with a currency suffix doesn't fit in a quarter-width card.
function formatCompactAmount(value: string | number): string {
  const amount = Number(value);
  if (amount >= 1_000_000) return `${(amount / 1_000_000).toLocaleString('fr-FR', { maximumFractionDigits: 1 })} M`;
  if (amount >= 1_000) return `${Math.round(amount / 1000)} k`;
  return String(Math.round(amount));
}

function relativeOrDate(dateString: string | null): string {
  if (!dateString) return '—';
  const date = new Date(dateString);
  const days = Math.floor((Date.now() - date.getTime()) / 86400000);
  if (days <= 0) return "Aujourd'hui";
  if (days === 1) return 'Hier';
  if (days < 7) return `Il y a ${days} j`;
  return date.toLocaleDateString('fr-FR');
}

export default function AdminDashboardView({ data }: { data: AdminDashboardData }) {
  const tip = useMemo(() => TIPS[new Date().getDate() % TIPS.length], []);

  const stats = [
    { icon: 'account-group' as const, color: '#16a34a', value: String(data.totalEleves), label: 'Élèves' },
    { icon: 'account-tie' as const, color: '#2563eb', value: String(data.totalEnseignants), label: 'Enseignants' },
    { icon: 'google-classroom' as const, color: '#d97706', value: String(data.totalClasses), label: 'Classes' },
    { icon: 'cash-multiple' as const, color: '#7c3aed', value: formatCompactAmount(data.totalRecettes), label: 'Recettes' },
  ];

  return (
    <View>
      <ImageBackground
        source={require('../../assets/images/dashboard-banner.jpg')}
        style={styles.banner}
        imageStyle={styles.bannerImage}
        resizeMode="cover">
        <LinearGradient
          colors={['rgba(0,0,0,0.55)', 'rgba(0,0,0,0.15)', 'rgba(0,0,0,0)']}
          start={{ x: 0, y: 0 }}
          end={{ x: 1, y: 0 }}
          style={styles.bannerGradient}>
          <Text style={styles.bannerTitle}>Une école mieux gérée{'\n'}pour un meilleur avenir !</Text>
          <Text style={styles.bannerSubtitle}>KalanNet, votre partenaire de gestion scolaire.</Text>
        </LinearGradient>
      </ImageBackground>

      <View style={styles.statsRow}>
        {stats.map((stat) => (
          <View key={stat.label} style={styles.statCard}>
            <View style={[styles.statIcon, { backgroundColor: stat.color }]}>
              <MaterialCommunityIcons name={stat.icon} size={18} color="#fff" />
            </View>
            <Text style={styles.statValue} numberOfLines={1} adjustsFontSizeToFit>
              {stat.value}
            </Text>
            <Text style={styles.statLabel}>{stat.label}</Text>
          </View>
        ))}
      </View>

      {data.recentEleves.length > 0 ? (
        <View style={styles.card}>
          <View style={styles.cardHeaderRow}>
            <View style={styles.cardHeaderLeft}>
              <MaterialCommunityIcons name="clock-outline" size={18} color={SURFACE.text} />
              <Text style={styles.cardTitle}>Derniers élèves inscrits</Text>
            </View>
            <Pressable onPress={() => router.push('/eleves')}>
              <Text style={styles.seeAll}>Voir tout</Text>
            </Pressable>
          </View>
          {data.recentEleves.slice(0, 3).map((eleve) => (
            <Pressable key={eleve.id_eleve} style={styles.activityRow} onPress={() => router.push(`/eleves/${eleve.id_eleve}`)}>
              <View style={styles.activityIcon}>
                <MaterialCommunityIcons name="account-plus" size={18} color="#16a34a" />
              </View>
              <View style={styles.activityInfo}>
                <Text style={styles.activityTitle}>
                  {eleve.prenom_eleve} {eleve.nom_eleve}
                </Text>
                <Text style={styles.activityMeta}>{eleve.classe?.nom_classe ?? '—'}</Text>
              </View>
              <Text style={styles.activityTime}>{relativeOrDate(eleve.date_inscription)}</Text>
            </Pressable>
          ))}
        </View>
      ) : null}

      <View style={styles.card}>
        <View style={styles.cardHeaderLeft}>
          <MaterialCommunityIcons name="star-outline" size={18} color={SURFACE.text} />
          <Text style={styles.cardTitle}>Accès rapides</Text>
        </View>
        <View style={styles.quickRow}>
          {QUICK_LINKS.map((link) => (
            <Pressable key={link.href} style={styles.quickItem} onPress={() => router.push(link.href as never)}>
              <View style={[styles.quickIcon, { backgroundColor: link.color }]}>
                <MaterialCommunityIcons name={link.icon} size={20} color="#fff" />
              </View>
              <Text style={styles.quickLabel}>{link.label}</Text>
            </Pressable>
          ))}
        </View>
      </View>

      <View style={styles.tipBanner}>
        <MaterialCommunityIcons name="school-outline" size={20} color="#16a34a" />
        <Text style={styles.tipText}>{tip}</Text>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  banner: {
    borderRadius: 16,
    marginBottom: 16,
    height: 140,
    width: '100%',
    justifyContent: 'center',
    overflow: 'hidden',
  },
  bannerGradient: {
    flex: 1,
    borderRadius: 16,
    justifyContent: 'center',
    paddingHorizontal: 16,
  },
  bannerImage: {
    borderRadius: 16,
  },
  bannerTitle: {
    color: '#ffffff',
    fontWeight: '800',
    fontSize: 17,
    lineHeight: 22,
    maxWidth: '75%',
    textShadowColor: 'rgba(0,0,0,0.45)',
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 4,
  },
  bannerSubtitle: {
    color: '#f1f5f9',
    fontSize: 13,
    marginTop: 8,
    maxWidth: '75%',
    textShadowColor: 'rgba(0,0,0,0.45)',
    textShadowOffset: { width: 0, height: 1 },
    textShadowRadius: 4,
  },
  statsRow: {
    flexDirection: 'row',
    gap: 10,
    marginBottom: 16,
  },
  statCard: {
    flex: 1,
    backgroundColor: SURFACE.card,
    borderWidth: 1,
    borderColor: SURFACE.border,
    borderRadius: 14,
    padding: 10,
    alignItems: 'center',
  },
  statIcon: {
    width: 30,
    height: 30,
    borderRadius: 15,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 8,
  },
  statValue: {
    fontSize: 15,
    fontWeight: '700',
    color: SURFACE.text,
  },
  statLabel: {
    fontSize: 11,
    color: SURFACE.muted,
    marginTop: 2,
  },
  card: {
    backgroundColor: SURFACE.card,
    borderWidth: 1,
    borderColor: SURFACE.border,
    borderRadius: 14,
    padding: 16,
    marginBottom: 16,
  },
  cardHeaderRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: 8,
  },
  cardHeaderLeft: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 8,
    marginBottom: 8,
  },
  cardTitle: {
    fontWeight: '700',
    color: SURFACE.text,
  },
  seeAll: {
    color: '#1f8a4c',
    fontWeight: '600',
    fontSize: 13,
  },
  activityRow: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 8,
    borderTopWidth: StyleSheet.hairlineWidth,
    borderColor: SURFACE.border,
  },
  activityIcon: {
    width: 32,
    height: 32,
    borderRadius: 16,
    backgroundColor: 'rgba(22,163,74,0.12)',
    alignItems: 'center',
    justifyContent: 'center',
    marginRight: 10,
  },
  activityInfo: {
    flex: 1,
  },
  activityTitle: {
    color: SURFACE.text,
    fontWeight: '500',
  },
  activityMeta: {
    color: SURFACE.muted,
    fontSize: 12,
  },
  activityTime: {
    color: SURFACE.muted,
    fontSize: 12,
  },
  quickRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  quickItem: {
    alignItems: 'center',
    flex: 1,
  },
  quickIcon: {
    width: 48,
    height: 48,
    borderRadius: 24,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 6,
  },
  quickLabel: {
    fontSize: 12,
    color: SURFACE.text,
    textAlign: 'center',
  },
  tipBanner: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 10,
    backgroundColor: 'rgba(22,163,74,0.1)',
    borderRadius: 12,
    padding: 14,
  },
  tipText: {
    flex: 1,
    color: '#166534',
    fontSize: 13,
    fontWeight: '500',
  },
});
