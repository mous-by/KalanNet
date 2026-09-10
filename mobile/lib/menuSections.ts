import { TranslationKey } from '@/lib/i18n';
import { hasAnyPermission } from '@/lib/permissions';
import { User } from '@/types/api';

export interface MenuItem {
  labelKey: TranslationKey;
  icon: string;
  href: string;
  // Any-of permission names — mirrors the exact groups gating the matching
  // link in resources/views/partials/sidebar.blade.php / configuration/_menu.blade.php.
  // Omitted entirely only for items with no web equivalent (mobile-only
  // features) or genuinely open to every authenticated staff member.
  permissions?: string[];
  // Mirrors sidebar.blade.php's `$user->droit !== 'parent'` guard on the
  // students/parents section: some parent accounts carry stray
  // eleves_dossier/parents_apercu permissions, so the permission check alone
  // isn't enough — this link must stay hidden from a parent regardless.
  hideForParent?: boolean;
}

export interface MenuSection {
  titleKey: TranslationKey;
  items: MenuItem[];
}

export function isMenuItemVisible(user: User | null | undefined, item: MenuItem): boolean {
  if (item.hideForParent && user?.droit === 'parent') return false;
  return !item.permissions || hasAnyPermission(user, item.permissions);
}

export const MENU_SECTIONS: MenuSection[] = [
  {
    titleKey: 'plus.section_sync',
    items: [{ labelKey: 'plus.synchronisation', icon: 'cloud-sync-outline', href: '/plus/synchronisation' }],
  },
  {
    titleKey: 'plus.section_pedagogy',
    items: [
      {
        labelKey: 'plus.teachers',
        icon: 'account-tie',
        href: '/plus/enseignants',
        permissions: ['enseignants_apercu', 'enseignants_creation', 'emargement_faire', 'presence_apercu', 'paiements_faire'],
      },
      { labelKey: 'plus.parents', icon: 'account-heart-outline', href: '/plus/parents', permissions: ['parents_apercu'], hideForParent: true },
      { labelKey: 'plus.subjects', icon: 'book-open-variant', href: '/plus/matieres', permissions: ['matieres_apercu'] },
      {
        labelKey: 'plus.timetable',
        icon: 'calendar-clock',
        href: '/plus/timetable',
        permissions: ['classes_apercu', 'enseignants_emploi', 'planning_apercu'],
      },
      { labelKey: 'plus.evaluations', icon: 'clipboard-text', href: '/plus/evaluations', permissions: ['evaluation_apercu'] },
      { labelKey: 'plus.emargements', icon: 'notebook-check', href: '/plus/emargements', permissions: ['emargement_faire'] },
      { labelKey: 'plus.presences', icon: 'account-check', href: '/plus/presences', permissions: ['presence_apercu'] },
      {
        labelKey: 'plus.bulletins',
        icon: 'file-document',
        href: '/plus/bulletins',
        permissions: ['bulletins_apercu', 'bulletins_generation', 'bulletins_pdf', 'bulletins_impression', 'bulletins_publication', 'generer_bulletins'],
      },
      {
        labelKey: 'plus.national_results',
        icon: 'school',
        href: '/plus/resultats-nationaux',
        permissions: ['resultats_nationaux_apercu', 'resultats_def_terminal_apercu', 'reinscriptions_apercu', 'inscriptions_reinscrire'],
      },
      {
        labelKey: 'plus.exam_calls',
        icon: 'alert-circle-outline',
        href: '/plus/appels-epreuves/new',
        permissions: ['controle_apercu', 'controle_creation'],
      },
    ],
  },
  {
    titleKey: 'plus.section_finances',
    items: [
      {
        labelKey: 'plus.payments',
        icon: 'cash-multiple',
        href: '/plus/finances',
        permissions: [
          'finances_planifications_apercu',
          'paiements_apercu',
          'paiements_faire',
          'historique_paiement_apercu',
          'caisses_apercu',
          'decaissements_apercu',
          'banques_apercu',
          'versements_apercu',
          'retraits_apercu',
        ],
      },
      { labelKey: 'plus.salaries', icon: 'wallet', href: '/plus/salaires', permissions: ['paiements_faire'] },
    ],
  },
  {
    titleKey: 'plus.section_communication',
    items: [{ labelKey: 'plus.announcements', icon: 'bullhorn', href: '/plus/annonces', permissions: ['annonces_apercu', 'annonces_creation', 'annonces_supprimer'] }],
  },
  {
    titleKey: 'plus.section_admin',
    items: [
      {
        labelKey: 'plus.users',
        icon: 'account-group',
        href: '/plus/configuration/utilisateurs',
        permissions: ['utilisateurs_apercu', 'administrateur_tabsConfig', 'enseignants_tabsConfig', 'parents_tabsConfig', 'dae_apercu', 'dcap_apercu'],
      },
      { labelKey: 'plus.permissions', icon: 'shield-key-outline', href: '/plus/configuration/permissions', permissions: ['permissions_apercu', 'permission_voir'] },
      { labelKey: 'plus.schools', icon: 'domain', href: '/plus/configuration/ecoles', permissions: ['ecoles_apercu'] },
      { labelKey: 'plus.school_years', icon: 'calendar-range', href: '/plus/configuration/annees', permissions: ['annees_scolaires_apercu'] },
      { labelKey: 'plus.academies', icon: 'city-variant-outline', href: '/plus/configuration/academies', permissions: ['academies_apercu'] },
      { labelKey: 'plus.caps', icon: 'office-building-outline', href: '/plus/configuration/caps', permissions: ['dcap_apercu'] },
      { labelKey: 'plus.grade_types', icon: 'numeric', href: '/plus/configuration/types-notes', permissions: ['types_notes_apercu'] },
      { labelKey: 'plus.official_classes', icon: 'google-classroom', href: '/plus/configuration/classes-officielles', permissions: ['classes_officielles_apercu'] },
      { labelKey: 'plus.control_statuses', icon: 'flag-outline', href: '/plus/configuration/status-controles', permissions: ['status_controles_apercu'] },
      {
        labelKey: 'plus.subscription',
        icon: 'star-circle',
        href: '/plus/abonnement',
        permissions: ['abonnements_apercu', 'abonnements_paiement', 'abonnements_configuration', 'abonnements_validation'],
      },
    ],
  },
];
