<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use App\Traits\BelongsToSchool;

class AnneeScolaire extends Model
{
    use BelongsToSchool;
    protected $table = 'anneescolaire';
    protected $primaryKey = 'id_anneeScolaire';
    public $timestamps = false;

    protected $fillable = [
        'annee',
        'date_debut',
        'date_fin',
        'id_ecole',
    ];

    /** Mois (1er du mois) à partir duquel l'année suivante existe : avant les résultats de fin d'année et la réinscription. */
    private const MOIS_ANNEE_SUIVANTE = 6;

    protected static function booted(): void
    {
        // Une école qui a créé elle-même une année ne voit pas en double l'année commune du même nom,
        // sauf si elle a déjà des élèves ou des formules dessus (rien de ce qui existe n'est jamais masqué).
        static::addGlobalScope('sans_doublon', function (Builder $builder) {
            $idEcole = session('idEcole');
            if (!$idEcole) {
                return;
            }
            $builder->where(fn ($q) => $q->whereNotNull('anneescolaire.id_ecole')
                ->orWhereNotExists(fn ($propre) => $propre->from('anneescolaire as propre')->select(DB::raw(1))
                    ->whereColumn('propre.annee', 'anneescolaire.annee')->where('propre.id_ecole', $idEcole))
                ->orWhereExists(fn ($eleve) => $eleve->from('eleve')->select(DB::raw(1))
                    ->whereColumn('eleve.id_annee', 'anneescolaire.id_anneeScolaire')->where('eleve.id_ecole', $idEcole))
                ->orWhereExists(fn ($formule) => $formule->from('planification')->select(DB::raw(1))
                    ->join('classe', 'classe.id_classe', '=', 'planification.id_classe')
                    ->whereColumn('planification.id_annee', 'anneescolaire.id_anneeScolaire')->where('classe.idEcole', $idEcole)));
        });
    }

    /**
     * Année en cours d'après les dates (jamais la dernière créée : l'année suivante existe dès juin), l'année de
     * l'école avant l'année commune ; à défaut, la dernière commencée.
     */
    public static function enCours(?int $idEcole = null): ?self
    {
        $requete = fn () => self::when($idEcole, fn ($q) => $q->where(fn ($q) => $q->where('id_ecole', $idEcole)->orWhereNull('id_ecole')));
        $aujourdhui = now()->toDateString();

        return $requete()->whereDate('date_debut', '<=', $aujourdhui)->whereDate('date_fin', '>=', $aujourdhui)
            ->orderByRaw('id_ecole IS NULL')->orderByDesc('id_anneeScolaire')->first()
            ?? $requete()->whereDate('date_debut', '<=', $aujourdhui)->orderByDesc('date_debut')->first();
    }

    /** Crée l'année commune en cours et, à partir de juin, l'année suivante. */
    public static function assurerAnneesCommunes(): void
    {
        $maintenant = now();
        $debut = $maintenant->month >= 9 ? $maintenant->year : $maintenant->year - 1;

        self::creerCommune($debut);
        if ($maintenant->month >= self::MOIS_ANNEE_SUIVANTE && $maintenant->month < 9) {
            self::creerCommune($debut + 1);
        }
    }

    private static function creerCommune(int $debut): void
    {
        self::withoutGlobalScopes()->firstOrCreate(
            ['annee' => $debut.'-'.($debut + 1), 'id_ecole' => null],
            ['date_debut' => $debut.'-09-01', 'date_fin' => ($debut + 1).'-08-31']
        );
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'id_ecole', 'idEcole');
    }
}
