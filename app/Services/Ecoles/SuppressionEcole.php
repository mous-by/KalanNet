<?php

namespace App\Services\Ecoles;

use App\Models\Ecole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

/**
 * Suppression définitive d'une école et de TOUT ce qui lui est rattaché :
 * classes, élèves, parents, enseignants, comptes, notes, présences, paiements,
 * caisse, abonnement, annonces, fichiers (photos, logo, reçus)…
 *
 * Restent intacts : les comptes SupAdmin / revendeur / DAE / DCAP (seulement
 * détachés de l'école), le référentiel national (classes et programmes
 * officiels) et les matières communes. Tout se fait dans une transaction :
 * en cas d'erreur, rien n'est supprimé.
 */
class SuppressionEcole
{
    /** Comptes qui ne dépendent pas d'une école : détachés, jamais supprimés. */
    private const COMPTES_PLATEFORME = ['SupAdmin', 'revendeur', 'DAE', 'DCAP'];

    /** Dossiers (dans public/) des fichiers propres à une école. */
    private const DOSSIERS_FICHIERS = ['image_eleves/', 'images_enseignant/', 'images_utilisateurs/', 'images_ecoles/', 'uploads/'];

    /** Ce qui sera supprimé, pour la page de confirmation : libellé => nombre. */
    public function inventaire(Ecole $ecole): array
    {
        $ids = $this->identifiants($ecole);

        return array_filter([
            'Classes' => count($ids['classes']),
            'Élèves' => count($ids['eleves']),
            'Parents' => count($ids['parents']),
            'Enseignants' => count($ids['enseignants']),
            'Comptes de connexion' => count($ids['utilisateurs']),
            'Notes' => $this->compter('ligne_evaluation', ['id_classe' => $ids['classes'], 'id_eleve' => $ids['eleves']]),
            'Présences et émargements' => $this->compter('presences', ['id_presence' => $ids['presences']])
                + $this->compter('emargement', ['id_ecole' => [$ecole->idEcole], 'id_classe' => $ids['classes'], 'id_enseignant' => $ids['enseignants']]),
            'Paiements de frais scolaires' => $this->compter('paiement', ['idEcole' => [$ecole->idEcole], 'id_eleve' => $ids['eleves']]),
            'Formules de paiement' => count($ids['planifications']),
            'Opérations de caisse' => $this->compter('encaissement', ['id_caisse' => $ids['caisses']]) + $this->compter('decaissement', ['id_caisse' => $ids['caisses']]),
            'Salaires' => count($ids['salaires']),
            'Annonces' => count($ids['annonces']),
            'Paiements d\'abonnement' => $this->compter('abonnement_paiements', ['ecole_id' => [$ecole->idEcole]]),
        ]);
    }

    /** Supprime l'école et tout ce qui s'y rattache. Retourne le nombre de lignes supprimées. */
    public function supprimer(Ecole $ecole): int
    {
        $ids = $this->identifiants($ecole);
        $fichiers = $this->fichiers($ecole, $ids);
        $e = [(int) $ecole->idEcole];
        $total = 0;

        DB::transaction(function () use ($ids, $e, &$total) {
            // Ordre sans importance : tout le sous-ensemble disparaît ensemble.
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            try {
                $suppressions = [
                    // Pédagogie
                    ['lecons_presence', ['id_presence' => $ids['presences']]],
                    ['presences', ['id_presence' => $ids['presences']]],
                    ['emargement', ['id_ecole' => $e, 'id_classe' => $ids['classes'], 'id_enseignant' => $ids['enseignants']]],
                    ['emploi_du_temps', ['id_classe' => $ids['classes'], 'id_enseignant' => $ids['enseignants']]],
                    ['evaluationprof', ['id_enseignant' => $ids['enseignants']]],
                    ['ligne_evaluation', ['id_classe' => $ids['classes'], 'id_eleve' => $ids['eleves'], 'id_enseignant' => $ids['enseignants']]],
                    ['moyenne_eleve', ['id_classe' => $ids['classes'], 'id_eleve' => $ids['eleves']]],
                    ['conduite', ['id_classe' => $ids['classes'], 'id_eleve' => $ids['eleves']]],
                    ['resultats_def_terminal', ['id_classe' => $ids['classes'], 'id_eleve' => $ids['eleves']]],
                    ['bulletin', ['id_eleve' => $ids['eleves']]],
                    ['bulletin_publications', ['id_ecole' => $e, 'id_classe' => $ids['classes']]],
                    ['controle_eleve', ['id_ecole' => $e, 'id_classe' => $ids['classes'], 'id_eleve' => $ids['eleves']]],
                    ['controle', ['id_ecole' => $e, 'id_eleve' => $ids['eleves']]],
                    ['indiscipline', ['id_eleve' => $ids['eleves']]],
                    ['note', ['id_ecole' => $e]],
                    ['ligneclasse', ['id_classe' => $ids['classes']]],
                    // Inscriptions et élèves
                    ['ligne_inscription', ['id_classe' => $ids['classes'], 'id_eleve' => $ids['eleves']]],
                    ['ligne_reinscription', ['id_classe' => $ids['classes'], 'id_eleve' => $ids['eleves']]],
                    ['inscription', ['id_eleve' => $ids['eleves']]],
                    ['transfert', ['id_ecole' => $e, 'id_eleve' => $ids['eleves']]],
                    ['subvention_etat_eleve', ['id_ecole' => $e, 'id_eleve' => $ids['eleves']]],
                    ['ligneparents_eleves', ['id_eleve' => $ids['eleves'], 'id_parent' => $ids['parents']]],
                    // Finances
                    ['ligne_paiement_eleve', ['idEcole' => $e, 'id_classe' => $ids['classes'], 'id_eleve' => $ids['eleves']]],
                    ['echeances_paiement', ['plan_paiement_id' => $ids['plans']]],
                    ['plans_paiement', ['id' => $ids['plans']]],
                    ['frais_scolaires', ['ecole_id' => $e]],
                    ['reduction_paiement_configs', ['ecole_id' => $e]],
                    ['paiement', ['idEcole' => $e, 'id_eleve' => $ids['eleves'], 'id_classe' => $ids['classes']]],
                    ['paiement_sequences', ['ecole_id' => $e]],
                    ['planification_tranches', ['id_planification' => $ids['planifications']]],
                    ['planification', ['id_planification' => $ids['planifications']]],
                    ['ligne_salaire', ['id_salaire' => $ids['salaires']]],
                    ['salaire', ['id_salaire' => $ids['salaires']]],
                    ['encaissement', ['id_caisse' => $ids['caisses'], 'idUtilisateur' => $ids['utilisateurs']]],
                    ['decaissement', ['id_caisse' => $ids['caisses'], 'idUtilisateur' => $ids['utilisateurs']]],
                    ['versement', ['idUtilisateur' => $ids['utilisateurs']]],
                    ['retrait', ['idUtilisateur' => $ids['utilisateurs']]],
                    ['caisse', ['id_caisse' => $ids['caisses']]],
                    ['banques', ['id_ecole' => $e]],
                    ['abonnement_paiements', ['ecole_id' => $e]],
                    ['abonnements', ['ecole_id' => $e]],
                    // Communication
                    ['annonces_fichiers', ['id_annonce' => $ids['annonces']]],
                    ['annonces_lues', ['id_utilisateur' => $ids['utilisateurs']]],
                    ['annonces_admin_gestionnaire', ['id_annonce' => $ids['annonces']]],
                    ['notifications', ['id_ecole' => $e]],
                    ['app_notifications', ['user_id' => $ids['utilisateurs']]],
                    ['kalanbot_actions_log', ['id_ecole' => $e, 'user_id' => $ids['utilisateurs']]],
                    // Comptes
                    ['user_permission', ['user_id' => $ids['utilisateurs']]],
                    ['user_role', ['user_id' => $ids['utilisateurs']]],
                    ['sessions', ['user_id' => $ids['utilisateurs']]],
                    ['utilisateurs', ['idUtilisateur' => $ids['utilisateurs']]],
                    // Référentiels de l'école
                    ['matiere_ordre', ['id_matiere' => $ids['matieres']]],
                    ['matiere', ['id_matiere' => $ids['matieres']]],
                    ['eleve', ['id_eleve' => $ids['eleves']]],
                    ['classe', ['id_classe' => $ids['classes']]],
                    ['enseignants', ['id_enseignant' => $ids['enseignants']]],
                    ['parents', ['id_parent' => $ids['parents']]],
                    ['filieres', ['id_ecole' => $e]],
                    ['trimestre', ['id_ecole' => $e]],
                    ['anneescolaire', ['id_ecole' => $e]],
                ];

                foreach ($suppressions as [$table, $conditions]) {
                    $total += $this->requete($table, $conditions)?->delete() ?? 0;
                }

                // Jetons de connexion mobile des comptes supprimés.
                if ($ids['utilisateurs'] !== []) {
                    $total += DB::table('personal_access_tokens')
                        ->where('tokenable_type', \App\Models\User::class)
                        ->whereIn('tokenable_id', $ids['utilisateurs'])
                        ->delete();
                }

                // Comptes plateforme rattachés à l'école : détachés, pas supprimés.
                DB::table('utilisateurs')->whereIn('idEcole', $e)->update(['idEcole' => null]);

                $total += DB::table('ecole')->where('idEcole', $e[0])->delete();
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        });

        // Fichiers : seulement une fois la suppression en base validée.
        foreach ($fichiers as $chemin) {
            File::delete($chemin);
        }

        return $total;
    }

    /** @return array<string, int[]> */
    private function identifiants(Ecole $ecole): array
    {
        $id = (int) $ecole->idEcole;
        $pluck = fn (string $table, string $col, callable $where) => Schema::hasTable($table) && Schema::hasColumn($table, $col)
            ? DB::table($table)->where($where)->pluck($col)->map(fn ($v) => (int) $v)->unique()->values()->all()
            : [];

        $classes = $pluck('classe', 'id_classe', fn ($q) => $q->where('idEcole', $id));
        $eleves = $pluck('eleve', 'id_eleve', fn ($q) => $q->where('id_ecole', $id)->orWhereIn('id_classe', $classes ?: [0]));
        $enseignants = $pluck('enseignants', 'id_enseignant', fn ($q) => $q->where('id_ecole', $id));
        $parents = $pluck('parents', 'id_parent', fn ($q) => $q->where('idEcole', $id));
        $utilisateurs = $pluck('utilisateurs', 'idUtilisateur', fn ($q) => $q
            ->where(fn ($inner) => $inner->where('idEcole', $id)
                ->orWhereIn('id_enseignant', $enseignants ?: [0])
                ->orWhereIn('id_parent', $parents ?: [0]))
            ->where(fn ($inner) => $inner->whereNull('droit')->orWhereNotIn('droit', self::COMPTES_PLATEFORME)));

        return [
            'classes' => $classes,
            'eleves' => $eleves,
            'enseignants' => $enseignants,
            'parents' => $parents,
            'utilisateurs' => $utilisateurs,
            'caisses' => $pluck('caisse', 'id_caisse', fn ($q) => $q->where('id_ecole', $id)),
            'planifications' => $pluck('planification', 'id_planification', fn ($q) => $q->whereIn('id_classe', $classes ?: [0])),
            'presences' => $pluck('presences', 'id_presence', fn ($q) => $q->where('id_ecole', $id)->orWhereIn('id_classe', $classes ?: [0])->orWhereIn('id_enseignant', $enseignants ?: [0])),
            'salaires' => $pluck('salaire', 'id_salaire', fn ($q) => $q->whereIn('id_enseignant', $enseignants ?: [0])),
            'annonces' => $pluck('annonces_admin_gestionnaire', 'id_annonce', fn ($q) => $q->where('id_ecole', $id)->orWhereIn('id_utilisateur', $utilisateurs ?: [0])),
            'matieres' => $pluck('matiere', 'id_matiere', fn ($q) => $q->where('id_ecole', $id)),
            'plans' => $pluck('plans_paiement', 'id', fn ($q) => $q->where('ecole_id', $id)->orWhereIn('eleve_id', $eleves ?: [0])),
        ];
    }

    /** Requête « table où colonne1 IN (...) OU colonne2 IN (...) », null si rien à cibler. */
    private function requete(string $table, array $conditions)
    {
        // Table ou colonne absente (base plus ancienne) : simplement ignorée.
        if (!Schema::hasTable($table)) {
            return null;
        }
        $conditions = array_filter($conditions, fn ($valeurs, $colonne) => $valeurs !== [] && Schema::hasColumn($table, $colonne), ARRAY_FILTER_USE_BOTH);
        if ($conditions === []) {
            return null;
        }

        return DB::table($table)->where(function ($q) use ($conditions) {
            foreach ($conditions as $colonne => $valeurs) {
                $q->orWhereIn($colonne, $valeurs);
            }
        });
    }

    private function compter(string $table, array $conditions): int
    {
        return $this->requete($table, $conditions)?->count() ?? 0;
    }

    /** Fichiers propres à l'école (photos, logo, reçus, pièces jointes), à effacer du disque. */
    private function fichiers(Ecole $ecole, array $ids): array
    {
        $chemins = collect([$ecole->logoEcole])
            ->merge(DB::table('eleve')->whereIn('id_eleve', $ids['eleves'] ?: [0])->pluck('image'))
            ->merge(DB::table('enseignants')->whereIn('id_enseignant', $ids['enseignants'] ?: [0])->pluck('avatar_enseignant'))
            ->merge(DB::table('utilisateurs')->whereIn('idUtilisateur', $ids['utilisateurs'] ?: [0])->pluck('image'))
            ->merge(DB::table('abonnement_paiements')->where('ecole_id', $ecole->idEcole)->pluck('preuve_url'))
            ->merge(DB::table('annonces_admin_gestionnaire')->whereIn('id_annonce', $ids['annonces'] ?: [0])->pluck('fichier_joint'));

        return $chemins
            ->filter()
            ->map(fn ($chemin) => ltrim((string) $chemin, '/'))
            // Jamais les images par défaut partagées, seulement les dossiers d'envoi.
            ->filter(fn ($chemin) => collect(self::DOSSIERS_FICHIERS)->contains(fn ($dossier) => str_starts_with($chemin, $dossier)))
            ->reject(fn ($chemin) => str_contains($chemin, '..'))
            ->map(fn ($chemin) => public_path($chemin))
            ->filter(fn ($chemin) => is_file($chemin))
            ->unique()
            ->values()
            ->all();
    }
}
