<?php

namespace App\Support;

use App\Models\Classe;
use App\Models\Ecole;
use App\Services\Bulletins\MoyennesAnnuelles;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Support\RapportSecondaireDefinitions as Def;

/**
 * Rapports de rentrée et de fin d'année du secondaire, remis à l'académie : pré-remplis depuis KalanNet, complétés par
 * l'établissement. Les lignes sont celles de l'établissement : une par série du général présente, une par classe du
 * technique et professionnel (même découpage que le rapport succinct).
 */
class RapportSecondaireDonnees
{
    private const TERMINALES = ['TLL', 'TAL', 'TSS', 'TSECO', 'TSE', 'TSEXP'];

    /** @return array{series: array<string, string>, terminales: array<string, string>, techniques: array<string, string>, classes: array<int, string>} */
    public static function lignes(int $idEcole): array
    {
        $classes = DB::table('classe')->where('idEcole', $idEcole)->whereIn('ordreEnseignement', RapportSuccinctDonnees::SECONDAIRE)->orderBy('nom_classe')
            ->get(['id_classe', 'nom_classe', 'serie', 'ordreEnseignement']);
        $cles = [];
        $autres = [];
        $techniques = [];
        foreach ($classes as $c) {
            $serie = $c->ordreEnseignement === 'secondairegenerale' ? RapportSuccinctDonnees::serie($c->nom_classe, $c->serie) : null;
            $cles[$c->id_classe] = $serie ?? 'c'.$c->id_classe;
            if (!$serie) {
                $autres['c'.$c->id_classe] = (string) $c->nom_classe;
                if ($c->ordreEnseignement === 'secondairetechniqueetprofessionnel') {
                    $techniques['c'.$c->id_classe] = (string) $c->nom_classe;
                }
            }
        }
        $generales = array_filter(RapportSuccinctDonnees::SERIES, fn ($l, $cle) => in_array($cle, $cles, true), ARRAY_FILTER_USE_BOTH);

        return [
            'series' => $generales + $autres,
            'terminales' => array_intersect_key($generales, array_flip(self::TERMINALES)),
            'techniques' => $techniques,
            'classes' => $cles,
        ];
    }

    /** @return list<array<string, mixed>> */
    public static function tableaux(string $type, int $idEcole): array
    {
        $l = self::lignes($idEcole);

        return Def::tableaux($type, $l['series'], $l['terminales'], $l['techniques']);
    }

    /** Années de lycée attendues sans redoublement : 1 en 10e (ou 1re année), 2 en 11e, 3 en terminale. */
    private static function niveau(string $cle, array $nomsClasses): int
    {
        return match (true) {
            $cle === '10CG' => 1,
            str_starts_with($cle, '11') => 2,
            in_array($cle, self::TERMINALES, true) => 3,
            default => max(1, min(3, (int) (ExamenNational::extractClasseLevel($nomsClasses[$cle] ?? '') ?? 1))),
        };
    }

    /** Élèves du secondaire de l'année, avec leur ligne, leur sexe et leurs redoublements au lycée. */
    private static function eleves(int $idEcole, int $idAnnee, array $classes): Collection
    {
        $eleves = DB::table('eleve')->where('id_ecole', $idEcole)->where('id_annee', $idAnnee)->whereIn('etat_dossier', [0, 2])
            ->whereIn('id_classe', array_keys($classes))->get(['id_eleve', 'id_classe', 'genre_eleve', 'date_naissance', 'etat_dossier']);
        $debut = DB::table('anneescolaire')->where('id_anneeScolaire', $idAnnee)->value('date_debut');
        $redoublements = DB::table('ligne_reinscription as lr')->join('reinscription as r', 'r.id_reinscription', '=', 'lr.id_reinscription')
            ->join('classe as c', 'c.id_classe', '=', 'lr.id_classe')->join('anneescolaire as a', 'a.id_anneeScolaire', '=', 'lr.id_annee')
            ->where('r.statut', 'redoublant')->whereIn('c.ordreEnseignement', RapportSuccinctDonnees::SECONDAIRE)->whereIn('lr.id_eleve', $eleves->pluck('id_eleve'))
            ->where('a.date_debut', '<=', $debut)->get(['lr.id_eleve', 'lr.id_annee']);

        return $eleves->map(function ($e) use ($classes, $redoublements, $idAnnee) {
            $e->cle = $classes[$e->id_classe];
            $e->sexe = PropositionPassageDonnees::fille($e->genre_eleve) ? 'f' : 'g';
            $e->actif = (int) $e->etat_dossier === 0;
            $e->redoublements = $redoublements->where('id_eleve', $e->id_eleve)->count();
            $e->redouble = $redoublements->where('id_eleve', $e->id_eleve)->where('id_annee', $idAnnee)->isNotEmpty();

            return $e;
        });
    }

    /** Enseignants du secondaire : tous si l'établissement n'a pas d'autre ordre, sinon ceux qui y tiennent une classe. */
    private static function enseignants(int $idEcole, array $classes): Collection
    {
        $requete = DB::table('enseignants')->where('id_ecole', $idEcole)->where(fn ($q) => $q->where('is_deleted', 0)->orWhereNull('is_deleted'));
        if (DB::table('classe')->where('idEcole', $idEcole)->count() !== count($classes)) {
            $ids = DB::table('ligneclasse')->whereIn('id_classe', array_keys($classes))->whereNotNull('id_enseignants')->pluck('id_enseignants')
                ->merge(DB::table('classe_maitres')->whereIn('id_classe', array_keys($classes))->pluck('id_enseignant'))->unique();
            $requete->whereIn('id_enseignant', $ids);
        }

        return $requete->get(['id_enseignant', 'genre_enseignant']);
    }

    /** Résultats du Bac d'une année, par ligne de terminale. */
    private static function bac(int $idEcole, string $annee, array $classes): Collection
    {
        return DB::table('resultats_def_terminal as r')->join('eleve as e', 'e.id_eleve', '=', 'r.id_eleve')
            ->where('e.id_ecole', $idEcole)->whereIn('r.id_annee', DB::table('anneescolaire')->where('annee', $annee)->pluck('id_anneeScolaire'))
            ->where('r.niveau_examen', 'BAC')->whereIn('r.id_classe', array_keys($classes))->get(['r.id_eleve', 'r.id_classe', 'r.decision', 'e.genre_eleve'])
            ->map(function ($r) use ($classes) {
                $r->cle = $classes[$r->id_classe];
                $r->sexe = PropositionPassageDonnees::fille($r->genre_eleve) ? 'f' : 'g';

                return $r;
            });
    }

    private static function ajouter(array &$t, string $code, string $ligne, string $colonne, int $n = 1): void
    {
        $t[$code][$ligne][$colonne] = ($t[$code][$ligne][$colonne] ?? 0) + $n;
    }

    /** Rapport pré-rempli : chiffres de KalanNet, et ce qui change peu repris des rapports précédents. */
    public static function calculer(Ecole $ecole, object $annee, string $type): array
    {
        $idEcole = (int) $ecole->idEcole;
        $lignes = self::lignes($idEcole);
        $classes = $lignes['classes'];
        $eleves = self::eleves($idEcole, (int) $annee->id_anneeScolaire, $classes);
        $actifs = $eleves->where('actif', true);
        $enseignants = self::enseignants($idEcole, $classes);
        $femmes = $enseignants->filter(fn ($e) => PropositionPassageDonnees::fille($e->genre_enseignant))->count();

        $infos = array_fill_keys(array_keys(Def::infos($type)), null);
        $infos = array_merge($infos, [
            'nom' => (string) $ecole->nomEcole, 'telephone' => $ecole->telephone, 'email' => $ecole->email,
            'statut' => strtolower(trim((string) $ecole->statut)) === 'public' ? 'Public' : (strtolower(trim((string) $ecole->statut)) === 'prive' ? 'Privé' : null),
            'effectif_f' => $actifs->where('sexe', 'f')->count(), 'effectif_g' => $actifs->where('sexe', 'g')->count(),
            'groupes' => $actifs->pluck('id_classe')->unique()->count(),
            'enseignants_f' => $femmes, 'enseignants_h' => $enseignants->count() - $femmes,
        ]);

        $t = [];
        if ($type === 'rentree') {
            foreach ($actifs as $e) {
                self::ajouter($t, 'T5', $e->cle, $e->sexe);
                self::ajouter($t, 'T6', $e->cle, ($e->redouble ? 'r_' : 'p_').$e->sexe);
                $annees = min(6, self::niveau($e->cle, $lignes['series']) + $e->redoublements);
                self::ajouter($t, 'T9', 'a'.$annees, $e->cle.'_'.$e->sexe);
                if (isset($lignes['terminales'][$e->cle])) {
                    self::ajouter($t, 'T11', $e->cle, 'a'.min(6, 3 + $e->redoublements).'_'.$e->sexe);
                }
                $age = self::age($e->date_naissance, $annee);
                if ($age !== null) {
                    self::ajouter($t, 'T7', $age < 13 ? 'moins13' : 'a'.min(25, $age), $e->cle.'_'.$e->sexe);
                }
            }
            foreach ($lignes['series'] as $cle => $libelle) {
                $t['T2']['classes'][$cle] = $actifs->where('cle', $cle)->pluck('id_classe')->unique()->count();
            }
            $precedente = DB::table('anneescolaire')->where('date_debut', '<', $annee->date_debut)->orderByDesc('date_debut')->value('annee');
            if ($precedente) {
                foreach (self::bac($idEcole, $precedente, $classes)->where('decision', 'admis') as $r) {
                    if (isset($lignes['terminales'][$r->cle])) {
                        self::ajouter($t, 'T10', $r->cle, 'admis_'.$r->sexe);
                    }
                }
            }
        } else {
            $redoublementsParEleve = $eleves->pluck('redoublements', 'id_eleve');
            foreach ($eleves as $e) {
                $code = match ($e->cle) { '10CG' => 'T6', '11L' => 'T7', '11S' => 'T8', '11SES' => 'T9', default => isset($lignes['techniques'][$e->cle]) ? 'T9b' : null };
                if ($code) {
                    self::ajouter($t, $code, $e->cle, 'effectif_'.$e->sexe);
                    if (!$e->actif) {
                        self::ajouter($t, $code, $e->cle, 'abandon_'.$e->sexe);
                    }
                }
                if ($e->actif && isset($lignes['terminales'][$e->cle])) {
                    self::ajouter($t, 'T11', $e->cle, 'effectif_'.$e->sexe);
                }
            }
            foreach (self::bac($idEcole, (string) $annee->annee, $classes)->where('decision', 'admis') as $r) {
                if (isset($lignes['terminales'][$r->cle])) {
                    self::ajouter($t, 'T11', $r->cle, 'admis_'.$r->sexe);
                    self::ajouter($t, 'T13', $r->cle, 'a'.min(5, 3 + (int) ($redoublementsParEleve[$r->id_eleve] ?? 0)).'_'.$r->sexe);
                }
            }
            foreach (self::tranches($actifs, (int) $annee->id_anneeScolaire) as [$cle, $colonne]) {
                self::ajouter($t, 'T15', $cle, $colonne);
            }
        }

        return self::reprendre(['type' => $type, 'infos' => $infos, 't' => $t], $idEcole, $annee, $type);
    }

    private static function age(?string $naissance, object $annee): ?int
    {
        $date = DateNaissance::pourSaisie($naissance);
        try {
            return $date ? Carbon::createFromFormat('d/m/Y', $date)->diffInYears(Carbon::parse($annee->date_debut)->endOfYear()) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    /** @return list<array{0: string, 1: string}> ligne et colonne du tableau des moyennes, par élève qui a une moyenne */
    private static function tranches(Collection $eleves, int $idAnnee): array
    {
        $cases = [];
        foreach ($eleves->groupBy('id_classe') as $idClasse => $groupe) {
            $classe = Classe::withoutGlobalScopes()->find($idClasse);
            $moyennes = MoyennesAnnuelles::pour($classe, $idAnnee, ModelesBulletin::calcul(ModelesBulletin::pourClasse($classe)));
            foreach ($groupe as $e) {
                $m = $moyennes->pourEleve($e->id_eleve)['moyenne'] ?? null;
                if ($m === null) {
                    continue;
                }
                $m = (float) $m;
                $tranche = match (true) { $m < 7 => 'm7', $m < 10 => 'm10', $m < 12 => 'm12', $m < 14 => 'm14', $m < 16 => 'm16', default => 'm20' };
                $cases[] = [$e->cle, $tranche.'_'.$e->sexe];
            }
        }

        return $cases;
    }

    /** Élèves sans date de naissance : absents du tableau des âges. */
    public static function sansDateDeNaissance(int $idEcole, int $idAnnee): int
    {
        return DB::table('eleve')->where('id_ecole', $idEcole)->where('id_annee', $idAnnee)->where('etat_dossier', 0)
            ->whereIn('id_classe', array_keys(self::lignes($idEcole)['classes']))->where(fn ($q) => $q->whereNull('date_naissance')->orWhere('date_naissance', ''))->count();
    }

    private static function reprendre(array $donnees, int $idEcole, object $annee, string $type): array
    {
        $rapport = fn (string $quel, bool $memeAnnee) => DB::table('rapports_secondaire as r')->join('anneescolaire as a', 'a.id_anneeScolaire', '=', 'r.id_annee')
            ->where('r.id_ecole', $idEcole)->where('r.type', $quel)
            ->when($memeAnnee, fn ($q) => $q->where('a.annee', $annee->annee), fn ($q) => $q->where('a.date_debut', '<', $annee->date_debut))
            ->orderByDesc('a.date_debut')->value('r.donnees');
        $lire = fn ($json) => $json ? json_decode($json, true) : null;

        $precedent = $lire($rapport($type, false));
        $rentree = $type === 'fin' ? $lire($rapport('rentree', true)) : null;
        $source = $rentree ?? $precedent ?? $lire($rapport($type === 'fin' ? 'rentree' : 'fin', false));

        foreach (Def::INFOS_STABLES as $champ) {
            if (blank($donnees['infos'][$champ] ?? null) && filled($source['infos'][$champ] ?? null)) {
                $donnees['infos'][$champ] = $source['infos'][$champ];
            }
        }
        foreach (Def::REPRISE_ANNEE_PRECEDENTE[$type] as $code) {
            if (isset($precedent['t'][$code]) && !isset($donnees['t'][$code])) {
                $donnees['t'][$code] = $precedent['t'][$code];
            }
        }
        if ($rentree) {
            foreach (Def::REPRISE_DE_LA_RENTREE as $code => $depuis) {
                if (isset($rentree['t'][$depuis])) {
                    $donnees['t'][$code] = $rentree['t'][$depuis];
                }
            }
        }

        return $donnees;
    }

    /**
     * Saisie de l'établissement ramenée aux tableaux, lignes et colonnes connus ; nombres entiers ou textes courts.
     *
     * @return array<string, array<string, array<string, int|string>>>
     */
    public static function assainir(array $saisie, array $tableaux): array
    {
        $propre = [];
        foreach ($tableaux as $tableau) {
            $texte = ($tableau['type'] ?? 'nombre') === 'texte';
            $colonnes = collect($tableau['colonnes'])->flatMap(fn ($g) => array_keys($g['cols']))->all();
            foreach (array_keys($tableau['lignes']) as $ligne) {
                foreach ($colonnes as $colonne) {
                    $valeur = $saisie[$tableau['code']][$ligne][$colonne] ?? null;
                    if ($valeur === null || $valeur === '') {
                        continue;
                    }
                    if ($texte) {
                        $propre[$tableau['code']][$ligne][$colonne] = mb_substr(trim((string) $valeur), 0, 150);
                    } elseif (is_numeric($valeur) && (int) $valeur >= 0 && (int) $valeur <= 99999) {
                        $propre[$tableau['code']][$ligne][$colonne] = (int) $valeur;
                    } else {
                        throw \Illuminate\Validation\ValidationException::withMessages(['tableaux' => $tableau['code'].' ('.$tableau['lignes'][$ligne].') : « '.mb_substr((string) $valeur, 0, 20).' » n\'est pas un nombre entier positif.']);
                    }
                }
            }
        }

        return $propre;
    }

    /** Ce qui manque avant de remettre le rapport à l'académie. @return list<string> */
    public static function champsManquants(array $donnees): array
    {
        return collect(['region' => 'Région', 'cercle' => 'Cercle', 'commune' => 'Commune'])->filter(fn ($l, $c) => blank($donnees['infos'][$c] ?? null))->values()->all();
    }
}
