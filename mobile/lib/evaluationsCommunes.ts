/** Évaluation commune telle que la renvoie l'API (EvaluationsCommunes::enrichir côté serveur). */
export interface EvaluationCommune {
  id: number;
  titre: string;
  origine: 'academie' | 'cap';
  niveau_libelle: string | null;
  matiere_libelle: string | null;
  debut_at: string;
  fin_at: string;
  duree_minutes: number;
  liberation_sujet_at: string;
  consignes: string | null;
  consignes_cap?: string | null;
  sujet_nom: string | null;
  corrige_nom: string | null;
  statut: string;
  etat: string;
  portee?: string;
  sujet_disponible: boolean;
  corrige_disponible: boolean;
  telecharge_at?: string | null;
}

/** « 2026-11-03 08:00:00 » ou ISO → « 03/11/2026 à 08:00 ». */
export function dateHeure(valeur: string | null | undefined): string {
  if (!valeur) return '—';
  const d = new Date(valeur.replace(' ', 'T'));
  if (Number.isNaN(d.getTime())) return valeur;
  const deux = (n: number) => String(n).padStart(2, '0');
  return `${deux(d.getDate())}/${deux(d.getMonth() + 1)}/${d.getFullYear()} à ${deux(d.getHours())}:${deux(d.getMinutes())}`;
}

/** Nom de fichier sûr pour le partage (sans caractères spéciaux). */
export function nomFichier(titre: string, piece: string, original: string | null): string {
  const extension = (original ?? '').split('.').pop() || 'pdf';
  return `${titre.replace(/[^\p{L}\p{N}]+/gu, '_').slice(0, 40)}_${piece}.${extension}`;
}
