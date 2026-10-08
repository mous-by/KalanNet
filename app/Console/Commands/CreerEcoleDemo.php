<?php

namespace App\Console\Commands;

use App\Http\Controllers\ConfigurationController;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Crée (ou complète) une école de démonstration et le compte utilisé par les
 * examinateurs Google Play : à lancer une fois par environnement. Relancée,
 * elle ne crée rien en double. Le mot de passe n'est jamais écrit dans le code.
 *
 *   php artisan kalannet:ecole-demo            école publique (Collège)
 *   php artisan kalannet:ecole-demo --privee   école privée (Complexe Scolaire)
 */
class CreerEcoleDemo extends Command
{
    protected $signature = 'kalannet:ecole-demo
        {--privee : École privée (Complexe Scolaire) au lieu de l\'école publique}
        {--password= : Mot de passe du compte démo (demandé si absent)}
        {--fin=2026-10-31 : Date de fin de l\'abonnement (AAAA-MM-JJ)}';

    protected $description = "Crée une école de démonstration KalanNet (publique, ou privée avec --privee) et son compte Google Play";

    private const PROFILS = [
        'publique' => [
            'ecole' => [
                'nomEcole' => 'Ecole Demonstration KalanNet',
                'typeEcole' => 'Collège',
                'statut' => 'public',
                'email' => 'demo@kalannet.com',
            ],
            'compte' => 'playstore.demo@kalannet.com',
            // Classes : nom => [ordre d'enseignement, classe officielle, formule annuelle (école privée)].
            'classes' => [
                '6eme annee A' => ['fondamentale1', '6eme année', null],
                '7eme annee A' => ['fondamentale2', '7eme année', null],
            ],
            // Élèves : [prénom, nom, genre, matricule, classe].
            'eleves' => [
                ['Aminata', 'Konate', 'Feminin', 'DEMO-KON781', '6eme annee A'],
                ['Moussa', 'Sangare', 'Masculin', 'DEMO-SAN785', '6eme annee A'],
                ['Fatoumata', 'Coulibaly', 'Feminin', 'DEMO-COU398', '6eme annee A'],
                ['Ibrahim', 'Traore', 'Masculin', 'DEMO-TRA701', '7eme annee A'],
                ['Mariam', 'Diarra', 'Feminin', 'DEMO-DIA702', '7eme annee A'],
                ['Oumar', 'Keita', 'Masculin', 'DEMO-KEI703', '7eme annee A'],
            ],
        ],
        'privee' => [
            'ecole' => [
                'nomEcole' => 'Complexe Scolaire Demonstration KalanNet',
                'typeEcole' => 'Complexe Scolaire',
                'statut' => 'prive',
                'email' => 'demo.prive@kalannet.com',
                'nomComplexe' => 'Complexe Scolaire Demonstration KalanNet',
                'nomFondamental' => 'Ecole Fondamentale Demo',
                'nomLycee' => 'Lycee Demo',
            ],
            'compte' => 'playstore.prive@kalannet.com',
            'classes' => [
                '6eme annee A' => ['fondamentale1', '6eme année', 75000],
                '7eme annee A' => ['fondamentale2', '7eme année', 90000],
                '10eme annee commune' => ['secondairegenerale', '10eme année commune', 120000],
            ],
            'eleves' => [
                ['Awa', 'Dembele', 'Feminin', 'DEMOP-DEM601', '6eme annee A'],
                ['Seydou', 'Coulibaly', 'Masculin', 'DEMOP-COU602', '6eme annee A'],
                ['Kadiatou', 'Sissoko', 'Feminin', 'DEMOP-SIS701', '7eme annee A'],
                ['Bakary', 'Kone', 'Masculin', 'DEMOP-KON702', '7eme annee A'],
                ['Salimata', 'Toure', 'Feminin', 'DEMOP-TOU101', '10eme annee commune'],
                ['Adama', 'Camara', 'Masculin', 'DEMOP-CAM102', '10eme annee commune'],
            ],
        ],
    ];

    public function handle(): int
    {
        $profil = self::PROFILS[$this->option('privee') ? 'privee' : 'publique'];
        $email = $profil['compte'];
        $prive = $profil['ecole']['statut'] === 'prive';

        $compteExistant = User::withoutGlobalScopes()->where('email', $email)->first();
        $motDePasse = $this->option('password')
            ?: ($compteExistant ? null : $this->secret('Mot de passe du compte '.$email));
        if (!$compteExistant && (!$motDePasse || strlen($motDePasse) < 8)) {
            $this->error('Mot de passe obligatoire (8 caractères minimum).');

            return self::FAILURE;
        }

        try {
            $fin = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', (string) $this->option('fin'))->endOfDay();
        } catch (\Throwable) {
            $this->error('Date de fin invalide : utilisez le format AAAA-MM-JJ, par exemple 2026-10-31.');

            return self::FAILURE;
        }

        // Références retrouvées par leur nom : les identifiants diffèrent d'une base à l'autre.
        $paysId = DB::table('pays')->where('code_iso', 'ML')->value('id');
        $academie = DB::table('academie')->where('nom_academie', 'AE de Kayes')->first();
        $cap = DB::table('cap')->where('nom_cap', 'CAP de Kayes Rives-Droite')->first();
        $annee = DB::table('anneescolaire')->whereNull('id_ecole')->where('annee', '2026-2027')->first()
            ?? DB::table('anneescolaire')->whereNull('id_ecole')->orderByDesc('id_anneeScolaire')->first();
        // Abonnement mensuel (à défaut, une autre formule active à durée limitée).
        $offreId = DB::table('abonnement_offres')->where('code', 'mensuel')->value('id')
            ?? DB::table('abonnement_offres')->where('actif', 1)->where('duree_jours', '>', 0)->value('id')
            ?? DB::table('abonnement_offres')->where('actif', 1)->value('id');

        DB::transaction(function () use ($profil, $email, $prive, $paysId, $academie, $cap, $annee, $offreId, $fin, $compteExistant, $motDePasse) {
            $anneeId = $annee->id_anneeScolaire;

            // 1. École.
            $nomEcole = $profil['ecole']['nomEcole'];
            $ecoleId = DB::table('ecole')->where('nomEcole', $nomEcole)->value('idEcole');
            if (!$ecoleId) {
                $ecoleId = DB::table('ecole')->insertGetId($profil['ecole'] + [
                    'id_pays' => $paysId,
                    'id_academie' => $academie?->id_academie,
                    'academie' => $academie?->nom_academie,
                    'id_cap' => $cap?->id_cap,
                    'cap' => $cap?->nom_cap,
                    'adresse' => 'Bamako, Mali',
                    'telephone' => '70000000',
                    'notification_sms' => 0,
                    'notification_email' => 0,
                    'franco_arabe' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->info("École « {$nomEcole} » créée (#{$ecoleId}).");
            } else {
                $this->line("École « {$nomEcole} » déjà présente (#{$ecoleId}).");
            }

            // 2. Classes, et pour une école privée leur formule annuelle (une classe manquante est ajoutée).
            $classes = [];
            $formules = [];
            foreach ($profil['classes'] as $nomClasse => [$ordre, $officielle, $montantAnnuel]) {
                $classeId = DB::table('classe')->where('idEcole', $ecoleId)->where('nom_classe', $nomClasse)->value('id_classe')
                    ?: DB::table('classe')->insertGetId([
                        'nom_classe' => $nomClasse,
                        'ordreEnseignement' => $ordre,
                        'idEcole' => $ecoleId,
                        // Nom officiel parfois saisi avec une espace en trop.
                        'id_classe_officielle' => DB::table('classes_officielles')->where('id_pays', $paysId)
                            ->where('ordre_enseignement', $ordre)->whereRaw('TRIM(nom_classe_officielle) = ?', [$officielle])
                            ->value('id_classe_officielle'),
                    ]);
                $classes[$nomClasse] = $classeId;

                if ($prive && $montantAnnuel) {
                    $formules[$nomClasse] = DB::table('planification')->where('id_classe', $classeId)->where('id_annee', $anneeId)->where('motif', 'annuelle')->value('id_planification')
                        ?: DB::table('planification')->insertGetId([
                            'motif' => 'annuelle',
                            'id_classe' => $classeId,
                            'id_annee' => $anneeId,
                            'date_debut' => substr((string) $annee->annee, 0, 4).'-10-01',
                            'date_fin' => substr((string) $annee->annee, 5, 4).'-06-30',
                            'montant_planification' => $montantAnnuel,
                        ]);
                }
            }

            // 3. Élèves.
            foreach ($profil['eleves'] as [$prenom, $nom, $genre, $matricule, $nomClasse]) {
                if (DB::table('eleve')->where('matricule', $matricule)->exists()) {
                    continue;
                }
                $classeId = $classes[$nomClasse];
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
                    'id_planification' => $formules[$nomClasse] ?? null,
                    'date_inscription' => now()->toDateString(),
                ]);
            }

            // 4. Compte Google Play (Admin de l'école démo), avec les permissions par défaut d'un Admin.
            if (!$compteExistant) {
                $compte = User::withoutGlobalScopes()->create([
                    'nomPrenom' => $prive ? 'Demo PlayStore Prive' : 'Demo PlayStore',
                    'email' => $email,
                    'fonction' => 'Promoteur',
                    'telephone' => '70000000',
                    'genre' => 'Masculin',
                    'droit' => 'Admin',
                    'idEcole' => $ecoleId,
                    'statut' => 1,
                    'image' => 'default.png',
                    'pwd' => Hash::make($motDePasse),
                ]);
                $this->info("Compte {$email} créé.");
            } else {
                $compte = $compteExistant;
                $maj = ['idEcole' => $ecoleId, 'statut' => 1];
                if ($motDePasse) {
                    $maj['pwd'] = Hash::make($motDePasse);
                }
                DB::table('utilisateurs')->where('idUtilisateur', $compte->idUtilisateur)->update($maj);
                $this->line("Compte {$email} déjà présent : rattaché à l'école".($motDePasse ? ', mot de passe mis à jour' : '').'.');
            }
            app(ConfigurationController::class)->syncDefaultPermissions($compte->fresh() ?? $compte, 1);

            // 5. Abonnement actif jusqu'à la date de fin demandée (créé, ou fin remise à cette date).
            $abonnement = DB::table('abonnements')->where('ecole_id', $ecoleId)->where('statut', 'actif')->orderByDesc('id')->first();
            if ($abonnement) {
                DB::table('abonnements')->where('id', $abonnement->id)->update(['offre_id' => $offreId ?? $abonnement->offre_id, 'fin_at' => $fin, 'updated_at' => now()]);
            } elseif ($offreId) {
                DB::table('abonnements')->insert([
                    'ecole_id' => $ecoleId,
                    'offre_id' => $offreId,
                    'statut' => 'actif',
                    'debut_at' => now(),
                    'fin_at' => $fin,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $this->line('Abonnement actif jusqu\'au '.$fin->format('d/m/Y').'.');
        });

        $this->info('École de démonstration prête. Compte : '.$email);

        return self::SUCCESS;
    }
}
