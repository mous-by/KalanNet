<?php

namespace App\Console\Commands;

use App\Http\Controllers\ConfigurationController;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Crée (ou complète) l'école de démonstration et le compte utilisé par les
 * examinateurs Google Play : à lancer une fois par environnement. Relancée,
 * elle ne crée rien en double. Le mot de passe n'est jamais écrit dans le code.
 *
 *   php artisan kalannet:ecole-demo
 */
class CreerEcoleDemo extends Command
{
    protected $signature = 'kalannet:ecole-demo {--password= : Mot de passe du compte démo (demandé si absent)}';

    protected $description = "Crée l'école de démonstration KalanNet et le compte Google Play (playstore.demo@kalannet.com)";

    private const NOM_ECOLE = 'Ecole Demonstration KalanNet';

    private const EMAIL_COMPTE = 'playstore.demo@kalannet.com';

    private const ELEVES = [
        ['Aminata', 'Konate', 'Feminin', 'DEMO-KON781'],
        ['Moussa', 'Sangare', 'Masculin', 'DEMO-SAN785'],
        ['Fatoumata', 'Coulibaly', 'Feminin', 'DEMO-COU398'],
    ];

    public function handle(): int
    {
        $compteExistant = User::withoutGlobalScopes()->where('email', self::EMAIL_COMPTE)->first();
        $motDePasse = $this->option('password')
            ?: ($compteExistant ? null : $this->secret('Mot de passe du compte '.self::EMAIL_COMPTE));
        if (!$compteExistant && (!$motDePasse || strlen($motDePasse) < 8)) {
            $this->error('Mot de passe obligatoire (8 caractères minimum).');

            return self::FAILURE;
        }

        // Références retrouvées par leur nom : les identifiants diffèrent d'une base à l'autre.
        $paysId = DB::table('pays')->where('code_iso', 'ML')->value('id');
        $academie = DB::table('academie')->where('nom_academie', 'AE de Kayes')->first();
        $cap = DB::table('cap')->where('nom_cap', 'CAP de Kayes Rives-Droite')->first();
        $classeOfficielle = DB::table('classes_officielles')->where('nom_classe_officielle', '6eme année')
            ->where('ordre_enseignement', 'fondamentale1')->where('id_pays', $paysId)->value('id_classe_officielle');
        $anneeId = DB::table('anneescolaire')->whereNull('id_ecole')->where('annee', '2026-2027')->value('id_anneeScolaire')
            ?? DB::table('anneescolaire')->whereNull('id_ecole')->orderByDesc('id_anneeScolaire')->value('id_anneeScolaire');
        $offreId = DB::table('abonnement_offres')->where('code', 'achat')->value('id');

        DB::transaction(function () use ($paysId, $academie, $cap, $classeOfficielle, $anneeId, $offreId, $compteExistant, $motDePasse) {
            // 1. École.
            $ecoleId = DB::table('ecole')->where('nomEcole', self::NOM_ECOLE)->value('idEcole');
            if (!$ecoleId) {
                $ecoleId = DB::table('ecole')->insertGetId([
                    'nomEcole' => self::NOM_ECOLE,
                    'typeEcole' => 'Collège',
                    'statut' => 'public',
                    'id_pays' => $paysId,
                    'id_academie' => $academie?->id_academie,
                    'academie' => $academie?->nom_academie,
                    'id_cap' => $cap?->id_cap,
                    'cap' => $cap?->nom_cap,
                    'adresse' => 'Bamako, Mali',
                    'telephone' => '70000000',
                    'email' => 'demo@kalannet.com',
                    'notification_sms' => 0,
                    'notification_email' => 0,
                    'franco_arabe' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->info("École créée (#{$ecoleId}).");
            } else {
                $this->line("École déjà présente (#{$ecoleId}).");
            }

            // 2. Classe.
            $classeId = DB::table('classe')->where('idEcole', $ecoleId)->where('nom_classe', '6eme annee A')->value('id_classe')
                ?: DB::table('classe')->insertGetId([
                    'nom_classe' => '6eme annee A',
                    'ordreEnseignement' => 'fondamentale1',
                    'idEcole' => $ecoleId,
                    'id_classe_officielle' => $classeOfficielle,
                ]);

            // 3. Élèves.
            foreach (self::ELEVES as [$prenom, $nom, $genre, $matricule]) {
                if (DB::table('eleve')->where('matricule', $matricule)->exists()) {
                    continue;
                }
                $eleveId = DB::table('eleve')->insertGetId([
                    'prenom_eleve' => $prenom,
                    'nom_eleve' => $nom,
                    'genre_eleve' => $genre,
                    'lieu_naiss' => 'Bamako',
                    'matricule' => $matricule,
                    'id_classe' => $classeId,
                    'id_annee' => $anneeId,
                    'id_ecole' => $ecoleId,
                    'cas_social' => 'normal',
                    'statut_paiement' => 'normal',
                    'etat_dossier' => 0,
                    'image' => 'assets/images/avatars/avatar-1.png',
                    'date_inscription' => now()->toDateString(),
                ]);
                DB::table('ligne_inscription')->insert([
                    'id_eleve' => $eleveId,
                    'id_classe' => $classeId,
                    'id_annee' => $anneeId,
                    'id_planification' => null,
                    'date_inscription' => now()->toDateString(),
                ]);
            }

            // 4. Compte Google Play (Admin de l'école démo), avec les permissions par défaut d'un Admin.
            if (!$compteExistant) {
                $compte = User::withoutGlobalScopes()->create([
                    'nomPrenom' => 'Demo PlayStore',
                    'email' => self::EMAIL_COMPTE,
                    'fonction' => 'Promoteur',
                    'telephone' => '70000000',
                    'genre' => 'Masculin',
                    'droit' => 'Admin',
                    'idEcole' => $ecoleId,
                    'statut' => 1,
                    'image' => 'default.png',
                    'pwd' => Hash::make($motDePasse),
                ]);
                $this->info('Compte '.self::EMAIL_COMPTE.' créé.');
            } else {
                $compte = $compteExistant;
                $maj = ['idEcole' => $ecoleId, 'statut' => 1];
                if ($motDePasse) {
                    $maj['pwd'] = Hash::make($motDePasse);
                }
                DB::table('utilisateurs')->where('idUtilisateur', $compte->idUtilisateur)->update($maj);
                $this->line('Compte '.self::EMAIL_COMPTE.' déjà présent : rattaché à l\'école'.($motDePasse ? ', mot de passe mis à jour' : '').'.');
            }
            app(ConfigurationController::class)->syncDefaultPermissions($compte->fresh() ?? $compte, 1);

            // 5. Abonnement : licence à vie, pour que l'app reste utilisable par les examinateurs.
            if ($offreId && !DB::table('abonnements')->where('ecole_id', $ecoleId)->where('statut', 'actif')->exists()) {
                DB::table('abonnements')->insert([
                    'ecole_id' => $ecoleId,
                    'offre_id' => $offreId,
                    'statut' => 'actif',
                    'debut_at' => now(),
                    'fin_at' => now()->addYears(10),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        $this->info('École de démonstration prête.');

        return self::SUCCESS;
    }
}
