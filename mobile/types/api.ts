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
  permissions: string[];
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

export interface ClasseOfficielle {
  id_classe_officielle: number;
  nom_classe_officielle: string;
  ordre_enseignement: string;
}

export interface Classe {
  id_classe: number;
  nom_classe: string;
  ordreEnseignement: string;
  idEcole: number;
  eleves_count?: number;
  classeOfficielle?: ClasseOfficielle | null;
  ligneClasses?: LigneClasse[];
  [key: string]: unknown;
}

export interface LigneClasse {
  id_ligneclasse: number;
  id_classe: number;
  id_matiere: number;
  id_enseignants: number | null;
  coefficient: number;
  matiere?: Matiere;
  enseignant?: Enseignant;
  classe?: Classe;
  [key: string]: unknown;
}

export interface Matiere {
  id_matiere: number;
  nom_matiere: string;
  ordres?: { ordre_enseignement: string }[];
  [key: string]: unknown;
}

export interface Trimestre {
  id_trimestre: number;
  nom_trimestre?: string;
  [key: string]: unknown;
}

export interface AnneeScolaire {
  id_anneeScolaire: number;
  annee: string;
  date_debut: string;
  date_fin: string;
  [key: string]: unknown;
}

export interface Enseignant {
  id_enseignant: number;
  nom_prenom_enseignant: string;
  email_enseignant: string | null;
  telephone_enseignant: string | null;
  genre_enseignant: string | null;
  matricule: string | null;
  is_deleted?: number;
  [key: string]: unknown;
}

export interface ParentEleve {
  id_parent: number;
  nom_prenom_parent: string;
  email_parent?: string | null;
  telephone_parent?: string | null;
  genre?: string | null;
  eleves_count?: number;
  [key: string]: unknown;
}

export interface Planification {
  id_planification: number;
  id_classe: number;
  id_annee: number;
  motif: string;
  montant_planification: number | string;
  [key: string]: unknown;
}

export interface Eleve {
  id_eleve: number;
  nom_eleve: string;
  prenom_eleve: string;
  matricule: string | null;
  genre_eleve: string | null;
  date_naissance: string | null;
  lieu_naiss: string | null;
  adresse_eleve: string | null;
  cas_social: string | null;
  mode_paiement: string | null;
  statut_paiement: string | null;
  id_classe: number;
  id_annee: number;
  date_inscription: string | null;
  etat_dossier: number;
  classe?: Classe;
  [key: string]: unknown;
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
