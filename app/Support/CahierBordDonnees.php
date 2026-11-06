<?php

namespace App\Support;

use App\Models\Ecole;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * « Cahier de bord » d'un établissement : une fiche par cycle. Jardin d'enfants, 1er et 2e cycle sont remis au CAP, le
 * secondaire (général, technique et professionnel) à l'académie ; les écoles de santé ne sont pas concernées. Effectifs,
 * enseignants et classes tenues viennent de KalanNet ; ce que KalanNet ne connaît pas (dates de carrière, salles,
 * tables-bancs, équipements) est saisi une fois puis repris de la fiche de l'année précédente.
 */
class CahierBordDonnees
{
    public const CYCLES = ['jardin' => "Jardin d'enfants", 'premier' => '1er cycle', 'second' => '2e cycle', 'secondaire' => 'Secondaire'];

    private const ORDRES = ['jardin' => ['jardin'], 'premier' => ['fondamentale1'], 'second' => ['fondamentale2'], 'secondaire' => RapportSuccinctDonnees::SECONDAIRE];

    public const SALLES = ['dur' => 'Dur', 'semi_dur' => 'Semi-dur', 'banco' => 'Banco', 'autres' => 'Autres'];

    public const TABLES = ['p2_bon' => 'À 2 places, en bon état', 'p2_mauvais' => 'À 2 places, en mauvais état', 'p4_bon' => 'À 4 places, en bon état', 'p4_mauvais' => 'À 4 places, en mauvais état'];

    public const EQUIPEMENTS = ['bureaux' => 'Bureaux maîtres', 'chaises' => 'Chaises maîtres', 'tableaux_muraux' => 'Tableaux muraux', 'tableaux_chevalet' => 'Tableaux à chevalet', 'armoires' => 'Armoires', 'toilettes' => 'Toilettes', 'toilettes_bon' => 'Toilettes en bon état', 'toilettes_mauvais' => 'Toilettes en mauvais état'];

    /** Colonnes de carrière d'une personne (enseignant ou personnel administratif). */
    public const DATES = ['recrutement' => 'Recrutement', 'integration' => 'Intégration fonction publique', 'titularisation' => 'Titularisation', 'arrivee_cap' => 'Arrivée CAP', 'dernier_poste' => 'Dernier poste', 'arrivee_ecole' => "Arrivée dans l'école"];

    /** Champs d'un enseignant que l'école complète ; l'identité vient de KalanNet. */
    public const CHAMPS_ENSEIGNANT = ['echelon', 'sf', 'fonction', 'specialite', 'classes', 'diplome', 'recrutement', 'integration', 'titularisation', 'arrivee_cap', 'dernier_poste', 'arrivee_ecole', 'contact'];

    public const CHAMPS_ADMINISTRATIF = ['nom', 'naissance', 'matricule', 'echelon', 'sf', 'fonction', 'diplome', 'recrutement', 'integration', 'titularisation', 'arrivee_cap', 'dernier_poste', 'arrivee_ecole', 'contact'];

    /** Cycles présents dans l'école, dans l'ordre du modèle. @return list<string> */
    public static function cycles(?int $idEcole): array
    {
        if (!$idEcole || ModelesBulletin::estEcoleSante(DB::table('ecole')->where('idEcole', $idEcole)->value('typeEcole'))) {
            return [];
        }
        $ordres = DB::table('classe')->where('idEcole', $idEcole)->whereNotNull('ordreEnseignement')->distinct()->pluck('ordreEnseignement')->all();

        return array_values(array_filter(array_keys(self::ORDRES), fn ($cycle) => array_intersect(self::ORDRES[$cycle], $ordres) !== []));
    }

    /** Le secondaire remet son cahier à l'académie, le reste au CAP. */
    public static function versAcademie(string $cycle): bool
    {
        return $cycle === 'secondaire';
    }

    /** @return Collection<int, object> */
    private static function classes(int $idEcole, string $cycle): Collection
    {
        return DB::table('classe')->where('idEcole', $idEcole)->whereIn('ordreEnseignement', self::ORDRES[$cycle])->orderBy('nom_classe')->get(['id_classe', 'nom_classe']);
    }

    /** Lignes fixes du tableau des effectifs (le secondaire a les siennes, par série ou filière). @return array<string, string> */
    public static function niveaux(string $cycle): array
    {
        return match ($cycle) {
            'jardin' => ['ps' => 'Section des Petits', 'ms' => 'Section des Moyens', 'gs' => 'Section des Grands'],
            'secondaire' => [],
            'premier' => ['1' => '1ère A', '2' => '2è A', '3' => '3è A', '4' => '4è A', '5' => '5è A', '6' => '6è A'],
            default => ['7' => '7è A', '8' => '8è A', '9' => '9è A'],
        };
    }

    /** Ligne d'une classe : sa section au jardin, son année ailleurs ; null si elle n'est pas reconnue. */
    public static function niveauDe(string $cycle, ?string $nom): ?string
    {
        if ($cycle === 'jardin') {
            $texte = Str::lower(Str::ascii((string) $nom));

            return match (true) {
                (bool) preg_match('/petit|\bps\b/', $texte) => 'ps',
                (bool) preg_match('/moyen|\bms\b/', $texte) => 'ms',
                (bool) preg_match('/grand|\bgs\b/', $texte) => 'gs',
                default => null,
            };
        }
        $niveau = ExamenNational::extractClasseLevel($nom);

        return $niveau !== null && isset(self::niveaux($cycle)[(string) $niveau]) ? (string) $niveau : null;
    }

    /** Classes absentes du tableau des effectifs, à signaler. @return list<string> */
    public static function classesNonReconnues(int $idEcole, string $cycle): array
    {
        if ($cycle === 'secondaire') {
            return []; // une série inconnue a sa propre ligne
        }
        return self::classes($idEcole, $cycle)->filter(fn ($c) => self::niveauDe($cycle, $c->nom_classe) === null)->pluck('nom_classe')->values()->all();
    }

    /** Fiche pré-remplie : chiffres de KalanNet, et ce qui ne change pas d'une année à l'autre repris de la fiche précédente. */
    public static function calculer(Ecole $ecole, int $idAnnee, string $cycle, string $directeur): array
    {
        $idEcole = (int) $ecole->idEcole;
        $classes = self::classes($idEcole, $cycle);

        $donnees = [
            'cycle' => $cycle,
            'ecole' => (string) $ecole->nomEcole,
            'directeur' => $directeur,
            'contact' => $ecole->telephone,
            'depuis' => null,
            'regime' => null,
            'commune' => null,
            'academie' => DB::table('academie')->where('id_academie', $ecole->id_academie)->value('nom_academie') ?: $ecole->academie,
            'cap' => DB::table('cap')->where('id_cap', $ecole->id_cap)->value('nom_cap') ?: $ecole->cap,
            'effectifs' => $cycle === 'secondaire'
                ? RapportSuccinctDonnees::calculer($ecole, $idAnnee, 'secondaire')['groupes']
                : self::effectifs($idEcole, $idAnnee, $cycle, $classes),
            'administratifs' => array_fill(0, self::lignesAdministratives($cycle), array_fill_keys(self::CHAMPS_ADMINISTRATIF, null)),
            'enseignants' => self::enseignants($idEcole, $cycle, $classes),
            'salles' => array_fill_keys(array_keys(self::SALLES), null),
            'date_creation' => null,
            'date_ouverture' => null,
            'tables' => [],
            'equipements' => array_fill_keys(array_keys(self::EQUIPEMENTS), null),
        ];
        $donnees['tables'] = collect($donnees['effectifs'])->mapWithKeys(fn ($l) => [$l['cle'] => array_fill_keys(array_keys(self::TABLES), null)])->all();
        $donnees['administratifs'][0]['nom'] = $directeur;
        $donnees['administratifs'][0]['fonction'] = $cycle === 'secondaire' ? 'Proviseur' : 'Directeur';

        return self::reprendre($donnees, self::precedente($idEcole, $idAnnee, $cycle));
    }

    /** Un lycée a plus de personnel administratif qu'une école. */
    public static function lignesAdministratives(string $cycle): int
    {
        return $cycle === 'secondaire' ? 8 : 4;
    }

    /** Dernière fiche d'une année antérieure, pour ce cycle. */
    private static function precedente(int $idEcole, int $idAnnee, string $cycle): ?array
    {
        $debut = DB::table('anneescolaire')->where('id_anneeScolaire', $idAnnee)->value('date_debut');
        $fiche = DB::table('cahiers_bord as c')->join('anneescolaire as a', 'a.id_anneeScolaire', '=', 'c.id_annee')
            ->where('c.id_ecole', $idEcole)->where('c.cycle', $cycle)->where('a.date_debut', '<', $debut)->orderByDesc('a.date_debut')->value('c.donnees');

        return $fiche ? json_decode($fiche, true) : null;
    }

    private static function reprendre(array $donnees, ?array $avant): array
    {
        if (!$avant) {
            return $donnees;
        }
        foreach (['depuis', 'regime', 'commune', 'contact', 'date_creation', 'date_ouverture'] as $champ) {
            $donnees[$champ] = $avant[$champ] ?? $donnees[$champ];
        }
        foreach (['salles', 'equipements'] as $bloc) {
            $donnees[$bloc] = $avant[$bloc] ?? $donnees[$bloc];
        }
        foreach ($avant['administratifs'] ?? [] as $i => $personne) {
            if (isset($donnees['administratifs'][$i])) {
                $donnees['administratifs'][$i] = $personne;
            }
        }
        foreach ($donnees['tables'] as $cle => $ligne) {
            $donnees['tables'][$cle] = $avant['tables'][$cle] ?? $ligne;
        }
        // Enseignants toujours présents : leurs dates et leur échelon ne se ressaisissent pas.
        $anciens = collect($avant['enseignants'] ?? [])->keyBy('id');
        foreach ($donnees['enseignants'] as $i => $enseignant) {
            foreach (['echelon', 'sf', 'fonction', 'diplome', 'recrutement', 'integration', 'titularisation', 'arrivee_cap', 'dernier_poste', 'arrivee_ecole'] as $champ) {
                if (filled($anciens[$enseignant['id']][$champ] ?? null)) {
                    $donnees['enseignants'][$i][$champ] = $anciens[$enseignant['id']][$champ];
                }
            }
        }

        return $donnees;
    }

    private static function effectifs(int $idEcole, int $idAnnee, string $cycle, Collection $classes): array
    {
        $lignes = [];
        foreach (self::niveaux($cycle) as $cle => $libelle) {
            $lignes[$cle] = ['cle' => (string) $cle, 'libelle' => $libelle, 'groupes' => 0, 'g' => 0, 'f' => 0, 'rg' => 0, 'rf' => 0];
        }
        $niveaux = $classes->mapWithKeys(fn ($c) => [$c->id_classe => self::niveauDe($cycle, $c->nom_classe)]);

        $eleves = DB::table('eleve as e')->where('e.id_ecole', $idEcole)->where('e.id_annee', $idAnnee)->where('e.etat_dossier', 0)
            ->whereIn('e.id_classe', $classes->pluck('id_classe'))
            ->selectRaw('e.id_classe, e.genre_eleve, EXISTS (SELECT 1 FROM ligne_reinscription lr JOIN reinscription r ON r.id_reinscription = lr.id_reinscription
                WHERE lr.id_eleve = e.id_eleve AND lr.id_annee = e.id_annee AND r.statut = ?) as redouble', ['redoublant'])
            ->get();

        $avecEleves = [];
        foreach ($eleves as $eleve) {
            $niveau = $niveaux[$eleve->id_classe] ?? null;
            if ($niveau === null) {
                continue;
            }
            $fille = PropositionPassageDonnees::fille($eleve->genre_eleve);
            $lignes[$niveau][$fille ? 'f' : 'g']++;
            if ($eleve->redouble) {
                $lignes[$niveau][$fille ? 'rf' : 'rg']++;
            }
            $avecEleves[$niveau][$eleve->id_classe] = true;
        }
        foreach ($avecEleves as $niveau => $classesDuNiveau) {
            $lignes[$niveau]['groupes'] = count($classesDuNiveau);
        }

        return array_values($lignes);
    }

    /** Enseignants du cycle : tous si l'école n'a que ce cycle, sinon ceux qui y tiennent une classe. */
    private static function enseignants(int $idEcole, string $cycle, Collection $classes): array
    {
        $ids = $classes->pluck('id_classe');
        $tenues = DB::table('classe_maitres as m')->join('classe as c', 'c.id_classe', '=', 'm.id_classe')->whereIn('m.id_classe', $ids)->get(['m.id_enseignant as id', 'c.nom_classe'])
            ->merge(DB::table('ligneclasse as l')->join('classe as c', 'c.id_classe', '=', 'l.id_classe')->whereIn('l.id_classe', $ids)->whereNotNull('l.id_enseignants')->get(['l.id_enseignants as id', 'c.nom_classe']))
            ->groupBy('id')->map(fn ($lignes) => $lignes->pluck('nom_classe')->unique()->sort()->implode(', '));

        $requete = DB::table('enseignants')->where('id_ecole', $idEcole)->where(fn ($q) => $q->where('is_deleted', 0)->orWhereNull('is_deleted'))->orderBy('nom_prenom_enseignant');
        if (DB::table('classe')->where('idEcole', $idEcole)->count() !== $classes->count()) {
            $requete->whereIn('id_enseignant', $tenues->keys());
        }

        return $requete->get()->map(fn ($e) => [
            'id' => (int) $e->id_enseignant,
            'nom' => (string) $e->nom_prenom_enseignant,
            'femme' => PropositionPassageDonnees::fille($e->genre_enseignant),
            'naissance' => DateNaissance::pourSaisie($e->date_naissance_enseignant),
            'matricule' => $e->matricule,
            'echelon' => null,
            'sf' => $e->statut_matrimonial,
            'fonction' => match ($cycle) { 'jardin' => 'Éducateur', 'secondaire' => 'Professeur', default => 'Maître' },
            'specialite' => $e->specialite,
            'classes' => $tenues[$e->id_enseignant] ?? null,
            'diplome' => $e->diplome_enseignant,
            'recrutement' => null, 'integration' => null, 'titularisation' => null, 'arrivee_cap' => null, 'dernier_poste' => null, 'arrivee_ecole' => null,
            'contact' => $e->telephone_enseignant,
        ])->values()->all();
    }

    /** Ce qui manque avant de remettre la fiche au CAP. @return list<string> */
    public static function champsManquants(array $donnees): array
    {
        $manquants = collect(['directeur' => 'Dirigée par', 'commune' => 'Commune', 'regime' => 'Régime de recrutement'])
            ->filter(fn ($libelle, $champ) => blank($donnees[$champ] ?? null))->values()->all();
        if (collect($donnees['salles'] ?? [])->sum(fn ($n) => (int) $n) === 0) {
            $manquants[] = 'Salles de classe';
        }

        return $manquants;
    }
}
