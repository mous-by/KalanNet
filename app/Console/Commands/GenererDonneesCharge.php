<?php

namespace App\Console\Commands;

use App\Http\Controllers\ConfigurationController;
use App\Models\Ecole;
use App\Models\User;
use App\Services\Ecoles\SuppressionEcole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Remplit la base LOCALE de données fictives pour les tests de charge :
 * écoles de tous les types et de tous les ordres (Jardin, Fondamental I et II,
 * secondaire général et technique, complexes, franco-arabes, santé), avec
 * classes, enseignants, élèves, parents, notes de l'année 2025-2026,
 * absences, paiements, emplois du temps et présences.
 *
 *   php artisan kalannet:donnees-charge                 110 écoles, 25 élèves par classe
 *   php artisan kalannet:donnees-charge --ecoles=150 --eleves=30
 *   php artisan kalannet:donnees-charge --supprimer     retire tout ce que la commande a créé
 *
 * Les écoles créées sont notées dans storage/app/donnees-charge.json ; seules
 * celles-là sont supprimées. Refus en production ou sur une base non locale.
 */
class GenererDonneesCharge extends Command
{
    protected $signature = 'kalannet:donnees-charge
        {--ecoles=110 : Nombre d\'écoles (100 au minimum conseillé)}
        {--eleves=25 : Élèves par classe (Jardin : 70 %)}
        {--mot-de-passe=KalanNet2026 : Mot de passe des comptes Admin générés}
        {--supprimer : Supprime les écoles créées par cette commande}';

    protected $description = 'Remplit la base locale de données fictives (écoles de tous les ordres, ~1 million de lignes)';

    private const MANIFESTE = 'donnees-charge.json';

    private const ANNEE = 2; // 2025-2026, année complète

    /** Types d'école : [type, ordres, nombre pour 110 écoles]. */
    private const TYPES = [
        ['Fondamentale I', ['jardin', 'fondamentale1'], 22],
        ['Fondamentale II', ['jardin', 'fondamentale2'], 14],
        ['Collège', ['jardin', 'fondamentale1', 'fondamentale2'], 22],
        ['Secondaire Generale', ['secondairegenerale'], 16],
        ['Secondaire Technique et Professionnel', ['secondairetechniqueetprofessionnel'], 8],
        ['Complexe Scolaire', ['jardin', 'fondamentale1', 'fondamentale2', 'secondairegenerale', 'secondairetechniqueetprofessionnel'], 22],
        ['École de Santé', ['sante'], 6],
    ];

    /** Classes par ordre : nom => série (secondaire). */
    private const CLASSES = [
        'jardin' => ['Petite section A' => null, 'Grande section A' => null],
        'fondamentale1' => ['1ère année A' => null, '2ème année A' => null, '3ème année A' => null, '4ème année A' => null, '5ème année A' => null, '6ème année A' => null],
        'fondamentale2' => ['7ème année A' => null, '8ème année A' => null, '9ème année A' => null],
        'secondairegenerale' => ['10ème année commune' => null, '11ème année Sciences' => 'sciences', '11ème année SES' => 'ses', '12ème année TSE' => 'tse', '12ème année TSS' => 'tss'],
        'secondairetechniqueetprofessionnel' => ['1ère année Génie civil' => null, '2ème année Génie civil' => null, '3ème année Génie civil' => null],
        'sante' => ['1ère année Infirmier' => null, '2ème année Infirmier' => null, '3ème année Infirmier' => null],
    ];

    /** Libellé de l'ordre dans matiere_ordre, et nombre de matières par classe. */
    private const MATIERES = [
        'fondamentale1' => ['Fondamentale I', 9],
        'fondamentale2' => ['Fondamentale II', 12],
        'secondairegenerale' => ['Secondaire Generale', 10],
        'secondairetechniqueetprofessionnel' => ['Secondaire Technique et Professionnel', 8],
        'sante' => ['École de Santé', 8],
    ];

    private const PRENOMS_F = ['Aminata', 'Fatoumata', 'Mariam', 'Kadiatou', 'Awa', 'Oumou', 'Salimata', 'Djeneba', 'Assitan', 'Hawa', 'Ramatou', 'Bintou', 'Aïssata', 'Nana', 'Korotoumou', 'Sitan', 'Maimouna', 'Fanta', 'Rokia', 'Safiatou'];

    private const PRENOMS_M = ['Moussa', 'Ibrahim', 'Oumar', 'Seydou', 'Bakary', 'Adama', 'Mamadou', 'Souleymane', 'Boubacar', 'Modibo', 'Drissa', 'Lassina', 'Abdoulaye', 'Sekou', 'Yacouba', 'Issa', 'Mahamadou', 'Cheick', 'Sidiki', 'Amadou'];

    private const NOMS = ['Traoré', 'Coulibaly', 'Diarra', 'Keita', 'Konaté', 'Sangaré', 'Diallo', 'Touré', 'Dembélé', 'Sissoko', 'Camara', 'Koné', 'Cissé', 'Maïga', 'Samaké', 'Doumbia', 'Kanté', 'Fofana', 'Sidibé', 'Bagayoko', 'Sacko', 'Haïdara', 'Guindo', 'Togola', 'Dicko'];

    private const VILLES = ['Bamako', 'Kayes', 'Sikasso', 'Ségou', 'Mopti', 'Koulikoro', 'Gao', 'Tombouctou', 'Kita', 'Koutiala'];

    public function handle(): int
    {
        $hote = (string) config('database.connections.mysql.host');
        if (app()->environment('production') || !in_array($hote, ['127.0.0.1', 'localhost', '::1'], true)) {
            $this->error('Refusé : uniquement sur une base locale, jamais en production.');

            return self::FAILURE;
        }

        return $this->option('supprimer') ? $this->supprimer() : $this->generer();
    }

    private function generer(): int
    {
        if ($this->manifeste() !== []) {
            $this->error('Des données de charge existent déjà : lancez d\'abord « php artisan kalannet:donnees-charge --supprimer ».');

            return self::FAILURE;
        }

        $nbEcoles = max(7, (int) $this->option('ecoles'));
        $parClasse = max(5, (int) $this->option('eleves'));
        mt_srand(2026);
        $debut = microtime(true);

        $mali = (int) DB::table('pays')->where('code_iso', 'ML')->value('id');
        $caps = DB::table('cap as c')->join('academie as a', 'a.id_academie', '=', 'c.id_academie')
            ->where('a.id_pays', $mali)->get(['c.id_cap', 'c.nom_cap', 'a.id_academie', 'a.nom_academie'])->all();
        $offre = DB::table('abonnement_offres')->where('code', 'annuel')->value('id') ?? DB::table('abonnement_offres')->where('actif', 1)->value('id');
        $matieres = $this->matieresCommunes();
        $mdpAdmin = Hash::make((string) $this->option('mot-de-passe'));
        $mdpParent = Hash::make('Parent2026');

        // Répartition des types, proportionnelle pour un autre nombre d'écoles.
        $plan = [];
        foreach (self::TYPES as [$type, $ordres, $n]) {
            for ($i = 0; $i < max(1, (int) round($n * $nbEcoles / 110)); $i++) {
                $plan[] = [$type, $ordres];
            }
        }

        DB::statement('CREATE TEMPORARY TABLE IF NOT EXISTS chg_evaluations (id_evaluation INT, id_classe INT, id_matiere INT, id_enseignant INT, id_trimestre INT NULL, mois INT NULL, id_note INT, sur INT, KEY (id_classe))');

        $barre = $this->output->createProgressBar(count($plan));
        $creees = [];
        foreach ($plan as $n => [$type, $ordres]) {
            $creees[] = $this->creerEcole($n + 1, $type, $ordres, $parClasse, $caps[$n % count($caps)], $mali, $offre, $matieres, $mdpAdmin, $mdpParent);
            $this->enregistrerManifeste($creees);
            $barre->advance();
        }
        $barre->finish();
        $this->newLine(2);

        $this->bilan($creees, microtime(true) - $debut);

        return self::SUCCESS;
    }

    /** Matières communes (id_ecole null) : [ordre][franco-arabe 0|1] => [id, nom]. */
    private function matieresCommunes(): array
    {
        $liste = [];
        foreach (self::MATIERES as $ordre => [$libelle]) {
            foreach ([0, 1] as $fa) {
                $liste[$ordre][$fa] = DB::table('matiere as m')->join('matiere_ordre as o', 'o.id_matiere', '=', 'm.id_matiere')
                    ->whereNull('m.id_ecole')->where('o.ordre_enseignement', $libelle)
                    ->when($ordre !== 'sante', fn ($q) => $q->where('m.est_franco_arabe', $fa))
                    ->orderBy('m.id_matiere')->get(['m.id_matiere', 'm.nom_matiere'])->all();
            }
        }

        return $liste;
    }

    private function creerEcole(int $n, string $type, array $ordres, int $parClasse, object $cap, int $mali, ?int $offre, array $matieres, string $mdpAdmin, string $mdpParent): int
    {
        $fa = in_array($type, ['Fondamentale I', 'Fondamentale II', 'Collège', 'Complexe Scolaire', 'Secondaire Generale'], true) && $n % 4 === 0 ? 1 : 0;
        $prive = $type === 'Complexe Scolaire' || $n % 3 === 0;
        $ville = self::VILLES[$n % count(self::VILLES)];
        $nom = ($type === 'Complexe Scolaire' ? 'Complexe Scolaire' : ($type === 'École de Santé' ? 'École de Santé' : 'École')).' Charge '.str_pad((string) $n, 3, '0', STR_PAD_LEFT).($fa ? ' (franco-arabe)' : '');

        return DB::transaction(function () use ($n, $type, $ordres, $parClasse, $cap, $mali, $offre, $matieres, $mdpAdmin, $mdpParent, $fa, $prive, $ville, $nom) {
            $idEcole = DB::table('ecole')->insertGetId([
                'nomEcole' => $nom, 'typeEcole' => $type, 'statut' => $prive ? 'prive' : 'public', 'franco_arabe' => $fa,
                'id_pays' => $mali, 'id_academie' => $cap->id_academie, 'academie' => $cap->nom_academie, 'id_cap' => $cap->id_cap, 'cap' => $cap->nom_cap,
                'nomComplexe' => $type === 'Complexe Scolaire' ? $nom : null,
                'nomFondamental' => $type === 'Complexe Scolaire' ? 'École fondamentale Charge '.$n : null,
                'nomLycee' => $type === 'Complexe Scolaire' ? 'Lycée Charge '.$n : null,
                'adresse' => $ville.', Mali', 'telephone' => (string) (70000000 + $n), 'email' => "ecole$n@charge.kalannet.test",
                'notification_sms' => 0, 'notification_email' => 0, 'created_at' => now(), 'updated_at' => now(),
            ]);

            $notes = [];
            foreach ([['devoir', 'Devoir', 20], ['composition', 'Compo', 20], ['NT10', 'NT10', 10]] as [$t, $c, $v]) {
                $notes[$t] = DB::table('note')->insertGetId(['typeNote' => $t, 'codeNote' => $c, 'valeur' => $v, 'id_ecole' => $idEcole]);
            }
            if ($offre) {
                DB::table('abonnements')->insert(['ecole_id' => $idEcole, 'offre_id' => $offre, 'statut' => 'actif', 'debut_at' => now(), 'fin_at' => now()->addYear(), 'created_at' => now(), 'updated_at' => now()]);
            }
            $admin = User::withoutGlobalScopes()->create([
                'nomPrenom' => 'Admin Charge '.$n, 'email' => "admin$n@charge.kalannet.test", 'fonction' => 'Directeur', 'telephone' => (string) (71000000 + $n),
                'genre' => 'Masculin', 'droit' => 'Admin', 'idEcole' => $idEcole, 'statut' => 1, 'image' => 'default.png', 'pwd' => $mdpAdmin, 'doit_changer_mdp' => false,
            ]);
            app(ConfigurationController::class)->syncDefaultPermissions($admin, 1);
            $idCaisse = $prive ? DB::table('caisse')->insertGetId(['libelle' => 'CAISSE-'.$n, 'created_at' => now(), 'montant_initial' => 0, 'montant_net' => 0, 'id_ecole' => $idEcole, 'status' => 1, 'reference' => 'CS-CHG-'.$n]) : null;
            $idFiliere = in_array('sante', $ordres, true) ? DB::table('filieres')->insertGetId(['nom_filiere' => 'Infirmier d\'État', 'id_ecole' => $idEcole, 'actif' => 1, 'created_at' => now(), 'updated_at' => now()]) : null;

            // Classes.
            $classes = [];
            foreach ($ordres as $ordre) {
                foreach (self::CLASSES[$ordre] as $nomClasse => $serie) {
                    $classes[] = ['id' => DB::table('classe')->insertGetId([
                        'nom_classe' => $nomClasse, 'ordreEnseignement' => $ordre === 'sante' ? null : $ordre, 'idEcole' => $idEcole,
                        'id_filiere' => $ordre === 'sante' ? $idFiliere : null, 'serie' => $serie,
                    ]), 'ordre' => $ordre, 'nom' => $nomClasse];
                }
            }

            // Enseignants : un maître par classe du Jardin et du Fondamental I, plus une équipe pour les autres ordres.
            $nbMaitres = count(array_filter($classes, fn ($c) => in_array($c['ordre'], ['jardin', 'fondamentale1'], true)));
            $nbProfs = count(array_filter($classes, fn ($c) => !in_array($c['ordre'], ['jardin', 'fondamentale1'], true))) > 0 ? 10 : 0;
            $lignes = [];
            for ($i = 1; $i <= $nbMaitres + $nbProfs; $i++) {
                $f = $i % 2 === 0;
                $lignes[] = [
                    'nom_prenom_enseignant' => ($f ? self::PRENOMS_F[$i % 20] : self::PRENOMS_M[$i % 20]).' '.self::NOMS[($i * 7 + $n) % 25],
                    'genre_enseignant' => $f ? 'Féminin' : 'Masculin', 'telephone_enseignant' => (string) (72000000 + $n * 100 + $i),
                    'email_enseignant' => "prof$i.ecole$n@charge.kalannet.test", 'matricule' => "CHG$n-ENS$i", 'id_ecole' => $idEcole,
                    'type_contrat_enseignant' => ['CDI', 'CDD', 'VCT', 'FONCTIONNAIRE'][$i % 4], 'salaire_enseignant' => 75000 + ($i % 5) * 15000,
                    'salaire_mois_mode' => 12, 'is_deleted' => 0,
                ];
            }
            DB::table('enseignants')->insert($lignes);
            $profs = DB::table('enseignants')->where('id_ecole', $idEcole)->orderBy('id_enseignant')->pluck('id_enseignant')->all();
            $maitres = array_slice($profs, 0, $nbMaitres);
            $equipe = array_slice($profs, $nbMaitres) ?: $profs;

            // Matières, maîtres et emplois du temps.
            $ligneclasse = []; $edt = []; $jours = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
            $iMaitre = 0;
            foreach ($classes as &$classe) {
                if ($classe['ordre'] === 'jardin') {
                    DB::table('classe_maitres')->insert(['id_classe' => $classe['id'], 'id_enseignant' => $maitres[$iMaitre++ % max(1, count($maitres))]]);
                    $classe['matieres'] = [];
                    continue;
                }
                [, $nb] = self::MATIERES[$classe['ordre']];
                $liste = array_slice($matieres[$classe['ordre']][$fa] ?: $matieres[$classe['ordre']][0], 0, $nb);
                $maitre = $classe['ordre'] === 'fondamentale1' ? $maitres[$iMaitre++ % max(1, count($maitres))] : null;
                $classe['matieres'] = [];
                foreach ($liste as $k => $m) {
                    $prof = $maitre ?? $equipe[($k + $classe['id']) % count($equipe)];
                    $coef = $classe['ordre'] === 'fondamentale1' ? 1 : (preg_match('/math|français|francais|physique|arabe/iu', $m->nom_matiere) ? 3 : ($k % 3 === 0 ? 2 : 1));
                    $ligneclasse[] = ['id_classe' => $classe['id'], 'id_matiere' => $m->id_matiere, 'id_enseignants' => $prof, 'coefficient' => $coef, 'groupe' => str_starts_with($classe['ordre'], 'secondaire') ? ($coef >= 2 ? 'principale' : 'secondaire') : null];
                    $classe['matieres'][] = [$m->id_matiere, $prof, $m->nom_matiere];
                    $h = 7 + ($k % 5) * 2;
                    $edt[] = ['id_classe' => $classe['id'], 'id_matiere' => $m->id_matiere, 'id_enseignant' => $prof, 'id_annee_scolaire' => self::ANNEE, 'jour' => $jours[$k % 6], 'heure_debut' => sprintf('%02d:00:00', $h), 'heure_fin' => sprintf('%02d:00:00', $h + 2)];
                }
            }
            unset($classe);
            foreach (array_chunk($ligneclasse, 1000) as $lot) DB::table('ligneclasse')->insert($lot);
            foreach (array_chunk($edt, 1000) as $lot) DB::table('emploi_du_temps')->insert($lot);

            // Formules de paiement (écoles privées).
            $formules = [];
            if ($prive) {
                foreach ($classes as $classe) {
                    $montant = ['jardin' => 60000, 'fondamentale1' => 75000, 'fondamentale2' => 90000, 'secondairegenerale' => 120000, 'secondairetechniqueetprofessionnel' => 150000, 'sante' => 250000][$classe['ordre']];
                    $formules[$classe['id']] = [DB::table('planification')->insertGetId(['motif' => 'annuelle', 'id_classe' => $classe['id'], 'id_annee' => self::ANNEE, 'date_debut' => '2025-10-01', 'date_fin' => '2026-06-30', 'montant_planification' => $montant]), $montant];
                }
            }

            // Élèves et parents.
            $eleves = []; $parents = [];
            $sequence = 0;
            foreach ($classes as $classe) {
                $nb = $classe['ordre'] === 'jardin' ? (int) ceil($parClasse * 0.7) : $parClasse;
                $niveau = (int) preg_replace('/\D.*/', '', $classe['nom']) ?: 0;
                $age = ['jardin' => 4, 'fondamentale1' => 6, 'fondamentale2' => 6, 'secondairegenerale' => 6, 'secondairetechniqueetprofessionnel' => 16, 'sante' => 19][$classe['ordre']] + ($classe['ordre'] === 'jardin' ? (str_starts_with($classe['nom'], 'Grande') ? 1 : 0) : $niveau - 1);
                for ($i = 0; $i < $nb; $i++) {
                    $sequence++;
                    $fille = mt_rand(0, 1) === 1;
                    $famille = self::NOMS[mt_rand(0, 24)];
                    $eleves[] = [
                        'prenom_eleve' => $fille ? self::PRENOMS_F[mt_rand(0, 19)] : self::PRENOMS_M[mt_rand(0, 19)], 'nom_eleve' => $famille,
                        'genre_eleve' => $fille ? 'Féminin' : 'Masculin', 'date_naissance' => (2025 - $age).'-'.sprintf('%02d-%02d', mt_rand(1, 12), mt_rand(1, 28)),
                        'lieu_naiss' => self::VILLES[mt_rand(0, 9)], 'adresse_eleve' => $ville, 'matricule' => "CHG$n-$sequence",
                        'id_classe' => $classe['id'], 'id_annee' => self::ANNEE, 'id_ecole' => $idEcole, 'cas_social' => 'normal', 'statut_paiement' => 'normal',
                        'etat_dossier' => mt_rand(1, 100) <= 2 ? 2 : 0, 'image' => 'assets/images/avatars/avatar-1.png', 'date_inscription' => '2025-10-01',
                    ];
                    if ($sequence % 3 !== 0) {
                        $parents[$sequence] = ['nom_prenom_parent' => self::PRENOMS_M[mt_rand(0, 19)].' '.$famille, 'email_parent' => "parent$sequence.ecole$n@charge.kalannet.test", 'telephone_parent' => (string) (73000000 + $n * 1000 + $sequence), 'genre' => 'Masculin', 'idEcole' => $idEcole, 'pwd' => $mdpParent];
                    }
                }
            }
            foreach (array_chunk($eleves, 1000) as $lot) DB::table('eleve')->insert($lot);
            $idsEleves = DB::table('eleve')->where('id_ecole', $idEcole)->orderBy('id_eleve')->get(['id_eleve', 'matricule', 'id_classe', 'nom_eleve', 'prenom_eleve'])->keyBy(fn ($e) => (int) substr($e->matricule, strlen("CHG$n-")));
            foreach (array_chunk(array_values($parents), 1000) as $lot) DB::table('parents')->insert($lot);
            $idsParents = DB::table('parents')->where('idEcole', $idEcole)->pluck('id_parent', 'email_parent');

            $liens = []; $inscriptions = [];
            foreach ($idsEleves as $seq => $e) {
                // Deux enfants sur trois ont un parent ; le troisième est rattaché au parent de son aîné.
                $p = $idsParents["parent$seq.ecole$n@charge.kalannet.test"] ?? $idsParents['parent'.($seq - 1).".ecole$n@charge.kalannet.test"] ?? null;
                if ($p) $liens[] = ['id_eleve' => $e->id_eleve, 'id_parent' => $p, 'informer' => 1, 'lien_parent' => 'Père'];
                $inscriptions[] = ['id_eleve' => $e->id_eleve, 'id_classe' => $e->id_classe, 'id_annee' => self::ANNEE, 'id_planification' => $formules[$e->id_classe][0] ?? null, 'date_inscription' => '2025-10-01'];
            }
            foreach (array_chunk($liens, 1000) as $lot) DB::table('ligneparents_eleves')->insert($lot);
            foreach (array_chunk($inscriptions, 1000) as $lot) DB::table('ligne_inscription')->insert($lot);

            // Paiements (écoles privées) : une ou deux tranches de la formule annuelle.
            if ($prive) {
                $paiements = []; $recu = 0;
                foreach ($idsEleves as $seq => $e) {
                    [$idFormule, $montant] = $formules[$e->id_classe];
                    $tranches = $seq % 5 === 0 ? 1 : 2;
                    for ($t = 1; $t <= $tranches; $t++) {
                        $recu++;
                        $paiements[] = ['montant' => $montant / 2, 'montant_paye' => $montant / 2, 'date_paiement' => $t === 1 ? '2025-10-15' : '2026-01-15', 'mode_reglement' => 'especes', 'statut' => 'valide', 'motif' => 'annuelle',
                            'id_classe' => $e->id_classe, 'id_annee' => self::ANNEE, 'id_trimestre' => $t, 'reference' => "CHG$n-PAI$recu", 'idEcole' => $idEcole, 'id_eleve' => $e->id_eleve,
                            'parent' => 'Père', 'nom_payeur' => 'Parent de '.$e->prenom_eleve.' '.$e->nom_eleve, 'telephone' => (string) (74000000 + $recu), 'id_utilisateur' => $admin->idUtilisateur,
                            'id_caisse' => $idCaisse, 'numero_recu' => $recu, 'id_planification' => $idFormule];
                    }
                }
                foreach (array_chunk($paiements, 1000) as $lot) DB::table('paiement')->insert($lot);
                $faits = DB::table('paiement')->where('idEcole', $idEcole)->get(['id_paiement', 'id_classe', 'id_eleve', 'id_trimestre', 'montant', 'date_paiement']);
                foreach (array_chunk($faits->map(fn ($p) => ['id_classe' => $p->id_classe, 'id_annee' => self::ANNEE, 'id_paiement' => $p->id_paiement, 'id_eleve' => $p->id_eleve, 'id_trimestre' => $p->id_trimestre, 'idEcole' => $idEcole])->all(), 1000) as $lot) DB::table('ligne_paiement_eleve')->insert($lot);
                foreach (array_chunk($faits->map(fn ($p) => ['paiement_id' => $p->id_paiement, 'type_operation' => 'Paiement eleve', 'date_encaissement' => substr($p->date_paiement, 0, 10), 'motif_encaissement' => 'annuelle', 'montant_encaissement' => $p->montant, 'statut' => 'valide', 'id_annee_scolaire' => self::ANNEE, 'id_caisse' => $idCaisse, 'idUtilisateur' => $admin->idUtilisateur])->all(), 1000) as $lot) DB::table('encaissement')->insert($lot);
                DB::table('caisse')->where('id_caisse', $idCaisse)->update(['montant_net' => $faits->sum('montant')]);
            }

            // Absences et retards (environ 15 % des élèves).
            $premier = $idsEleves->first()?->id_eleve;
            if ($premier) {
                $absence = DB::table('controle')->insertGetId(['alertControle' => 'oui', 'type_controle' => 'Absence', 'id_eleve' => $premier, 'penalite_conduite' => 1, 'id_ecole' => $idEcole]);
                $retard = DB::table('controle')->insertGetId(['alertControle' => 'oui', 'type_controle' => 'Retard', 'id_eleve' => $premier, 'penalite_conduite' => 0.5, 'id_ecole' => $idEcole]);
                $matiereDe = collect($classes)->mapWithKeys(fn ($c) => [$c['id'] => $c['matieres'][0][0] ?? 0]);
                $appels = [];
                foreach ($idsEleves as $seq => $e) {
                    if ($seq % 7 !== 0) continue;
                    foreach ([1, 2, 3] as $tr) {
                        $appels[] = ['id_eleve' => $e->id_eleve, 'id_classe' => $e->id_classe, 'id_matiere' => $matiereDe[$e->id_classe], 'id_annee_scolaire' => self::ANNEE, 'id_trimestre' => $tr, 'id_ecole' => $idEcole,
                            'date' => ['2025-11-12', '2026-02-10', '2026-05-06'][$tr - 1], 'libelle' => 'Appel', 'heure_debut' => '08:00', 'heure_fin' => '10:00', 'notifier_parent' => 0, 'id_controle' => $seq % 2 ? $absence : $retard];
                    }
                }
                foreach (array_chunk($appels, 1000) as $lot) DB::table('controle_eleve')->insert($lot);
            }

            // Présences des enseignants (une séance par classe et par mois, d'octobre à mai).
            $presences = [];
            foreach ($classes as $classe) {
                $prof = $classe['matieres'][0][1] ?? null;
                if (!$prof) continue;
                foreach ([10, 11, 12, 1, 2, 3, 4, 5] as $mois) {
                    $presences[] = ['id_enseignant' => $prof, 'id_classe' => $classe['id'], 'date_presence' => ($mois >= 10 ? 2025 : 2026).sprintf('-%02d-10', $mois), 'nombre_heure' => 2, 'id_trimestre' => $mois >= 10 ? 1 : ($mois <= 3 ? 2 : 3), 'id_anneeScolaire' => self::ANNEE, 'id_ecole' => $idEcole, 'valide' => 1];
                }
            }
            foreach (array_chunk($presences, 1000) as $lot) DB::table('presences')->insert($lot);

            $this->genererNotes($idEcole, $classes, $notes);

            return $idEcole;
        });
    }

    /**
     * Évaluations et notes de l'année : Fondamental I en compositions mensuelles
     * (sur 10, d'octobre à mai) ; autres ordres : un devoir et une composition
     * par trimestre (sur 20). Notes calculées en SQL : niveau propre à l'élève
     * plus un écart par évaluation, toujours identiques d'une génération à l'autre.
     */
    private function genererNotes(int $idEcole, array $classes, array $notes): void
    {
        $evaluations = []; $correspondance = [];
        foreach ($classes as $classe) {
            foreach ($classe['matieres'] as [$idMatiere, $prof, $nomMatiere]) {
                $periodes = $classe['ordre'] === 'fondamentale1'
                    ? array_map(fn ($m) => [null, $m, $notes['NT10'], 10, 'Composition mensuelle', ($m >= 10 ? 2025 : 2026).sprintf('-%02d-25', $m)], [10, 11, 12, 1, 2, 3, 4, 5])
                    : array_merge(...array_map(fn ($t) => [[$t, null, $notes['devoir'], 20, 'Devoir', ['2025-11-05', '2026-02-04', '2026-04-29'][$t - 1]], [$t, null, $notes['composition'], 20, 'Composition', ['2025-12-15', '2026-03-16', '2026-06-01'][$t - 1]]], [1, 2, 3]));
                foreach ($periodes as [$tr, $mois, $idNote, $sur, $libelle, $date]) {
                    $evaluations[] = ['libeller' => "$libelle — $nomMatiere", 'date_evaluation' => $date, 'heure_debut' => '08:00:00', 'heure_fin' => '10:00:00'];
                    $correspondance[] = [$classe['id'], $idMatiere, $prof, $tr, $mois, $idNote, $sur];
                }
            }
        }
        if (!$evaluations) {
            return;
        }

        DB::table('chg_evaluations')->delete();
        foreach (array_chunk(array_keys($evaluations), 1000) as $cles) {
            DB::table('evaluation')->insert(array_map(fn ($k) => $evaluations[$k], $cles));
            // Identifiants consécutifs pour une insertion multiple (innodb_autoinc_lock_mode 0 ou 1), vérifié ci-dessous.
            $premier = (int) DB::getPdo()->lastInsertId();
            $lignes = [];
            foreach ($cles as $i => $k) {
                [$c, $m, $p, $tr, $mois, $note, $sur] = $correspondance[$k];
                $lignes[] = ['id_evaluation' => $premier + $i, 'id_classe' => $c, 'id_matiere' => $m, 'id_enseignant' => $p, 'id_trimestre' => $tr, 'mois' => $mois, 'id_note' => $note, 'sur' => $sur];
            }
            if (DB::table('evaluation')->whereBetween('id_evaluation', [$premier, $premier + count($cles) - 1])->count() !== count($cles)) {
                throw new \RuntimeException('Identifiants d\'évaluation non consécutifs : génération arrêtée.');
            }
            DB::table('chg_evaluations')->insert($lignes);
        }

        DB::statement("INSERT INTO ligne_evaluation (id_evaluation, id_classe, id_matiere, id_annee_scolaire, id_trimestre, mois, id_note, id_eleve, note, validation_status, id_enseignant)
            SELECT m.id_evaluation, m.id_classe, m.id_matiere, ?, m.id_trimestre, m.mois, m.id_note, e.id_eleve,
                   ROUND(LEAST(m.sur, GREATEST(0, (6.5 + CAST(CRC32(CONCAT('n', e.id_eleve)) % 11 AS SIGNED) + CAST(CRC32(CONCAT(e.id_eleve, '-', m.id_evaluation)) % 9 AS SIGNED) - 4) * m.sur / 20)) * 4) / 4,
                   'valide', m.id_enseignant
            FROM chg_evaluations m
            JOIN eleve e ON e.id_classe = m.id_classe AND e.id_annee = ? AND e.etat_dossier = 0 AND e.id_ecole = ?", [self::ANNEE, self::ANNEE, $idEcole]);
    }

    private function supprimer(): int
    {
        $ids = $this->manifeste();
        if ($ids === []) {
            $this->info('Aucune donnée de charge à supprimer.');

            return self::SUCCESS;
        }
        $this->warn(count($ids).' écoles de charge vont être supprimées avec toutes leurs données (les autres écoles ne sont pas touchées).');
        if (!$this->confirm('Continuer ?', true)) {
            return self::FAILURE;
        }

        $service = app(SuppressionEcole::class);
        $barre = $this->output->createProgressBar(count($ids));
        foreach ($ids as $id) {
            $ecole = Ecole::withoutGlobalScopes()->find($id);
            // Garde-fou : seulement les écoles créées par cette commande.
            if ($ecole && str_contains((string) $ecole->email, '@charge.kalannet.test')) {
                $evaluations = DB::table('ligne_evaluation as l')->join('classe as c', 'c.id_classe', '=', 'l.id_classe')
                    ->where('c.idEcole', $id)->distinct()->pluck('l.id_evaluation');
                $service->supprimer($ecole);
                foreach ($evaluations->chunk(1000) as $lot) DB::table('evaluation')->whereIn('id_evaluation', $lot)->delete();
            }
            $barre->advance();
        }
        $barre->finish();
        $this->newLine();
        @unlink(storage_path('app/'.self::MANIFESTE));
        $this->info('Données de charge supprimées.');

        return self::SUCCESS;
    }

    private function manifeste(): array
    {
        $fichier = storage_path('app/'.self::MANIFESTE);

        return is_file($fichier) ? (json_decode((string) file_get_contents($fichier), true)['ecoles'] ?? []) : [];
    }

    private function enregistrerManifeste(array $ids): void
    {
        file_put_contents(storage_path('app/'.self::MANIFESTE), json_encode(['cree_le' => now()->toDateTimeString(), 'ecoles' => $ids], JSON_PRETTY_PRINT));
    }

    private function bilan(array $ids, float $secondes): void
    {
        $classes = DB::table('classe')->whereIn('idEcole', $ids)->pluck('id_classe');
        $eleves = DB::table('eleve')->whereIn('id_ecole', $ids)->pluck('id_eleve');
        $tables = [
            'ecole' => count($ids),
            'classe' => $classes->count(),
            'enseignants' => DB::table('enseignants')->whereIn('id_ecole', $ids)->count(),
            'eleve' => $eleves->count(),
            'parents' => DB::table('parents')->whereIn('idEcole', $ids)->count(),
            'ligneparents_eleves' => DB::table('ligneparents_eleves')->whereIn('id_eleve', $eleves)->count(),
            'ligne_inscription' => DB::table('ligne_inscription')->whereIn('id_eleve', $eleves)->count(),
            'ligneclasse' => DB::table('ligneclasse')->whereIn('id_classe', $classes)->count(),
            'emploi_du_temps' => DB::table('emploi_du_temps')->whereIn('id_classe', $classes)->count(),
            'evaluation' => DB::table('ligne_evaluation')->whereIn('id_classe', $classes)->distinct()->count('id_evaluation'),
            'ligne_evaluation' => DB::table('ligne_evaluation')->whereIn('id_classe', $classes)->count(),
            'controle_eleve' => DB::table('controle_eleve')->whereIn('id_ecole', $ids)->count(),
            'presences' => DB::table('presences')->whereIn('id_ecole', $ids)->count(),
            'paiement' => DB::table('paiement')->whereIn('idEcole', $ids)->count(),
            'ligne_paiement_eleve' => DB::table('ligne_paiement_eleve')->whereIn('idEcole', $ids)->count(),
            'encaissement' => DB::table('encaissement')->whereIn('paiement_id', DB::table('paiement')->whereIn('idEcole', $ids)->pluck('id_paiement'))->count(),
            'utilisateurs' => DB::table('utilisateurs')->whereIn('idEcole', $ids)->count(),
        ];
        $this->table(['Table', 'Lignes créées'], collect($tables)->map(fn ($n, $t) => [$t, number_format($n, 0, ',', ' ')])->values()->all());
        $this->info('Total : '.number_format(array_sum($tables), 0, ',', ' ').' lignes en '.round($secondes).' s.');
        $this->line('Types : '.DB::table('ecole')->whereIn('idEcole', $ids)->groupBy('typeEcole')->selectRaw('typeEcole, count(*) n')->get()->map(fn ($r) => "$r->typeEcole $r->n")->implode(' · '));
        $this->line('Connexion : admin1@charge.kalannet.test … admin'.count($ids).'@charge.kalannet.test, mot de passe « '.$this->option('mot-de-passe').' ».');
    }
}
