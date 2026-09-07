export type Droit = 'SupAdmin' | 'DAE' | 'DCAP' | 'Admin' | 'Gestionnaire' | 'enseignant' | 'parent';

export interface Ecole {
  id: number;
  nom: string;
  type: string;
  logo: string | null;
}

export interface User {
  id: number;
  nom_prenom: string;
  email: string | null;
  telephone: string | null;
  fonction: string | null;
  genre: string | null;
  droit: Droit;
  statut: number;
  theme_preference: string | null;
  locale_preference: string | null;
  ecole: Ecole | null;
}

export interface AccountChoice {
  id_utilisateur: number;
  id_ecole: number | null;
  nom_ecole: string | null;
  droit: Droit;
}

export interface LoginSuccess {
  token: string;
  user: User;
  subscription_blocked: boolean;
}

export interface LoginRequiresSchoolSelection {
  requires_school_selection: true;
  accounts: AccountChoice[];
}

export type LoginResponse = LoginSuccess | LoginRequiresSchoolSelection;

export function isLoginSuccess(response: LoginResponse): response is LoginSuccess {
  return 'token' in response;
}

export interface ApiNotification {
  id: number;
  type: string;
  title: string;
  message: string;
  link: string | null;
  data: Record<string, unknown> | null;
  read_at: string | null;
  created_at: string | null;
  updated_at: string | null;
}
