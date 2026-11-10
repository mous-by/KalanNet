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
    public static function tableaux(string $type, int $idEcole, ?object $annee = null): array
    {
        $l = self::lignes($idEcole);
        // Taille des listes nominatives : une ligne par enseignant (rentrée) ou par admis au Bac (fin d'année), plus quelques lignes libres.
        $tailles = ['enseignants' => self::enseignants($idEcole, $l['classes'])->count(), 'admis' => 0];
        if ($type === 'fin' && $annee) {
            $tailles['admis'] = self::bac($idEcole, (string) $annee->annee, $l['classes'])->where('decision', 'admis')->count();
        }

        return Def::tableaux($type, $l['series'], $l['terminales'], $l['techniques'], $tailles);
    }

    /**
     * Année du DEF tirée des résultats nationaux enregistrés dans KalanNet (admis au DEF d'une année scolaire 2025-2026 = CG26),
     * pour les élèves dont la fiche ne la porte pas.
     *
     * @return array<int, int> id_eleve => année
     */
    private static function anneesDuDef(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return DB::table('resultats_def_terminal as r')->join('anneescolaire as a', 'a.id_anneeScolaire', '=', 'r.id_annee')
            ->whereIn('r.id_eleve', $ids)->where('r.niveau_examen', 'DEF')->where('r.decision', 'admis')
            ->get(['r.id_eleve', 'a.annee'])
            ->mapWithKeys(fn ($r) => [(int) $r->id_eleve => (int) substr(trim((string) $r->annee), -4)])
            ->filter(fn ($annee) => $annee > 1980)->all();
    }

    /**
     * Scolarité au lycée (T9) : depuis l'année du DEF (en 2026-2027, CG26 = 1 an, CG25 = 2 ans) ; sans elle, estimée par le
     * niveau et les redoublements. Bornée aux lignes du canevas : 1 à 3 ans en 10e, 2 à 5 en 11e, 3 à 6 en terminale.
     */
    private static function scolarite(object $e, int $debut, array $series): int
    {
        $niveau = self::niveau($e->cle, $series);
        $ans = $e->annee_def ? $debut - (int) $e->annee_def + 1 : $niveau + $e->redoublements;

        return max($niveau, min($niveau === 1 ? 3 : $niveau + 3, $ans));
    }

    /** Élèves du général sans année du DEF (ni sur la fiche, ni dans les résultats nationaux). */
    public static function sansAnneeDuDef(int $idEcole, object $annee): int
    {
        $lignes = self::lignes($idEcole);
        $generales = array_filter($lignes['classes'], fn ($cle) => !str_starts_with($cle, 'c'));

        return self::eleves($idEcole, (int) $annee->id_anneeScolaire, $generales)->where('actif', true)->whereNull('annee_def')->count();
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
            ->whereIn('id_classe', array_keys($classes))->get(['id_eleve', 'id_classe', 'genre_eleve', 'date_naissance', 'etat_dossier', 'annee_def']);
        $defs = self::anneesDuDef($eleves->whereNull('annee_def')->pluck('id_eleve')->all());
        $debut = DB::table('anneescolaire')->where('id_anneeScolaire', $idAnnee)->value('date_debut');
        $redoublements = DB::table('ligne_reinscription as lr')->join('reinscription as r', 'r.id_reinscription', '=', 'lr.id_reinscription')
            ->join('classe as c', 'c.id_classe', '=', 'lr.id_classe')->join('anneescolaire as a', 'a.id_anneeScolaire', '=', 'lr.id_annee')
            ->where('r.statut', 'redoublant')->whereIn('c.ordreEnseignement', RapportSuccinctDonnees::SECONDAIRE)->whereIn('lr.id_eleve', $eleves->pluck('id_eleve'))
            ->where('a.date_debut', '<=', $debut)->get(['lr.id_eleve', 'lr.id_annee']);

        return $eleves->map(function ($e) use ($classes, $redoublements, $idAnnee, $defs) {
            $e->annee_def = $e->annee_def ?: ($defs[$e->id_eleve] ?? null);
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

        return $requete->orderBy('nom_prenom_enseignant')->get(['id_enseignant', 'genre_enseignant', 'nom_prenom_enseignant', 'date_naissance_enseignant', 'matricule', 'specialite', 'statut_matrimonial', 'nombre_enfants']);
    }

    /** Résultats du Bac d'une année, par ligne de terminale. */
    private static function bac(int $idEcole, string $annee, array $classes): Collection
    {
        return DB::table('resultats_def_terminal as r')->join('eleve as e', 'e.id_eleve', '=', 'r.id_eleve')
            ->where('e.id_ecole', $idEcole)->whereIn('r.id_annee', DB::table('anneescolaire')->where('annee', $annee)->pluck('id_anneeScolaire'))
            ->where('r.niveau_examen', 'BAC')->whereIn('r.id_classe', array_keys($classes))->orderBy('e.nom_eleve')->orderBy('e.prenom_eleve')->get(['r.id_eleve', 'r.id_classe', 'r.decision', 'e.genre_eleve', 'e.prenom_eleve', 'e.nom_eleve', 'e.matricule', 'e.annee_def'])
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
                self::ajouter($t, 'T9', 'a'.self::scolarite($e, (int) substr((string) $annee->date_debut, 0, 4), $lignes['series']), $e->cle.'_'.$e->sexe);
                $age = self::age($e->date_naissance, $annee);
                if ($age !== null) {
                    self::ajouter($t, 'T7', $age < 13 ? 'moins13' : 'a'.min(25, $age), $e->cle.'_'.$e->sexe);
                }
            }
            foreach ($lignes['series'] as $cle => $libelle) {
                $t['T2']['classes'][$cle] = $actifs->where('cle', $cle)->pluck('id_classe')->unique()->count();
            }
            foreach ($enseignants->values() as $n => $e) {
                [$prenom, $nom] = PropositionPassageDonnees::decouperNom($e->nom_prenom_enseignant);
                $t['T21']['n'.($n + 1)] = array_filter(['prenoms' => $prenom, 'nom' => $nom, 'sexe' => PropositionPassageDonnees::fille($e->genre_enseignant) ? 'F' : 'H',
                    'naissance' => $e->date_naissance_enseignant ? substr((string) $e->date_naissance_enseignant, 0, 4) : null, 'matricule' => $e->matricule,
                    'specialite' => $e->specialite, 'situation' => $e->statut_matrimonial, 'enfants' => $e->nombre_enfants !== null ? (string) $e->nombre_enfants : null], fn ($v) => filled($v));
            }
            // Bac de la session écoulée : inscrits et admis (T10), admis par scolarité depuis le DEF (T11).
            $precedente = DB::table('anneescolaire')->where('date_debut', '<', $annee->date_debut)->orderByDesc('date_debut')->first(['annee', 'date_debut']);
            if ($precedente) {
                $bac = self::bac($idEcole, (string) $precedente->annee, $classes)->filter(fn ($r) => in_array($r->cle, self::TERMINALES, true));
                $defs = self::anneesDuDef($bac->whereNull('annee_def')->pluck('id_eleve')->all());
                $redoublements = DB::table('ligne_reinscription as lr')->join('reinscription as r', 'r.id_reinscription', '=', 'lr.id_reinscription')
                    ->join('classe as c', 'c.id_classe', '=', 'lr.id_classe')->where('r.statut', 'redoublant')->whereIn('c.ordreEnseignement', RapportSuccinctDonnees::SECONDAIRE)
                    ->whereIn('lr.id_eleve', $bac->pluck('id_eleve'))->selectRaw('lr.id_eleve, COUNT(*) as n')->groupBy('lr.id_eleve')->pluck('n', 'lr.id_eleve');
                $debutSession = (int) substr((string) $precedente->date_debut, 0, 4);
                foreach ($bac as $r) {
                    self::ajouter($t, 'T10', $r->cle, 'inscrits_'.$r->sexe);
                    if ($r->decision !== 'admis') {
                        continue;
                    }
                    self::ajouter($t, 'T10', $r->cle, 'admis_'.$r->sexe);
                    $def = $r->annee_def ?: ($defs[$r->id_eleve] ?? null);
                    $ans = $def ? $debutSession - (int) $def + 1 : 3 + (int) ($redoublements[$r->id_eleve] ?? 0);
                    self::ajouter($t, 'T11', $r->cle, 'a'.max(3, min(6, $ans)).'_'.$r->sexe);
                }
            }
        } else {
            // Élèves de l'année écoulée, même déjà réinscrits, avec la décision prise à la réinscription.
            $membres = self::membresDeLAnnee($idEcole, $annee, $classes);
            $redoublementsParEleve = $membres->pluck('redoublements', 'id_eleve');
            foreach ($membres as $e) {
                $code = match ($e->cle) { '10CG' => 'T6', '11L' => 'T7', '11S' => 'T8', '11SES' => 'T9', default => isset($lignes['techniques'][$e->cle]) ? 'T9b' : null };
                $colonne = self::sortie($e->decision);
                if ($code) {
                    self::ajouter($t, $code, $e->cle, 'effectif_'.$e->sexe);
                    if (in_array($e->decision, ['passant', 'passage_force'], true)) {
                        $cible = $classes[$e->id_classe_cible] ?? null;
                        // Passage en telle série au général, simple passage au technique.
                        $colonne = $code === 'T9b' ? 'passage' : ($cible ? 'p'.$cible : null);
                    } elseif ($e->decision === null && !$e->actif) {
                        $colonne = 'abandon'; // retiré en cours d'année, sans décision de réinscription
                    }
                    if ($colonne) {
                        self::ajouter($t, $code, $e->cle, $colonne.'_'.$e->sexe);
                    }
                }
                if (isset($lignes['terminales'][$e->cle]) && $e->actif) {
                    self::ajouter($t, 'T11', $e->cle, 'effectif_'.$e->sexe);
                    if ($colonne === 'red') {
                        self::ajouter($t, 'T11', $e->cle, 'red_'.$e->sexe);
                    } elseif (in_array($colonne, ['insuffisance', 'epuisement'], true)) {
                        self::ajouter($t, 'T11', $e->cle, 'exclu_'.$e->sexe);
                    }
                }
            }
            foreach (self::bac($idEcole, (string) $annee->annee, $classes)->where('decision', 'admis') as $r) {
                if (isset($lignes['terminales'][$r->cle])) {
                    self::ajouter($t, 'T11', $r->cle, 'admis_'.$r->sexe);
                    self::ajouter($t, 'T13', $r->cle, 'a'.min(5, 3 + (int) ($redoublementsParEleve[$r->id_eleve] ?? 0)).'_'.$r->sexe);
                }
            }
            foreach (self::tranches($membres->where('actif', true), (int) $annee->id_anneeScolaire) as [$cle, $colonne]) {
                self::ajouter($t, 'T15', $cle, $colonne);
            }
            self::recapitulatifs($t, $lignes);
            $n = 0;
            foreach (self::bac($idEcole, (string) $annee->annee, $classes)->where('decision', 'admis') as $r) {
                if (isset($lignes['terminales'][$r->cle])) {
                    $t['T23']['n'.(++$n)] = array_filter(['prenoms' => $r->prenom_eleve, 'nom' => $r->nom_eleve, 'matricule' => $r->matricule, 'serie' => $r->cle], fn ($v) => filled($v));
                }
            }
        }

        return self::reprendre(['type' => $type, 'infos' => $infos, 't' => $t], $idEcole, $annee, $type);
    }

    /**
     * Tableaux récapitulatifs de fin d'année, tirés des résultats par classe (T6 à T9, T11) : T10 (classes de 11e), T14 (par année
     * d'études) et T17 (passants et redoublants par série).
     */
    private static function recapitulatifs(array &$t, array $lignes): void
    {
        $cibles = ['11L' => ['TLL', 'TAL'], '11S' => ['TSE', 'TSEXP'], '11SES' => ['TSECO', 'TSS']];
        $codes = ['10CG' => 'T6', '11L' => 'T7', '11S' => 'T8', '11SES' => 'T9'];
        $somme = fn (array $l, string $prefixe) => collect($l)->filter(fn ($v, $k) => str_starts_with($k, $prefixe))->sum();
        foreach ($cibles as $cle => [$p1, $p2]) {
            foreach ($t[$codes[$cle]][$cle] ?? [] as $colonne => $nombre) {
                $nouvelle = str_replace(['p'.$p1.'_', 'p'.$p2.'_'], ['p1_', 'p2_'], $colonne);
                $t['T10'][$cle][$nouvelle] = ($t['T10'][$cle][$nouvelle] ?? 0) + $nombre;
            }
        }
        foreach ($codes as $cle => $code) {
            $l = $t[$code][$cle] ?? [];
            if ($l === []) {
                continue;
            }
            $niveau = $cle === '10CG' ? 'n10' : 'n11';
            $passage = collect($l)->filter(fn ($v, $k) => str_starts_with($k, 'p'))->sum();
            foreach (['effectif' => $somme($l, 'effectif_'), 'passage' => $passage, 'redoublement' => $somme($l, 'red_'),
                'exclusion' => $somme($l, 'insuffisance_') + $somme($l, 'epuisement_')] as $colonne => $nombre) {
                $t['T14'][$niveau][$colonne] = ($t['T14'][$niveau][$colonne] ?? 0) + $nombre;
            }
            foreach (['f', 'g'] as $sexe) {
                $t['T17'][$cle]['passants_'.$sexe] = collect($l)->filter(fn ($v, $k) => str_starts_with($k, 'p') && str_ends_with($k, '_'.$sexe))->sum();
                $t['T17'][$cle]['redoublants_'.$sexe] = (int) ($l['red_'.$sexe] ?? 0);
            }
        }
        foreach ($lignes['terminales'] as $cle => $libelle) {
            $l = $t['T11'][$cle] ?? [];
            if ($l === []) {
                continue;
            }
            foreach (['effectif' => $somme($l, 'effectif_'), 'redoublement' => $somme($l, 'red_'), 'exclusion' => $somme($l, 'exclu_')] as $colonne => $nombre) {
                $t['T14']['n12'][$colonne] = ($t['T14']['n12'][$colonne] ?? 0) + $nombre;
            }
            foreach (['f', 'g'] as $sexe) {
                $t['T17'][$cle]['redoublants_'.$sexe] = (int) ($l['red_'.$sexe] ?? 0);
            }
        }
        foreach ($t as $code => $tableau) {
            foreach ($tableau as $ligne => $valeurs) {
                $t[$code][$ligne] = array_filter($valeurs, fn ($v) => $v !== 0 && $v !== null);
            }
            $t[$code] = array_filter($t[$code]);
        }
    }

    /** Colonne du rapport de fin d'année pour une décision de réinscription (le passage dépend de la classe d'arrivée). */
    private static function sortie(?string $decision): ?string
    {
        return match ($decision) {
            'redoublant', 'ajourne' => 'red',
            'exclu_insuffisance' => 'insuffisance',
            'exclu_epuisement' => 'epuisement',
            'abandon' => 'abandon',
            default => null, // « exclu » sans motif : à ranger par l'établissement
        };
    }

    /**
     * Élèves d'une année dans les classes du secondaire, retrouvés par l'historique : inscription de l'année, réinscription
     * pour l'année, ou fiche encore sur l'année. Chacun avec sa classe de l'année et sa décision pour l'année suivante.
     */
    private static function membresDeLAnnee(int $idEcole, object $annee, array $classes): Collection
    {
        $idsAnnee = DB::table('anneescolaire')->where('annee', $annee->annee)->pluck('id_anneeScolaire');
        $suivante = DB::table('anneescolaire')->where('date_debut', '>', $annee->date_debut)->orderBy('date_debut')->value('annee');
        $idsSuivante = $suivante ? DB::table('anneescolaire')->where('annee', $suivante)->pluck('id_anneeScolaire') : collect();
        $idsClasses = array_keys($classes);

        $classeDe = [];
        foreach (DB::table('eleve')->where('id_ecole', $idEcole)->whereIn('id_annee', $idsAnnee)->whereIn('etat_dossier', [0, 2])->whereIn('id_classe', $idsClasses)->get(['id_eleve', 'id_classe']) as $e) {
            $classeDe[$e->id_eleve] = $e->id_classe;
        }
        foreach (DB::table('ligne_inscription')->whereIn('id_annee', $idsAnnee)->whereIn('id_classe', $idsClasses)->get(['id_eleve', 'id_classe']) as $l) {
            $classeDe[$l->id_eleve] = $l->id_classe;
        }
        foreach (DB::table('ligne_reinscription as lr')->join('reinscription as r', 'r.id_reinscription', '=', 'lr.id_reinscription')->whereIn('lr.id_annee', $idsAnnee)
            ->whereIn('lr.id_classe', $idsClasses)->whereIn('r.statut', ['passant', 'passage_force', 'redoublant'])->get(['lr.id_eleve', 'lr.id_classe']) as $l) {
            $classeDe[$l->id_eleve] = $l->id_classe;
        }
        // Redoublants, exclus, abandons : la décision garde la classe de départ, ce qui suffit à les retrouver.
        foreach (DB::table('ligne_reinscription as lr')->join('reinscription as r', 'r.id_reinscription', '=', 'lr.id_reinscription')->whereIn('lr.id_annee', $idsSuivante)
            ->whereIn('lr.id_classe', $idsClasses)->whereNotIn('r.statut', ['passant', 'passage_force'])->get(['lr.id_eleve', 'lr.id_classe']) as $l) {
            $classeDe[$l->id_eleve] ??= $l->id_classe;
        }
        if ($classeDe === []) {
            return collect();
        }

        $eleves = DB::table('eleve')->where('id_ecole', $idEcole)->whereIn('id_eleve', array_keys($classeDe))->get(['id_eleve', 'genre_eleve', 'etat_dossier', 'id_annee'])->keyBy('id_eleve');
        $decisions = DB::table('ligne_reinscription as lr')->join('reinscription as r', 'r.id_reinscription', '=', 'lr.id_reinscription')
            ->whereIn('lr.id_annee', $idsSuivante)->whereIn('lr.id_eleve', array_keys($classeDe))->get(['lr.id_eleve', 'lr.id_classe', 'r.statut'])->keyBy('id_eleve');
        $redoublements = DB::table('ligne_reinscription as lr')->join('reinscription as r', 'r.id_reinscription', '=', 'lr.id_reinscription')
            ->join('classe as c', 'c.id_classe', '=', 'lr.id_classe')->join('anneescolaire as a', 'a.id_anneeScolaire', '=', 'lr.id_annee')
            ->where('r.statut', 'redoublant')->whereIn('c.ordreEnseignement', RapportSuccinctDonnees::SECONDAIRE)->whereIn('lr.id_eleve', array_keys($classeDe))
            ->where('a.date_debut', '<=', $annee->date_debut)->get(['lr.id_eleve'])->countBy('id_eleve');

        return collect($classeDe)->map(function ($idClasse, $idEleve) use ($eleves, $decisions, $redoublements, $classes, $idsAnnee) {
            $eleve = $eleves[$idEleve] ?? null;
            if (!$eleve) {
                return null;
            }
            $decision = $decisions[$idEleve] ?? null;

            return (object) [
                'id_eleve' => (int) $idEleve, 'id_classe' => (int) $idClasse, 'cle' => $classes[$idClasse],
                'sexe' => PropositionPassageDonnees::fille($eleve->genre_eleve) ? 'f' : 'g',
                // Retiré pendant l'année : fiche restée sur l'année, marquée retirée, sans décision de réinscription.
                'actif' => !($decision === null && (int) $eleve->etat_dossier === 2 && $idsAnnee->contains($eleve->id_annee)),
                'redoublements' => (int) ($redoublements[$idEleve] ?? 0),
                'decision' => $decision?->statut,
                'id_classe_cible' => $decision ? (int) $decision->id_classe : null,
            ];
        })->filter()->values();
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

    /**
     * Annexes nominatives, calculées depuis KalanNet. Rentrée : élèves par classe, avec leur commune (adresse) et leur
     * sexe. Fin d'année : propositions de passage, de redoublement et d'exclusion par classe (décisions de réinscription).
     *
     * @return list<array{titre: string, entetes: list<string>, lignes: list<list<mixed>>}>
     */
    public static function annexes(string $type, int $idEcole, object $annee): array
    {
        $lignes = self::lignes($idEcole);
        $noms = DB::table('classe')->whereIn('id_classe', array_keys($lignes['classes']))->pluck('nom_classe', 'id_classe');
        $annexes = [];

        if ($type === 'rentree') {
            $eleves = DB::table('eleve')->where('id_ecole', $idEcole)->where('id_annee', $annee->id_anneeScolaire)->where('etat_dossier', 0)
                ->whereIn('id_classe', array_keys($lignes['classes']))->orderBy('adresse_eleve')->orderBy('nom_eleve')->orderBy('prenom_eleve')
                ->get(['id_classe', 'prenom_eleve', 'nom_eleve', 'genre_eleve', 'date_naissance', 'adresse_eleve', 'matricule'])->groupBy('id_classe');
            foreach ($noms->sort() as $idClasse => $nom) {
                $n = 0;
                $annexes[] = ['titre' => 'Liste nominative des élèves : '.$nom, 'entetes' => ['N°', 'Commune / adresse', 'Prénom', 'Nom', 'Sexe', 'Date de naissance', 'N° Mle'],
                    'lignes' => collect($eleves->get($idClasse, []))->map(fn ($e) => [++$n, $e->adresse_eleve, $e->prenom_eleve, $e->nom_eleve,
                        PropositionPassageDonnees::fille($e->genre_eleve) ? 'F' : 'G', DateNaissance::pourSaisie($e->date_naissance), $e->matricule])->all()];
            }

            return $annexes;
        }

        $libelles = ['passant' => 'Passe', 'passage_force' => 'Passe', 'redoublant' => 'Redouble', 'ajourne' => 'Redouble', 'exclu_insuffisance' => 'Exclu : insuffisance de travail',
            'exclu_epuisement' => 'Exclu : épuisement de scolarité', 'exclu' => 'Exclu', 'abandon' => 'Abandon', 'admis_sortant' => 'Admis au Bac', 'diplome_sortant' => 'Diplômé'];
        $membres = self::membresDeLAnnee($idEcole, $annee, $lignes['classes']);
        $identites = DB::table('eleve')->whereIn('id_eleve', $membres->pluck('id_eleve'))->get(['id_eleve', 'prenom_eleve', 'nom_eleve', 'matricule'])->keyBy('id_eleve');
        foreach ($noms->sort() as $idClasse => $nom) {
            $n = 0;
            $annexes[] = ['titre' => 'Propositions de passage, de redoublement et d\'exclusion : '.$nom, 'entetes' => ['N°', 'Prénom', 'Nom', 'Sexe', 'N° Mle', 'Décision', 'Classe d\'arrivée'],
                'lignes' => $membres->where('id_classe', $idClasse)->sortBy(fn ($m) => [$identites[$m->id_eleve]->nom_eleve ?? '', $identites[$m->id_eleve]->prenom_eleve ?? ''])
                    ->map(fn ($m) => [++$n, $identites[$m->id_eleve]->prenom_eleve ?? '', $identites[$m->id_eleve]->nom_eleve ?? '', $m->sexe === 'f' ? 'F' : 'G', $identites[$m->id_eleve]->matricule ?? '',
                        $m->decision ? ($libelles[$m->decision] ?? $m->decision) : ($m->actif ? 'Réinscription non encore faite' : 'Abandon'),
                        in_array($m->decision, ['passant', 'passage_force'], true) ? ($noms[$m->id_classe_cible] ?? DB::table('classe')->where('id_classe', $m->id_classe_cible)->value('nom_classe')) : null])->values()->all()];
        }

        return $annexes;
    }

    /** Ce qui manque avant de remettre le rapport à l'académie. @return list<string> */
    public static function champsManquants(array $donnees): array
    {
        return collect(['region' => 'Région', 'cercle' => 'Cercle', 'commune' => 'Commune'])->filter(fn ($l, $c) => blank($donnees['infos'][$c] ?? null))->values()->all();
    }
}
