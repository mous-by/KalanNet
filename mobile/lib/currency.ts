import type { Devise, User } from '@/types/api';

// Repli si l'ecole/la devise n'est pas encore chargee (ex: tout premier
// rendu avant que /auth/me ait repondu) — miroir du repli Mali cote backend
// (voir App\Support\Devise::mali()), pour ne jamais rien afficher de faux.
const DEVISE_PAR_DEFAUT: Devise = { code: 'XOF', symbole: 'FCFA', decimales: 0 };

export function deviseDe(user: User | null | undefined): Devise {
  return user?.ecole?.devise ?? DEVISE_PAR_DEFAUT;
}

export function formatMontant(montant: number, user: User | null | undefined): string {
  const devise = deviseDe(user);
  const formatte = Number(montant).toLocaleString('fr-FR', {
    minimumFractionDigits: devise.decimales,
    maximumFractionDigits: devise.decimales,
  });

  return `${formatte} ${devise.symbole}`;
}
