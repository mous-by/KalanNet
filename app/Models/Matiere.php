<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToSchool;

class Matiere extends Model
{
    use BelongsToSchool;
    protected $table = 'matiere';
    protected $primaryKey = 'id_matiere';
    public $timestamps = false;

    protected $fillable = [
        'nom_matiere',
        'id_ecole',
        'est_lv2',
        'est_franco_arabe',
    ];

    protected $casts = [
        'est_lv2' => 'boolean',
        'est_franco_arabe' => 'boolean',
    ];

    /**
     * Une école franco-arabe voit les deux catalogues communs (franco-arabe et classique) et ses matières ; les autres
     * écoles ne voient jamais les matières franco-arabes.
     */
    public function scopeVisiblesPourEcole($query, ?Ecole $ecole)
    {
        if (!$ecole) {
            return $query;
        }

        // École franco-arabe : elle enseigne les deux programmes, elle voit les deux catalogues communs (classique et
        // franco-arabe) en plus de ses matières. École classique : le catalogue classique et ses matières.
        if ($ecole->franco_arabe) {
            return $query->where(function ($inner) use ($ecole) {
                $inner->where('id_ecole', $ecole->idEcole)
                    ->orWhereNull('id_ecole')
                    ->orWhere('est_franco_arabe', true);
            });
        }

        return $query->where(function ($inner) use ($ecole) {
            $inner->where('id_ecole', $ecole->idEcole)
                ->orWhere('est_franco_arabe', false);
        });
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'id_ecole', 'idEcole');
    }

    public function ordres()
    {
        return $this->hasMany(MatiereOrdre::class, 'id_matiere', 'id_matiere');
    }

    public function scopeLv2($query)
    {
        return $query->where('est_lv2', true);
    }
}
