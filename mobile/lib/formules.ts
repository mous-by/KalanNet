// Formules de paiement (frais scolaires) : types et libellés partagés par les écrans Finances.

export interface Tranche {
  libelle: string;
  montant: number;
  date_limite: string;
}

export interface Formule {
  id: number;
  motif: string;
  date_debut: string;
  date_fin: string;
  montant: number;
  eleves_lies: number;
  tranches: Tranche[];
}

export interface PlanificationsReponse {
  classes: { id_classe: number; nom_classe: string }[];
  annees: { id: number; annee: string; date_debut: string | null; date_fin: string | null }[];
  annee_courante: number | null;
  est_publique: boolean;
  tranches_min: number;
  tranches_max: number;
  peut: { creer: boolean; modifier: boolean; supprimer: boolean };
  planifications: Formule[];
}

export const LIBELLES_MOTIF: Record<string, string> = {
  mensuelle: 'Mensuelle',
  trimestrielle: 'Trimestrielle',
  annuelle: 'Annuelle',
  cooperative: 'Coopérative',
};
