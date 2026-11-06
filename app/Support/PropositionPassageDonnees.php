<?php

namespace App\Support;

use App\Models\AnneeScolaire;
use App\Models\Classe;
use App\Services\Bulletins\MoyennesAnnuelles;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * « Proposition de passage, redoublement, exclusion » d'une classe du fondamental : pré-remplie depuis KalanNet, ajustée
 * par le conseil des maîtres, puis décidée par le CAP. Classes à examen national : le résultat de l'examen s'il existe
 * (en 9e, un élève peut passer avec une moyenne plus basse qu'un autre qui redouble), sinon la moyenne annuelle.
 * Un élève qui échoue redouble, sauf si les règles rappelées sur la fiche officielle l'excluent (voir apresEchec).
 */
class PropositionPassageDonnees
{
    public const DECISIONS = ['passe' => 'Passe', 'redouble' => 'Redouble', 'exclu' => 'Exclu(e)', 'abandon' => 'Abandon'];

    private const ORDRE = ['passe' => 0, 'redouble' => 1, 'exclu' => 2, 'abandon' => 3];

    private const FILLES = ['féminin', 'feminin', 'f', 'fille', 'femme'];

    public const ORDRES = ['fondamentale1', 'fondamentale2'];

    /** Présentations officielles de la fiche : chaque école prend celle que son CAP demande. */
    public const MODELES = [
        'standard' => 'Fiche avec Décision CAP et Observation',
        'detaillee' => 'Fiche détaillée : années passées par classe, renseignements généraux',
    ];

    public const STATUTS_ECOLE = ['publique' => 'Publique', 'communautaire' => 'Communautaire', 'prive' => 'Privé', 'medersa' => 'Médersas'];

    public const RECRUTEMENTS = ['annuel' => 'Annuel', 'biennal' => 'Biennal'];

    /** Fiche détaillée : tout le fondamental au Mali ; au premier cycle, sans les colonnes propres au second. */
    public static function detailleeDisponible(Classe $classe): bool
    {
        $niveau = ExamenNational::extractClasseLevel($classe->nom_classe);

        return $niveau !== null && $niveau >= 1 && $niveau <= 9 && ExamenNational::estMali((int) $classe->idEcole);
    }

    /** Renseignements généraux : ceux de la fiche, sinon de la dernière fiche de l'école, sinon de sa fiche et de son rapport succinct. */
    public static function renseignements(int $idEcole, ?array $saisis): array
    {
        if ($saisis) {
            return $saisis;
        }
        $precedents = DB::table('propositions_passage')->where('id_ecole', $idEcole)->whereNotNull('renseignements')->latest('updated_at')->value('renseignements');
        if ($precedents) {
            return json_decode($precedents, true);
        }
        $ecole = DB::table('ecole')->where('idEcole', $idEcole)->first(['telephone', 'statut', 'franco_arabe']);
        $rapport = json_decode((string) DB::table('rapports_succincts')->where('id_ecole', $idEcole)->latest('updated_at')->value('donnees'), true) ?: [];
        $statut = strtolower(trim((string) $ecole?->statut));

        return [
            'commune' => $rapport['commune'] ?? null,
            'telephone' => $ecole?->telephone,
            'statut' => $ecole?->franco_arabe ? 'medersa' : ($statut === 'public' ? 'publique' : ($statut === 'prive' ? 'prive' : null)),
            'recrutement' => null,
        ];
    }

    /** « Moussa TRAORE » ou « TRAORE Moussa » → [prénoms, nom]. Sans indice : le dernier mot est le nom. @return array{0: string, 1: string} */
    public static function decouperNom(?string $nomComplet): array
    {
        $mots = preg_split('/\s+/u', trim((string) $nomComplet), -1, PREG_SPLIT_NO_EMPTY);
        if (count($mots) < 2) {
            return [$mots[0] ?? '', ''];
        }
        $majuscules = fn (string $mot) => $mot === mb_strtoupper($mot);
        if ($majuscules($mots[0]) && !$majuscules(end($mots))) {
            return [implode(' ', array_slice($mots, 1)), $mots[0]];
        }

        return [implode(' ', array_slice($mots, 0, -1)), end($mots)];
    }

    /** Avis écrit comme sur la fiche détaillée : accordé au sexe, « Admis(e) » pour une classe à examen. */
    public static function avis(?string $decision, string $sexe, bool $examen): string
    {
        $fille = $sexe === 'F';

        return match ($decision) {
            'passe' => $examen ? ($fille ? 'Admise' : 'Admis') : 'Passe',
            'redouble' => 'Redouble',
            'exclu' => $fille ? 'Exclue' : 'Exclu',
            'abandon' => 'Abandon',
            default => '',
        };
    }

    /**
     * Colonnes de la fiche détaillée, déduites des redoublements connus, comme sur le modèle : années passées par classe,
     * entrée en 7e (année de la fiche moins les années déjà passées au second cycle), recrutement (entrée en 7e moins la
     * scolarité du 1er cycle).
     *
     * @param  list<int>  $doublements
     * @return array<string, mixed>
     */
    public static function colonnesDetaillees(?int $niveau, array $doublements, int $anneeDebut): array
    {
        if ($niveau === null || $niveau < 1 || $niveau > 9) {
            return array_fill_keys(['annee_recrutement', 'doublees_1er', 'scolarite_1er', 'annee_7e', 'annees_7', 'annees_8', 'annees_9'], null);
        }
        $premierCycle = array_values(array_filter($doublements, fn (int $n) => $n < 7));
        if ($niveau < 7) {
            // Premier cycle : scolarité jusqu'à la classe actuelle, recrutement déduit.
            $scolarite = $niveau + count($premierCycle);

            return [
                'annee_recrutement' => $anneeDebut - ($scolarite - 1),
                'doublees_1er' => $premierCycle ? implode(';', array_map(fn (int $n) => $n === 1 ? '1ère' : $n.'ème', $premierCycle)) : 'Néant',
                'scolarite_1er' => $scolarite, 'annee_7e' => null, 'annees_7' => null, 'annees_8' => null, 'annees_9' => null,
            ];
        }
        $annees = [];
        foreach ([7, 8, 9] as $n) {
            $annees[$n] = $n <= $niveau ? 1 + count(array_keys($doublements, $n)) : null;
        }
        $entree = $anneeDebut - (array_sum($annees) - 1);
        $scolarite = 6 + count($premierCycle);

        return [
            'annee_recrutement' => $entree - $scolarite,
            'doublees_1er' => $premierCycle ? implode(';', array_map(fn (int $n) => $n === 1 ? '1ère' : $n.'ème', $premierCycle)) : 'Néant',
            'scolarite_1er' => $scolarite,
            'annee_7e' => $entree,
            'annees_7' => $annees[7],
            'annees_8' => $annees[8],
            'annees_9' => $annees[9],
        ];
    }

    public static function classes(int $idEcole)
    {
        return Classe::where('idEcole', $idEcole)->whereIn('ordreEnseignement', self::ORDRES)->orderBy('nom_classe')->get();
    }

    public static function ecoleConcernee(?int $idEcole): bool
    {
        return $idEcole && !ModelesBulletin::estEcoleSante(DB::table('ecole')->where('idEcole', $idEcole)->value('typeEcole'))
            && DB::table('classe')->where('idEcole', $idEcole)->whereIn('ordreEnseignement', self::ORDRES)->exists();
    }

    public static function fille(?string $genre): bool
    {
        return in_array(mb_strtolower(trim((string) $genre)), self::FILLES, true);
    }

    /** @return array<int|string, array<string, mixed>> une ligne par élève, indexée par id_eleve */
    public static function calculer(Classe $classe, int $idAnnee): array
    {
        $idEcole = (int) $classe->idEcole;
        $seuil = Ordres::surDix($classe->ordreEnseignement) ? 5.0 : 10.0;
        $niveau = ExamenNational::extractClasseLevel($classe->nom_classe);
        $examen = ExamenNational::pourClasse($classe, $idEcole);

        // Actifs de la classe, et ceux qui l'ont quittée en abandon dans l'année (le modèle les liste).
        $eleves = DB::table('eleve')->where('id_ecole', $idEcole)->where('id_classe', $classe->id_classe)->where('id_annee', $idAnnee)
            ->whereIn('etat_dossier', [0, 2])->orderBy('nom_eleve')->orderBy('prenom_eleve')->get();
        $ids = $eleves->pluck('id_eleve');

        $moyennes = MoyennesAnnuelles::pour($classe, $idAnnee, ModelesBulletin::calcul(ModelesBulletin::pourClasse($classe)));
        $resultats = $examen && SchemaExiste::table('resultats_def_terminal')
            ? DB::table('resultats_def_terminal')->whereIn('id_eleve', $ids)->where('id_annee', $idAnnee)->where('niveau_examen', $examen)
                ->where(fn ($q) => $q->whereNull('id_classe')->orWhere('id_classe', $classe->id_classe))->orderBy('date_resultat')->orderBy('id')->get()->keyBy('id_eleve')
            : collect();

        $parents = DB::table('ligneparents_eleves as l')->join('parents as p', 'p.id_parent', '=', 'l.id_parent')->whereIn('l.id_eleve', $ids)
            ->get(['l.id_eleve', 'l.lien_parent', 'p.nom_prenom_parent', 'p.genre'])->groupBy('id_eleve');

        // Redoublements déjà enregistrés dans KalanNet, jusqu'à l'année de la fiche.
        $debut = DB::table('anneescolaire')->where('id_anneeScolaire', $idAnnee)->value('date_debut');
        $anneeDebut = (int) substr((string) $debut, 0, 4);
        $doublees = DB::table('ligne_reinscription as lr')->join('reinscription as r', 'r.id_reinscription', '=', 'lr.id_reinscription')
            ->join('classe as c', 'c.id_classe', '=', 'lr.id_classe')->join('anneescolaire as a', 'a.id_anneeScolaire', '=', 'lr.id_annee')
            ->where('r.statut', 'redoublant')->whereIn('lr.id_eleve', $ids)->where('a.date_debut', '<=', $debut)
            ->orderBy('a.date_debut')->get(['lr.id_eleve', 'c.nom_classe'])->groupBy('id_eleve');

        // Règles lues sur les fiches du Mali : ailleurs, le conseil des maîtres décide des exclusions.
        $reglesMali = ExamenNational::estMali($idEcole);

        $lignes = [];
        foreach ($eleves as $eleve) {
            $moyenne = $moyennes->pourEleve($eleve->id_eleve)['moyenne'] ?? null;
            $moyenne = $moyenne !== null ? round((float) $moyenne, 2) : null;
            $resultat = $resultats->get($eleve->id_eleve);

            $doublements = $doublees->get($eleve->id_eleve, collect())->map(fn ($d) => ExamenNational::extractClasseLevel($d->nom_classe))->filter()->values()->all();
            $echec = $reglesMali ? self::apresEchec($niveau, $doublements, $moyenne) : 'redouble';
            $proposition = match (true) {
                (int) $eleve->etat_dossier === 2 => 'abandon',
                $resultat?->decision !== null && $resultat?->decision !== '' => Str::lower(Str::ascii((string) $resultat->decision)) === 'admis' ? 'passe' : $echec,
                $moyenne === null => null, // à décider par le conseil : jamais de redoublement par manque de notes
                default => $moyenne >= $seuil ? 'passe' : $echec,
            };

            $liens = $parents->get($eleve->id_eleve, collect());
            $parent = fn (array $mots) => $liens->first(fn ($p) => in_array(mb_strtolower(trim((string) $p->lien_parent)), $mots, true))?->nom_prenom_parent;
            $classesDoublees = $doublees->get($eleve->id_eleve, collect())->map(fn ($d) => self::abrege($d->nom_classe))->values();

            $lignes[$eleve->id_eleve] = [
                'id_eleve' => (int) $eleve->id_eleve,
                'prenom' => (string) $eleve->prenom_eleve,
                'nom' => (string) $eleve->nom_eleve,
                'sexe' => self::fille($eleve->genre_eleve) ? 'F' : 'M',
                'date_naissance' => DateNaissance::pourSaisie($eleve->date_naissance),
                'lieu_naissance' => $eleve->lieu_naiss,
                'matricule' => $eleve->matricule,
                'moyenne' => $moyenne,
                'resultat_examen' => $resultat?->decision,
                'pere' => $parent(['père', 'pere']),
                'mere' => $parent(['mère', 'mere']),
                'scolarite' => $niveau !== null ? $niveau + $classesDoublees->count() : null,
                'doublees' => $classesDoublees->isEmpty() ? 'Néant' : $classesDoublees->implode('-'),
                'proposition' => $proposition,
                'observation' => null,
            ] + self::colonnesDetaillees($niveau, $doublements, $anneeDebut);
        }

        return self::trier($lignes);
    }

    /**
     * Élève qui n'a pas réussi : redouble ou exclu, d'après les règles de la fiche officielle.
     * Deux redoublements au plus dans le cycle, sur des années différentes ; exception : la 9e peut être doublée deux fois
     * par un élève qui n'avait jamais doublé avant. Moyenne de 0 : exclu. Le conseil des maîtres peut toujours changer.
     *
     * @param  list<int>  $doublements  niveau de chaque redoublement déjà fait
     */
    public static function apresEchec(?int $niveau, array $doublements, ?float $moyenne = null): string
    {
        if ($moyenne !== null && $moyenne <= 0.0) {
            return 'exclu';
        }
        if ($niveau === null) {
            return 'redouble';
        }

        // 1er cycle : 1re à 6e ; 2d cycle : 7e à 9e. Les redoublements du 1er cycle ne comptent pas au second.
        $cycle = array_filter($doublements, fn (int $n) => ($n >= 7) === ($niveau >= 7));
        $ici = count(array_filter($cycle, fn (int $n) => $n === $niveau));
        $avant = count($cycle) - $ici;

        if ($niveau === 9 && $avant === 0) {
            return $ici < 2 ? 'redouble' : 'exclu';
        }

        return $ici === 0 && $avant < 2 ? 'redouble' : 'exclu';
    }

    /** « 7ème année A » → « 7èA ». */
    private static function abrege(?string $nom): string
    {
        $niveau = ExamenNational::extractClasseLevel($nom);

        return $niveau === null ? (string) $nom : $niveau.'è'.mb_strtoupper(ClasseSuivante::groupe($nom));
    }

    /** Comme sur le modèle : passants, redoublants, puis exclus et abandons, chaque groupe par moyenne décroissante. */
    public static function trier(array $lignes): array
    {
        uasort($lignes, fn ($a, $b) => [self::ORDRE[$a['proposition'] ?? ''] ?? 4, -($a['moyenne'] ?? -1), $a['nom'], $a['prenom']]
            <=> [self::ORDRE[$b['proposition'] ?? ''] ?? 4, -($b['moyenne'] ?? -1), $b['nom'], $b['prenom']]);

        return $lignes;
    }

    /** Récapitulatif par décision et par sexe. @return array<string, array{g: int, f: int, t: int}> */
    public static function recap(array $lignes, string $champ = 'proposition'): array
    {
        $recap = array_fill_keys([...array_keys(self::DECISIONS), 'total'], ['g' => 0, 'f' => 0, 't' => 0]);
        foreach ($lignes as $ligne) {
            $decision = $ligne[$champ] ?? null;
            $sexe = ($ligne['sexe'] ?? 'M') === 'F' ? 'f' : 'g';
            if (isset(self::DECISIONS[$decision])) {
                $recap[$decision][$sexe]++;
                $recap[$decision]['t']++;
                $recap['total'][$sexe]++;
                $recap['total']['t']++;
            }
        }

        return $recap;
    }

    /** En-tête du document. @return array<string, mixed> */
    public static function entete(Classe $classe, object $annee): array
    {
        $ecole = DB::table('ecole as e')->leftJoin('cap as c', 'c.id_cap', '=', 'e.id_cap')->leftJoin('academie as a', 'a.id_academie', '=', 'e.id_academie')
            ->leftJoin('pays as p', 'p.id', '=', 'e.id_pays')->where('e.idEcole', $classe->idEcole)
            ->first(['e.nomEcole', 'e.adresse', 'e.cap', 'e.academie', 'c.nom_cap', 'c.localite_cap', 'a.nom_academie', 'p.entete_document_gauche', 'p.entete_document_droite']);

        return [
            'gauche' => $ecole->entete_document_gauche ?: 'Ministère de l\'Education Nationale',
            'droite' => $ecole->entete_document_droite ?: 'République du Mali',
            'academie' => $ecole->nom_academie ?: $ecole->academie,
            'cap' => $ecole->nom_cap ?: $ecole->cap,
            'localite_cap' => $ecole->localite_cap ?: $ecole->nom_cap ?: $ecole->cap,
            'ecole' => $ecole->nomEcole,
            'lieu' => $ecole->adresse,
            'annee' => $annee->annee,
            'classe' => $classe->nom_classe,
            'cycle' => Ordres::surDix($classe->ordreEnseignement) ? 'PREMIER CYCLE' : 'SECOND CYCLE',
            'examen' => ExamenNational::pourClasse($classe, (int) $classe->idEcole) !== null,
        ];
    }
}
