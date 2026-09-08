import { TranslationKey } from '@/lib/i18n';
import { Droit } from '@/types/api';

export interface MenuItem {
  labelKey: TranslationKey;
  icon: string;
  href: string;
  roles?: Droit[];
}

export interface MenuSection {
  titleKey: TranslationKey;
  items: MenuItem[];
}

export const MENU_SECTIONS: MenuSection[] = [
  {
    titleKey: 'plus.section_sync',
    items: [{ labelKey: 'plus.synchronisation', icon: 'cloud-sync-outline', href: '/plus/synchronisation' }],
  },
  {
    titleKey: 'plus.section_pedagogy',
    items: [
      { labelKey: 'plus.teachers', icon: 'account-tie', href: '/plus/enseignants', roles: ['SupAdmin', 'Admin', 'Gestionnaire', 'DAE', 'DCAP'] },
      { labelKey: 'plus.timetable', icon: 'calendar-clock', href: '/plus/timetable' },
      { labelKey: 'plus.evaluations', icon: 'clipboard-text', href: '/plus/evaluations' },
      { labelKey: 'plus.emargements', icon: 'notebook-check', href: '/plus/emargements', roles: ['SupAdmin', 'Admin', 'Gestionnaire', 'enseignant'] },
      { labelKey: 'plus.presences', icon: 'account-check', href: '/plus/presences', roles: ['SupAdmin', 'Admin', 'Gestionnaire', 'enseignant'] },
      { labelKey: 'plus.bulletins', icon: 'file-document', href: '/plus/bulletins', roles: ['SupAdmin', 'Admin', 'Gestionnaire', 'DAE', 'DCAP'] },
      { labelKey: 'plus.national_results', icon: 'school', href: '/plus/resultats-nationaux', roles: ['SupAdmin', 'Admin', 'Gestionnaire', 'DAE', 'DCAP'] },
      { labelKey: 'plus.exam_calls', icon: 'alert-circle-outline', href: '/plus/appels-epreuves/new', roles: ['SupAdmin', 'Admin', 'Gestionnaire', 'enseignant'] },
    ],
  },
  {
    titleKey: 'plus.section_finances',
    items: [
      { labelKey: 'plus.payments', icon: 'cash-multiple', href: '/plus/finances', roles: ['SupAdmin', 'Admin', 'Gestionnaire'] },
      { labelKey: 'plus.salaries', icon: 'wallet', href: '/plus/salaires', roles: ['SupAdmin', 'Admin', 'Gestionnaire'] },
    ],
  },
  {
    titleKey: 'plus.section_communication',
    items: [{ labelKey: 'plus.announcements', icon: 'bullhorn', href: '/plus/annonces' }],
  },
  {
    titleKey: 'plus.section_admin',
    items: [
      { labelKey: 'plus.users', icon: 'account-group', href: '/plus/configuration/utilisateurs', roles: ['SupAdmin', 'Admin', 'DAE', 'DCAP'] },
      { labelKey: 'plus.permissions', icon: 'shield-key-outline', href: '/plus/configuration/permissions', roles: ['SupAdmin'] },
      { labelKey: 'plus.schools', icon: 'domain', href: '/plus/configuration/ecoles', roles: ['SupAdmin'] },
      { labelKey: 'plus.school_years', icon: 'calendar-range', href: '/plus/configuration/annees', roles: ['SupAdmin', 'Admin', 'Gestionnaire'] },
      { labelKey: 'plus.academies', icon: 'city-variant-outline', href: '/plus/configuration/academies', roles: ['SupAdmin', 'DAE'] },
      { labelKey: 'plus.caps', icon: 'office-building-outline', href: '/plus/configuration/caps', roles: ['SupAdmin', 'DAE', 'DCAP'] },
      { labelKey: 'plus.grade_types', icon: 'numeric', href: '/plus/configuration/types-notes', roles: ['SupAdmin', 'Admin', 'Gestionnaire'] },
      { labelKey: 'plus.official_classes', icon: 'google-classroom', href: '/plus/configuration/classes-officielles', roles: ['SupAdmin', 'Admin', 'Gestionnaire'] },
      { labelKey: 'plus.control_statuses', icon: 'flag-outline', href: '/plus/configuration/status-controles', roles: ['SupAdmin', 'Admin', 'Gestionnaire'] },
      { labelKey: 'plus.subscription', icon: 'star-circle', href: '/plus/abonnement', roles: ['SupAdmin', 'Admin'] },
    ],
  },
];
