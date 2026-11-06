<?php

namespace App\Support;

use App\Models\Ecole;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Contenu du « Rapport succinct de rentrée » : pré-rempli depuis KalanNet (élèves de l'année, classes, redoublants,
 * enseignants), puis vérifié et complété par l'école. Deux portées : le préscolaire et le fondamental remettent leur fiche
 * au CAP, le secondaire (général, technique et professionnel) à l'académie.
 */
class RapportSuccinctDonnees
{
    public const SECONDAIRE = ['secondairegenerale', 'secondairetechniqueetprofessionnel'];

    public const CYCLES = [1 => '1er cycle', 2 => '2ème cycle', 3 => 'Cycle complet'];

    public const CYCLES_SECONDAIRE = [1 => 'Secondaire général', 2 => 'Technique et professionnel', 3 => 'Général et technique'];

    private const SERIES = ['10CG' => '10ème CG', '11L' => '11ème L', '11SES' => '11ème SES', '11S' => '11ème S', 'TLL' => 'TLL', 'TAL' => 'TAL', 'TSS' => 'TSS', 'TSECO' => 'TSECO', 'TSE' => 'TSE', 'TSEXP' => 'TSEXP'];

    public const STATUTS = [1 => 'Public', 2 => 'Privé confessionnel', 3 => 'Privé laïc', 4 => 'Médersa', 5 => 'École communautaire'];

    public const ENSEIGNEMENTS = [1 => 'Classique', 2 => 'École à PC', 3 => 'École à Curriculum', 4 => 'Franco-arabe'];

    public const RECRUTEMENTS = [1 => 'Annuel', 2 => 'Biennal', 3 => 'Autres'];

    private const FILLES = ['féminin', 'feminin', 'f', 'fille', 'femme'];

    /** Contrats comptés comme « permanents » ; les autres (CDD, VCT) comme « contractuels ». L'école peut corriger. */
    private const PERMANENTS = ['FONCTIONNAIRE', 'CDI'];

    /** @return list<string> */
    private static function ordres(string $portee): array
    {
        return $portee === 'secondaire' ? self::SECONDAIRE : Ordres::SOUS_CAP;
    }

    /** @return Collection<int, object> classes de l'école pour cette portée */
    private static function classes(int $idEcole, string $portee = 'fondamental'): Collection
    {
        return DB::table('classe')->where('idEcole', $idEcole)->whereIn('ordreEnseignement', self::ordres($portee))
            ->orderBy('nom_classe')->get(['id_classe', 'nom_classe', 'ordreEnseignement', 'serie']);
    }

    /** Fiche(s) que remet l'établissement. Un complexe a les deux, même si ses classes ne sont pas encore toutes créées. @return list<string> */
    public static function portees(?int $idEcole): array
    {
        $typeEcole = $idEcole ? DB::table('ecole')->where('idEcole', $idEcole)->value('typeEcole') : null;
        if (!$idEcole || ModelesBulletin::estEcoleSante($typeEcole)) {
            return []; // écoles de santé : non concernées
        }
        $type = Str::lower(Str::ascii((string) $typeEcole));
        $portees = [];
        if (str_contains($type, 'complexe') || self::classes($idEcole)->isNotEmpty()) {
            $portees[] = 'fondamental';
        }
        if (str_contains($type, 'complexe') || str_contains($type, 'secondaire') || self::classes($idEcole, 'secondaire')->isNotEmpty()) {
            $portees[] = 'secondaire';
        }

        return $portees;
    }

    public static function ecoleConcernee(?int $idEcole, string $portee = 'fondamental'): bool
    {
        return in_array($portee, self::portees($idEcole), true);
    }

    /** L'académie de l'école a-t-elle un compte DAE actif ? Sinon, le rapport se remet sur papier. */
    public static function academieSurKalanNet(?int $idAcademie): bool
    {
        return $idAcademie && DB::table('utilisateurs')->where('droit', 'DAE')->where('id_academie', $idAcademie)->where('statut', 1)->exists();
    }

    /** Série d'une classe de secondaire général, d'après son nom et sa série ; null si elle n'est pas reconnue. */
    public static function serie(?string $nom, ?string $serie = null): ?string
    {
        $texte = Str::lower(Str::ascii(trim($nom.' '.$serie)));
        foreach (['TSEXP' => 't\s*s?\s*\.?exp', 'TSECO' => 't\s*s?\s*\.?eco', 'TSS' => 'tss', 'TSE' => 'tse', 'TAL' => 'tal', 'TLL' => 'tll'] as $code => $motif) {
            if (preg_match('/\b'.$motif.'/', $texte)) {
                return $code;
            }
        }
        $niveau = ExamenNational::extractClasseLevel($nom);
        if ($niveau === 10) {
            return '10CG';
        }
        if ($niveau === 11) {
            return match (true) {
                (bool) preg_match('/\bses\b|economi/', $texte) => '11SES',
                (bool) preg_match('/lettre|litter|\bl\b/', $texte) => '11L',
                (bool) preg_match('/science|\bs\b/', $texte) => '11S',
                default => null,
            };
        }

        return null;
    }

    /** Le CAP de l'école a-t-il un compte DCAP actif ? Sinon, le rapport se remet sur papier. */
    public static function capSurKalanNet(?int $idCap): bool
    {
        return $idCap && DB::table('utilisateurs')->where('droit', 'DCAP')->where('id_cap', $idCap)->where('statut', 1)->exists();
    }

    /** @return array<string, mixed> */
    public static function calculer(Ecole $ecole, int $idAnnee, string $portee = 'fondamental'): array
    {
        $idEcole = (int) $ecole->idEcole;
        $classes = self::classes($idEcole, $portee);
        if ($portee === 'secondaire') {
            $general = $classes->contains('ordreEnseignement', 'secondairegenerale');
            $technique = $classes->contains('ordreEnseignement', 'secondairetechniqueetprofessionnel');

            return [
                'portee' => 'secondaire',
                'ecole' => (string) $ecole->nomEcole,
                'quartier' => $ecole->adresse,
                'commune' => null,
                'cercle' => null,
                'academie' => DB::table('academie')->where('id_academie', $ecole->id_academie)->value('nom_academie') ?: $ecole->academie,
                'cycle' => $technique ? ($general ? 3 : 2) : 1,
                'statut' => strtolower(trim((string) $ecole->statut)) === 'public' ? 1 : null,
                'groupes' => self::groupesSecondaire($idEcole, $idAnnee, $classes),
                'maitres' => self::maitresSecondaire($idEcole, $classes),
            ];
        }
        $ordres = $classes->pluck('ordreEnseignement')->unique();
        $aJardin = $ordres->contains(Ordres::JARDIN);
        $aPremier = $ordres->contains('fondamentale1');
        $aSecond = $ordres->contains('fondamentale2');

        return [
            'ecole' => (string) $ecole->nomEcole,
            'quartier' => $ecole->adresse,
            'commune' => null,
            'cap' => DB::table('cap')->where('id_cap', $ecole->id_cap)->value('nom_cap') ?: $ecole->cap,
            'cercle' => null,
            'academie' => DB::table('academie')->where('id_academie', $ecole->id_academie)->value('nom_academie') ?: $ecole->academie,
            'cycle' => $aSecond ? ($aPremier ? 3 : 2) : 1,
            'statut' => strtolower(trim((string) $ecole->statut)) === 'public' ? 1 : null,
            'enseignement' => $ecole->franco_arabe ? 4 : 1,
            'recrutement' => null,
            'langue' => null,
            'groupes' => self::groupes($idEcole, $idAnnee, $classes, $aJardin),
            'maitres' => self::maitres($idEcole, $classes, $aJardin),
        ];
    }

    /** Une ligne par année d'études, de la 1ère à la 9ème comme sur le modèle ; le préscolaire d'abord s'il existe. */
    private static function groupes(int $idEcole, int $idAnnee, Collection $classes, bool $aJardin): array
    {
        $niveauDe = fn ($classe) => $classe->ordreEnseignement === Ordres::JARDIN ? 'ps' : ExamenNational::extractClasseLevel($classe->nom_classe);
        $cles = $classes->mapWithKeys(fn ($c) => [$c->id_classe => $niveauDe($c)]);

        $lignes = [];
        if ($aJardin) {
            $lignes['ps'] = ['cle' => 'ps', 'libelle' => 'Préscolaire'];
        }
        foreach (range(1, 9) as $niveau) {
            $lignes[$niveau] = ['cle' => (string) $niveau, 'libelle' => $niveau.($niveau === 1 ? 'ère' : 'ème').' A'];
        }

        return self::compter($idEcole, $idAnnee, $classes, $lignes, $cles);
    }

    /** Une ligne par série du général ; une par classe pour une série non reconnue et pour les filières techniques. */
    private static function groupesSecondaire(int $idEcole, int $idAnnee, Collection $classes): array
    {
        $cles = [];
        $lignes = [];
        $autres = [];
        foreach ($classes as $classe) {
            $serie = $classe->ordreEnseignement === 'secondairegenerale' ? self::serie($classe->nom_classe, $classe->serie) : null;
            if ($serie) {
                $cles[$classe->id_classe] = $serie;
            } else {
                $cles[$classe->id_classe] = 'c'.$classe->id_classe;
                $autres['c'.$classe->id_classe] = ['cle' => 'c'.$classe->id_classe, 'libelle' => (string) $classe->nom_classe];
            }
        }
        foreach (self::SERIES as $serie => $libelle) {
            if (in_array($serie, $cles, true)) {
                $lignes[$serie] = ['cle' => $serie, 'libelle' => $libelle];
            }
        }

        return self::compter($idEcole, $idAnnee, $classes, $lignes + $autres, collect($cles));
    }

    /** @param array<int|string, array{cle: string, libelle: string}> $lignes */
    private static function compter(int $idEcole, int $idAnnee, Collection $classes, array $lignes, Collection $niveaux): array
    {
        foreach ($lignes as &$ligne) {
            $ligne += ['groupes' => 0, 'g' => 0, 'f' => 0, 'rg' => 0, 'rf' => 0];
        }
        unset($ligne);

        $eleves = DB::table('eleve as e')->where('e.id_ecole', $idEcole)->where('e.id_annee', $idAnnee)->where('e.etat_dossier', 0)
            ->whereIn('e.id_classe', $classes->pluck('id_classe'))
            ->selectRaw('e.id_classe, e.genre_eleve, EXISTS (SELECT 1 FROM ligne_reinscription lr JOIN reinscription r ON r.id_reinscription = lr.id_reinscription
                WHERE lr.id_eleve = e.id_eleve AND lr.id_annee = e.id_annee AND r.statut = ?) as redouble', ['redoublant'])
            ->get();

        $avecEleves = [];
        foreach ($eleves as $eleve) {
            $niveau = $niveaux[$eleve->id_classe] ?? null;
            if (!isset($lignes[$niveau])) {
                continue;
            }
            $fille = in_array(mb_strtolower(trim((string) $eleve->genre_eleve)), self::FILLES, true);
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

    /** Enseignants du général et du technique, comptés comme pour le fondamental. */
    private static function maitresSecondaire(int $idEcole, Collection $classes): array
    {
        $ids = [
            'general' => $classes->where('ordreEnseignement', 'secondairegenerale')->pluck('id_classe'),
            'technique' => $classes->where('ordreEnseignement', 'secondairetechniqueetprofessionnel')->pluck('id_classe'),
        ];

        return self::compterMaitres($idEcole, $classes, array_filter($ids, fn ($liste) => $liste->isNotEmpty()));
    }

    /** Maîtres par cycle, d'après leur contrat et les classes où ils enseignent. Les salles sont saisies par l'école. */
    private static function maitres(int $idEcole, Collection $classes, bool $aJardin): array
    {
        $idsParCycle = [
            'prescolaire' => $classes->where('ordreEnseignement', Ordres::JARDIN)->pluck('id_classe'),
            'premier' => $classes->where('ordreEnseignement', 'fondamentale1')->pluck('id_classe'),
            'second' => $classes->where('ordreEnseignement', 'fondamentale2')->pluck('id_classe'),
        ];
        if (!$aJardin) {
            unset($idsParCycle['prescolaire']);
        }

        return self::compterMaitres($idEcole, $classes, $idsParCycle);
    }

    /** @param array<string, Collection> $idsParCycle */
    private static function compterMaitres(int $idEcole, Collection $classes, array $idsParCycle): array
    {
        $actifs = DB::table('enseignants')->where('id_ecole', $idEcole)->where(fn ($q) => $q->where('is_deleted', 0)->orWhereNull('is_deleted'))
            ->pluck('type_contrat_enseignant', 'id_enseignant');

        // École d'un seul cycle, sans autre classe : tous ses enseignants en relèvent, même sans matière attribuée.
        $cyclesRemplis = collect($idsParCycle)->filter(fn ($ids) => $ids->isNotEmpty());
        $seulCycle = $cyclesRemplis->count() === 1 && DB::table('classe')->where('idEcole', $idEcole)->count() === $classes->count();

        $resultat = [];
        foreach ($idsParCycle as $cycle => $ids) {
            $maitres = $seulCycle && $ids->isNotEmpty()
                ? $actifs->keys()
                : DB::table('ligneclasse')->whereIn('id_classe', $ids)->whereNotNull('id_enseignants')->pluck('id_enseignants')
                    ->merge(DB::table('classe_maitres')->whereIn('id_classe', $ids)->pluck('id_enseignant'))->unique();

            $contrats = $maitres->filter(fn ($id) => $actifs->has($id))->map(fn ($id) => strtoupper((string) $actifs[$id]));
            $permanents = $contrats->filter(fn ($contrat) => in_array($contrat, self::PERMANENTS, true))->count();
            $resultat[$cycle] = ['permanents' => $permanents, 'contractuels' => $contrats->count() - $permanents, 'salles' => null];
        }

        return $resultat;
    }

    /** Totaux d'école, recalculés (jamais saisis). @return array<string, int> */
    public static function totaux(array $donnees): array
    {
        $lignes = collect($donnees['groupes'] ?? []);

        return [
            'groupes' => (int) $lignes->sum('groupes'),
            'g' => (int) $lignes->sum('g'),
            'f' => (int) $lignes->sum('f'),
            'eleves' => (int) ($lignes->sum('g') + $lignes->sum('f')),
            'rg' => (int) $lignes->sum('rg'),
            'rf' => (int) $lignes->sum('rf'),
            'redoublants' => (int) ($lignes->sum('rg') + $lignes->sum('rf')),
            'maitres' => (int) collect($donnees['maitres'] ?? [])->sum(fn ($m) => (int) ($m['permanents'] ?? 0) + (int) ($m['contractuels'] ?? 0)),
        ];
    }

    /** Ce qui manque avant de remettre la fiche au CAP. @return list<string> */
    public static function champsManquants(array $donnees): array
    {
        $secondaire = ($donnees['portee'] ?? 'fondamental') === 'secondaire';
        $libelles = $secondaire
            ? ['quartier' => 'Village / quartier', 'commune' => 'Commune', 'cercle' => 'Cercle', 'academie' => 'AE', 'statut' => 'Statut administratif']
            : ['quartier' => 'Village / quartier', 'commune' => 'Commune', 'cap' => 'CAP', 'cercle' => 'Cercle', 'academie' => 'AE',
                'statut' => 'Statut administratif', 'recrutement' => 'Mode de recrutement'];
        $manquants = collect($libelles)->filter(fn ($libelle, $champ) => blank($donnees[$champ] ?? null))->values()->all();

        foreach ($donnees['maitres'] ?? [] as $maitres) {
            if (($maitres['salles'] ?? null) === null || $maitres['salles'] === '') {
                $manquants[] = 'Nombre de salles de classe';
                break;
            }
        }

        if (($donnees['groupes'] ?? []) === []) {
            $manquants[] = 'Classes du secondaire (à créer dans KalanNet)';
        }

        return $manquants;
    }
}
