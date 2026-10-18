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
 * écoles surtout maliennes, mais aussi d'Afrique, d'Europe et d'Amérique
 * (noms inventés, villes, téléphones et monnaie du pays), de tous les types et ordres (Jardin, Fondamental I et II,
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
        {--supprimer : Supprime les écoles créées par cette commande}
        {--reprendre : Continue une génération interrompue (mêmes options)}';

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

    private const PRENOMS_F_PLUS = ['Mariétou', 'Kadidiatou', 'Fatoumata Binta', 'Nènè', 'Hadja', 'Sali', 'Tenin', 'Massitan', 'Fanta', 'Djénéba', 'Mamou', 'Saran', 'Bintou', 'Koumba', 'Aïchata', 'Adiaratou', 'Kani', 'Yah', 'Ténimba', 'Habibatou'];

    private const PRENOMS_M_PLUS = ['Siaka', 'Bréhima', 'Tiémoko', 'Fousseyni', 'Daouda', 'Lassana', 'Mamadou Lamine', 'Oumarou', 'Moctar', 'Hamidou', 'Alassane', 'Kalifa', 'Yaya', 'Bassirou', 'Zoumana', 'Sory', 'Madou', 'Nouhoum', 'Abdramane', 'Ousmane'];

    private const PRENOMS_F = ['Aminata', 'Fatoumata', 'Mariam', 'Kadiatou', 'Awa', 'Oumou', 'Salimata', 'Djeneba', 'Assitan', 'Hawa', 'Ramatou', 'Bintou', 'Aïssata', 'Nana', 'Korotoumou', 'Sitan', 'Maimouna', 'Fanta', 'Rokia', 'Safiatou'];

    private const PRENOMS_M = ['Moussa', 'Ibrahim', 'Oumar', 'Seydou', 'Bakary', 'Adama', 'Mamadou', 'Souleymane', 'Boubacar', 'Modibo', 'Drissa', 'Lassina', 'Abdoulaye', 'Sekou', 'Yacouba', 'Issa', 'Mahamadou', 'Cheick', 'Sidiki', 'Amadou'];



    /** Pays des écoles fictives (pour 110 écoles) : villes, quartiers, noms, monnaie (facteur par rapport au FCFA). */
    private const PAYS = [
        'ML' => ['n' => 80, 'facteur' => 1, 'groupe' => 'sahel',
            'villes' => ['Bamako', 'Kayes', 'Sikasso', 'Ségou', 'Mopti', 'Koulikoro', 'Gao', 'Tombouctou', 'Kita', 'Koutiala', 'San', 'Bougouni', 'Kati', 'Nioro du Sahel', 'Kidal', 'Ménaka', 'Taoudénit', 'Bandiagara', 'Djenné', 'Dioïla', 'Nara', 'Bafoulabé', 'Kolokani', 'Kangaba', 'Yanfolila', 'Kadiolo', 'Fana', 'Bla', 'Macina', 'Niono', 'Douentza', 'Bourem', 'Ansongo', 'Diré', 'Goundam', 'Tominian', 'Yorosso', 'Kéniéba', 'Diéma', 'Banamba'],
            'quartiers' => ['Badalabougou', 'Hamdallaye', 'Lafiabougou', 'Missira', 'Sogoniko', 'Kalaban Coura', 'Djélibougou', 'Niamakoro', 'Sébénikoro', 'Magnambougou', 'Banconi', 'Torokorobougou', 'Médina Coura', 'Bacodjicoroni', 'Faladié', 'Sabalibougou', 'Daoudabougou', 'Kalabancoro', 'Sotuba', 'Boulkassoumbougou', 'Kanadjiguila', 'Yirimadio', 'Senou', 'Kouloubleni', 'Dravéla', 'Bagadadji', 'Niaréla', 'Quinzambougou', 'Djicoroni Para', 'Samé', 'Kalaban Koro', 'Sirakoro', 'Garantiguibougou', 'Baco-Djicoroni ACI', 'Hippodrome', 'Korofina', 'Doumanzana', 'Titibougou', 'Moribabougou', 'Kati Coco'],
            'noms' => ['Traoré', 'Coulibaly', 'Diarra', 'Keita', 'Konaté', 'Sangaré', 'Diallo', 'Touré', 'Dembélé', 'Sissoko', 'Camara', 'Koné', 'Cissé', 'Maïga', 'Samaké', 'Doumbia', 'Kanté', 'Fofana', 'Sidibé', 'Bagayoko', 'Sacko', 'Haïdara', 'Guindo', 'Togola', 'Dicko', 'Bouaré', 'Dao', 'Mariko', 'Tangara', 'Kéita', 'Sanogo', 'Bengaly', 'Goïta', 'Dolo', 'Tembely', 'Sow', 'Ba', 'Cissoko', 'Kouyaté', 'Diabaté', 'Soumaré', 'Niaré', 'Berthé', 'Kassogué', 'Ongoïba', 'Boré', 'Dagnon', 'Mallé', 'Konaré', 'Dramé']],
        'SN' => ['n' => 6, 'facteur' => 1, 'groupe' => 'sahel', 'villes' => ['Dakar', 'Thiès', 'Saint-Louis', 'Ziguinchor', 'Kaolack', 'Mbour'], 'quartiers' => ['Médina', 'Le Plateau', 'Grand Yoff', 'Pikine', 'Les Almadies', 'Ouakam'],
            'noms' => ['Diop', 'Ndiaye', 'Fall', 'Sow', 'Ba', 'Gueye', 'Sarr', 'Faye', 'Diouf', 'Mbaye', 'Seck', 'Cissé']],
        'CI' => ['n' => 6, 'facteur' => 1, 'groupe' => 'cotier', 'villes' => ['Abidjan', 'Bouaké', 'Yamoussoukro', 'Daloa', 'San-Pédro', 'Korhogo'], 'quartiers' => ['Cocody', 'Yopougon', 'Abobo', 'Treichville', 'Marcory', 'Le Plateau'],
            'noms' => ['Kouassi', 'Koné', 'Yao', 'Kouadio', 'Bamba', 'Ouattara', 'N’Guessan', 'Koffi', 'Konan', 'Touré']],
        'BF' => ['n' => 5, 'facteur' => 1, 'groupe' => 'sahel', 'villes' => ['Ouagadougou', 'Bobo-Dioulasso', 'Koudougou', 'Banfora'], 'quartiers' => ['Gounghin', 'Dapoya', 'Ouaga 2000', 'Pissy'],
            'noms' => ['Ouédraogo', 'Sawadogo', 'Compaoré', 'Zongo', 'Kaboré', 'Ilboudo', 'Traoré', 'Sanou']],
        'GN' => ['n' => 3, 'facteur' => 15, 'groupe' => 'sahel', 'villes' => ['Conakry', 'Kankan', 'Labé'], 'quartiers' => ['Kaloum', 'Ratoma', 'Matoto', 'Dixinn'],
            'noms' => ['Barry', 'Bah', 'Diallo', 'Camara', 'Sylla', 'Soumah', 'Condé', 'Keïta']],
        'NE' => ['n' => 2, 'facteur' => 1, 'groupe' => 'sahel', 'villes' => ['Niamey', 'Zinder'], 'quartiers' => ['Le Plateau', 'Lazaret', 'Yantala'],
            'noms' => ['Issoufou', 'Moussa', 'Abdou', 'Hama', 'Garba', 'Mahamadou', 'Ibrahim']],
        'CM' => ['n' => 3, 'facteur' => 1, 'groupe' => 'cotier', 'villes' => ['Yaoundé', 'Douala', 'Bafoussam'], 'quartiers' => ['Bastos', 'Akwa', 'Bonapriso', 'Mvog-Mbi'],
            'noms' => ['Mballa', 'Ngono', 'Fotso', 'Tchoumi', 'Kamga', 'Nkoulou', 'Essomba', 'Mbarga']],
        'FR' => ['n' => 2, 'facteur' => 1 / 600, 'groupe' => 'europe', 'villes' => ['Paris', 'Lyon', 'Marseille', 'Toulouse', 'Lille'], 'quartiers' => ['Belleville', 'Croix-Rousse', 'Le Panier', 'Saint-Cyprien', 'Wazemmes'],
            'noms' => ['Martin', 'Bernard', 'Dubois', 'Petit', 'Durand', 'Leroy', 'Moreau', 'Laurent', 'Garcia', 'Diallo']],
        'BE' => ['n' => 1, 'facteur' => 1 / 600, 'groupe' => 'europe', 'villes' => ['Bruxelles', 'Liège', 'Namur'], 'quartiers' => ['Ixelles', 'Schaerbeek', 'Outremeuse'],
            'noms' => ['Peeters', 'Janssens', 'Dubois', 'Lambert', 'Dupont', 'Maes']],
        'CA' => ['n' => 1, 'facteur' => 1 / 450, 'groupe' => 'amerique', 'villes' => ['Montréal', 'Québec', 'Ottawa', 'Sherbrooke'], 'quartiers' => ['Le Plateau-Mont-Royal', 'Limoilou', 'Vanier', 'Fleurimont'],
            'noms' => ['Tremblay', 'Gagnon', 'Roy', 'Côté', 'Bouchard', 'Gauthier', 'Morin']],
        'US' => ['n' => 1, 'facteur' => 1 / 600, 'groupe' => 'amerique', 'villes' => ['New York', 'Atlanta', 'Houston', 'Washington'], 'quartiers' => ['Harlem', 'Midtown', 'Montrose', 'Georgetown'],
            'noms' => ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Davis', 'Miller', 'Wilson']],
    ];

    private const PRENOMS = [
        'sahel' => [[...self::PRENOMS_F, ...self::PRENOMS_F_PLUS], [...self::PRENOMS_M, ...self::PRENOMS_M_PLUS]],
        'cotier' => [['Aya', 'Adjoua', 'Amenan', 'Grâce', 'Estelle', 'Christelle', 'Mireille', 'Nadège', 'Fatou', 'Awa'], ['Kouamé', 'Yao', 'Serge', 'Didier', 'Hervé', 'Arnaud', 'Patrick', 'Jean-Marc', 'Ibrahim', 'Samuel']],
        'europe' => [['Emma', 'Léa', 'Chloé', 'Manon', 'Camille', 'Inès', 'Jade', 'Louise', 'Aïcha', 'Sarah'], ['Lucas', 'Hugo', 'Louis', 'Gabriel', 'Arthur', 'Nathan', 'Jules', 'Adam', 'Moussa', 'Yanis']],
        'amerique' => [['Olivia', 'Emma', 'Ava', 'Sophia', 'Mia', 'Charlotte', 'Florence', 'Alice', 'Aminata', 'Grace'], ['Liam', 'Noah', 'James', 'William', 'Ethan', 'Logan', 'Félix', 'Thomas', 'Samuel', 'Daniel']],
    ];

    /** Noms d'écoles (inventés : aucune école réelle). */
    private const MOTS = ['Les Étoiles', 'Le Savoir', 'Espoir', 'Avenir', 'Excellence', 'La Réussite', 'Les Pionniers', 'Lumière', 'Les Bâtisseurs', 'Horizon', 'La Source', 'Les Lauréats', 'Sabali', 'Benkadi', 'Jiguiya', 'Danaya', 'Nyeta', 'Hèrè', 'Badenya', 'Kalanso', 'Dème', 'Sininyesigi', 'Tilé', 'Nyèsigi', 'Les Flamboyants', 'Les Baobabs', 'Le Fleuve', 'La Colombe', 'L’Union', 'La Persévérance', 'Le Progrès', 'Les Jeunes Talents', 'La Clé du Savoir', 'Le Phare', 'Les Cèdres', 'Le Niger'];

    private const MOTS_EN = ['Riverside', 'Oak Hill', 'Maple Grove', 'Sunrise', 'Lakeview', 'Greenwood', 'Westbrook', 'Cedar Park'];

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
        $dejaCreees = $this->manifeste();
        if ($dejaCreees !== [] && !$this->option('reprendre')) {
            $this->error('Des données de charge existent déjà : « --reprendre » pour continuer, ou « --supprimer » pour tout retirer.');

            return self::FAILURE;
        }

        $nbEcoles = max(7, (int) $this->option('ecoles'));
        $parClasse = max(5, (int) $this->option('eleves'));
        mt_srand(2026);
        $debut = microtime(true);

        $mali = (int) DB::table('pays')->where('code_iso', 'ML')->value('id');
        $caps = DB::table('cap as c')->join('academie as a', 'a.id_academie', '=', 'c.id_academie')
            ->where('a.id_pays', $mali)->get(['c.id_cap', 'c.nom_cap', 'a.id_academie', 'a.nom_academie'])->all();
        $pays = DB::table('pays')->whereIn('code_iso', array_keys(self::PAYS))->get()->keyBy('code_iso');
        $offre = DB::table('abonnement_offres')->where('code', 'annuel')->value('id') ?? DB::table('abonnement_offres')->where('actif', 1)->value('id');
        $matieres = $this->matieresCommunes();
        $mdpAdmin = Hash::make((string) $this->option('mot-de-passe'));
        $mdpParent = Hash::make('Parent2026');

        $plan = $this->profils($nbEcoles, $pays, $caps);

        DB::statement('CREATE TEMPORARY TABLE IF NOT EXISTS chg_evaluations (id_evaluation INT, id_classe INT, id_matiere INT, id_enseignant INT, id_trimestre INT NULL, mois INT NULL, id_note INT, sur INT, KEY (id_classe))');

        $barre = $this->output->createProgressBar(count($plan));
        $creees = $dejaCreees;
        $barre->advance(count($creees));
        foreach ($plan as $n => $profil) {
            if ($n < count($dejaCreees)) {
                continue; // déjà créée lors d'une génération interrompue
            }
            $creees[] = $this->creerEcole($n + 1, $profil, $parClasse, $offre, $matieres, $mdpAdmin, $mdpParent);
            $this->enregistrerManifeste($creees);
            $barre->advance();
        }
        $barre->finish();
        $this->newLine(2);

        $this->bilan($creees, microtime(true) - $debut);

        return self::SUCCESS;
    }

    /**
     * Une école par profil : pays (surtout le Mali, puis l'Afrique, l'Europe et
     * l'Amérique), type et ordres, ville, quartier, nom inventé et unique.
     */
    private function profils(int $nbEcoles, $pays, array $caps): array
    {
        $profils = []; $noms = [];
        $typesMali = [];
        foreach (self::TYPES as [$type, $ordres, $n]) {
            for ($i = 0; $i < max(1, (int) round($n * self::PAYS['ML']['n'] / 110)); $i++) {
                $typesMali[] = [$type, $ordres];
            }
        }
        mt_srand(110);
        shuffle($typesMali);
        // Hors Mali : types proposés à la création d'école (Primaire, Secondaire Générale, Complexe).
        $typesAutres = [
            ['Primaire', ['jardin', 'fondamentale1']],
            ['Secondaire Generale', ['fondamentale2', 'secondairegenerale']],
            ['Complexe Scolaire', ['jardin', 'fondamentale1', 'fondamentale2', 'secondairegenerale']],
        ];

        $global = 0;
        foreach (self::PAYS as $code => $conf) {
            if (!isset($pays[$code])) {
                continue;
            }
            $nombre = max(1, (int) round($conf['n'] * $nbEcoles / 110));
            for ($i = 0; $i < $nombre; $i++) {
                [$type, $ordres] = $code === 'ML' ? $typesMali[$i % count($typesMali)] : $typesAutres[$i % count($typesAutres)];
                // Une école sur trois dans la capitale (quartiers), les autres réparties dans les villes.
                $ville = $i % 3 === 0 ? $conf['villes'][0] : $conf['villes'][1 + intdiv($i, 3) % (count($conf['villes']) - 1)];
                $quartier = $conf['quartiers'][intdiv($i, 7) % count($conf['quartiers'])];
                $nom = $this->nomEcole($code, $conf['groupe'], $type, $ville, $quartier, $global++, $noms);
                $capitale = $ville === $conf['villes'][0];
                $profils[] = [
                    'code' => $code, 'pays' => $pays[$code], 'conf' => $conf, 'type' => $type, 'ordres' => $ordres,
                    'ville' => $ville, 'quartier' => $quartier, 'nom' => $nom,
                    // Le quartier n'a de sens que dans la capitale.
                    'adresse' => ($capitale ? $quartier.', ' : '').$ville,
                    'cap' => $code === 'ML' ? $caps[$i % count($caps)] : null,
                    'fa' => $code === 'ML' && in_array($type, ['Fondamentale I', 'Fondamentale II', 'Collège', 'Complexe Scolaire', 'Secondaire Generale'], true) && $i % 4 === 3 ? 1 : 0,
                    'prive' => $type === 'Complexe Scolaire' || $i % 3 === 0,
                ];
            }
        }

        return $profils;
    }

    private function nomEcole(string $code, string $groupe, string $type, string $ville, string $quartier, int $i, array &$deja): string
    {
        $mot = self::MOTS[$i % count(self::MOTS)];
        // Dans la capitale, l'école porte le nom de son quartier.
        $lieu = $ville === (self::PAYS[$code]['villes'][0] ?? $ville) ? $quartier : $ville;
        $motEn = self::MOTS_EN[$i % count(self::MOTS_EN)];
        $nom = match (true) {
            $code === 'US' => match ($type) {
                'Primaire' => "$motEn Elementary School",
                'Secondaire Generale' => "$motEn High School",
                default => "$motEn Academy",
            },
            $type === 'Fondamentale I' => "École Fondamentale $mot de $lieu",
            $type === 'Fondamentale II' => "École Fondamentale $mot de $lieu",
            $type === 'Collège' => "Collège $mot de $lieu",
            $type === 'Secondaire Technique et Professionnel' => "Lycée Technique $mot de $lieu",
            $type === 'École de Santé' => "École de Santé $mot de $lieu",
            $type === 'Complexe Scolaire' => "Complexe Scolaire $mot de $lieu",
            $type === 'Primaire' => match ($groupe) {
                'europe' => "École élémentaire $mot de $lieu",
                'amerique' => "École primaire $mot de $lieu",
                default => "École Primaire $mot de $lieu",
            },
            $type === 'Secondaire Generale' && $groupe === 'amerique' => "École secondaire $mot de $lieu",
            default => "Lycée $mot de $lieu",
        };
        $nom = preg_replace(["/ de Le /u", "/ de Les /u", "/ de ([AEIOUYÉÈHaeiouyéè])/u"], [' du ', ' des ', ' d’$1'], $nom);
        // Deux écoles ne portent jamais le même nom.
        $base = $nom;
        for ($k = 2; isset($deja[$nom]); $k++) {
            $nom = "$base ($quartier".($k > 2 ? " $k" : '').')';
        }
        $deja[$nom] = true;

        return $nom;
    }

    /** Numéro au format du pays (longueur nationale), toujours différent selon $graine. */
    private function telephone(object $pays, int $graine): string
    {
        $longueur = max(8, (int) ($pays->telephone_longueur ?: 8));

        return '7'.str_pad((string) ($graine % (10 ** ($longueur - 1))), $longueur - 1, '0', STR_PAD_LEFT);
    }

    private function montant(float $fcfa, array $conf): float
    {
        return max(5, round($fcfa * $conf['facteur'] / 5) * 5);
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

    private function creerEcole(int $n, array $profil, int $parClasse, ?int $offre, array $matieres, string $mdpAdmin, string $mdpParent): int
    {
        ['type' => $type, 'ordres' => $ordres, 'fa' => $fa, 'prive' => $prive, 'ville' => $ville, 'nom' => $nom, 'cap' => $cap, 'pays' => $pays, 'conf' => $conf] = $profil;
        $mali = $profil['code'] === 'ML';
        [$prenomsF, $prenomsM] = self::PRENOMS[$conf['groupe']];
        $nomsFamille = $conf['noms'];
        $lieux = $conf['villes'];

        return DB::transaction(function () use ($n, $type, $ordres, $parClasse, $cap, $mali, $pays, $conf, $offre, $matieres, $mdpAdmin, $mdpParent, $fa, $prive, $ville, $nom, $profil, $prenomsF, $prenomsM, $nomsFamille, $lieux) {
            $mot = preg_replace('/^.*? (de|des|du) /u', '', $nom);
            $idEcole = DB::table('ecole')->insertGetId([
                'nomEcole' => $nom, 'typeEcole' => $type, 'statut' => $prive ? 'prive' : 'public', 'franco_arabe' => $fa,
                'id_pays' => $pays->id, 'id_academie' => $cap?->id_academie, 'academie' => $cap?->nom_academie ?? '', 'id_cap' => $cap?->id_cap, 'cap' => $cap?->nom_cap,
                'nomComplexe' => $type === 'Complexe Scolaire' ? $nom : null,
                'nomFondamental' => $type === 'Complexe Scolaire' ? 'École fondamentale '.$ville : null,
                'nomLycee' => $type === 'Complexe Scolaire' ? 'Lycée '.$ville : null,
                'adresse' => $profil['adresse'].', '.$pays->nom, 'telephone' => $this->telephone($pays, 1000 + $n), 'email' => "ecole$n@charge.kalannet.test",
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
                'nomPrenom' => $prenomsM[$n % count($prenomsM)].' '.$nomsFamille[$n % count($nomsFamille)], 'email' => "admin$n@charge.kalannet.test", 'fonction' => 'Directeur',
                // Colonne entière : numéros à 10 chiffres impossibles ; connexion par e-mail.
                'telephone' => $mali ? (int) $this->telephone($pays, 2000 + $n) : null,
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
                    'nom_prenom_enseignant' => ($f ? $prenomsF[$i % count($prenomsF)] : $prenomsM[$i % count($prenomsM)]).' '.$nomsFamille[($i * 7 + $n) % count($nomsFamille)],
                    'genre_enseignant' => $f ? 'Féminin' : 'Masculin', 'telephone_enseignant' => $this->telephone($pays, 300000 + $n * 100 + $i),
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
                    $montant = $this->montant(['jardin' => 60000, 'fondamentale1' => 75000, 'fondamentale2' => 90000, 'secondairegenerale' => 120000, 'secondairetechniqueetprofessionnel' => 150000, 'sante' => 250000][$classe['ordre']], $conf);
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
                    $famille = $nomsFamille[mt_rand(0, count($nomsFamille) - 1)];
                    $eleves[] = [
                        'prenom_eleve' => $fille ? $prenomsF[mt_rand(0, count($prenomsF) - 1)] : $prenomsM[mt_rand(0, count($prenomsM) - 1)], 'nom_eleve' => $famille,
                        'genre_eleve' => $fille ? 'Féminin' : 'Masculin', 'date_naissance' => (2025 - $age).'-'.sprintf('%02d-%02d', mt_rand(1, 12), mt_rand(1, 28)),
                        'lieu_naiss' => $lieux[mt_rand(0, count($lieux) - 1)], 'adresse_eleve' => $profil['adresse'], 'matricule' => "CHG$n-$sequence",
                        'id_classe' => $classe['id'], 'id_annee' => self::ANNEE, 'id_ecole' => $idEcole, 'cas_social' => 'normal', 'statut_paiement' => 'normal',
                        'etat_dossier' => mt_rand(1, 100) <= 2 ? 2 : 0, 'image' => 'assets/images/avatars/avatar-1.png', 'date_inscription' => '2025-10-01',
                    ];
                    if ($sequence % 3 !== 0) {
                        $parents[$sequence] = ['nom_prenom_parent' => $prenomsM[mt_rand(0, count($prenomsM) - 1)].' '.$famille, 'email_parent' => "parent$sequence.ecole$n@charge.kalannet.test", 'telephone_parent' => $this->telephone($pays, 400000 + $n * 1000 + $sequence), 'genre' => 'Masculin', 'idEcole' => $idEcole, 'pwd' => $mdpParent];
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
                            'parent' => 'Père', 'nom_payeur' => 'Parent de '.$e->prenom_eleve.' '.$e->nom_eleve, 'telephone' => $this->telephone($pays, 500000 + $n * 1000 + $recu), 'id_utilisateur' => $admin->idUtilisateur,
                            'id_caisse' => $idCaisse, 'numero_recu' => $recu, 'id_planification' => $idFormule];
                    }
                }
                foreach (array_chunk($paiements, 1000) as $lot) DB::table('paiement')->insert($lot);
                $faits = DB::table('paiement')->where('idEcole', $idEcole)->get(['id_paiement', 'id_classe', 'id_eleve', 'id_trimestre', 'montant', 'date_paiement']);
                foreach (array_chunk($faits->map(fn ($p) => ['id_classe' => $p->id_classe, 'id_annee' => self::ANNEE, 'id_paiement' => $p->id_paiement, 'id_eleve' => $p->id_eleve, 'id_trimestre' => $p->id_trimestre, 'idEcole' => $idEcole])->all(), 1000) as $lot) DB::table('ligne_paiement_eleve')->insert($lot);
                foreach (array_chunk($faits->map(fn ($p) => ['paiement_id' => $p->id_paiement, 'type_operation' => 'Paiement eleve', 'date_encaissement' => substr($p->date_paiement, 0, 10), 'motif_encaissement' => 'annuelle', 'montant_encaissement' => $p->montant, 'statut' => 'valide', 'id_annee_scolaire' => self::ANNEE, 'id_caisse' => $idCaisse, 'idUtilisateur' => $admin->idUtilisateur])->all(), 1000) as $lot) DB::table('encaissement')->insert($lot);
                DB::table('caisse')->where('id_caisse', $idCaisse)->update(['montant_net' => $faits->sum('montant')]);
                // Les reçus 1..$recu sont pris : le compteur doit repartir après.
                DB::table('paiement_sequences')->updateOrInsert(['ecole_id' => $idEcole, 'type' => 'recu'], ['dernier_numero' => $recu]);
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
        // Sous-requêtes : jamais la liste des élèves en paramètres (un million d'identifiants).
        $classes = DB::table('classe')->select('id_classe')->whereIn('idEcole', $ids);
        $eleves = DB::table('eleve')->select('id_eleve')->whereIn('id_ecole', $ids);
        $paiements = DB::table('paiement')->select('id_paiement')->whereIn('idEcole', $ids);
        $tables = [
            'ecole' => count($ids),
            'classe' => DB::table('classe')->whereIn('idEcole', $ids)->count(),
            'enseignants' => DB::table('enseignants')->whereIn('id_ecole', $ids)->count(),
            'eleve' => DB::table('eleve')->whereIn('id_ecole', $ids)->count(),
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
            'encaissement' => DB::table('encaissement')->whereIn('paiement_id', $paiements)->count(),
            'utilisateurs' => DB::table('utilisateurs')->whereIn('idEcole', $ids)->count(),
        ];
        $this->table(['Table', 'Lignes créées'], collect($tables)->map(fn ($n, $t) => [$t, number_format($n, 0, ',', ' ')])->values()->all());
        $this->info('Total : '.number_format(array_sum($tables), 0, ',', ' ').' lignes en '.round($secondes).' s.');
        $this->line('Pays : '.DB::table('ecole as e')->join('pays as p', 'p.id', '=', 'e.id_pays')->whereIn('e.idEcole', $ids)->groupBy('p.nom')->selectRaw('p.nom, count(*) n')->orderByDesc('n')->get()->map(fn ($r) => "$r->nom $r->n")->implode(' · '));
        $this->line('Types : '.DB::table('ecole')->whereIn('idEcole', $ids)->groupBy('typeEcole')->selectRaw('typeEcole, count(*) n')->get()->map(fn ($r) => "$r->typeEcole $r->n")->implode(' · '));
        $this->line('Connexion : admin1@charge.kalannet.test … admin'.count($ids).'@charge.kalannet.test, mot de passe « '.$this->option('mot-de-passe').' ».');
    }
}
