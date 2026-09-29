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
     * Une école franco-arabe ne voit que les matières franco-arabes (plus les
     * siennes) ; les autres écoles ne les voient jamais.
     */
    public function scopeVisiblesPourEcole($query, ?Ecole $ecole)
    {
        if (!$ecole) {
            return $query;
        }

        return $query->where(function ($inner) use ($ecole) {
            $inner->where('id_ecole', $ecole->idEcole)
                ->orWhere('est_franco_arabe', (bool) $ecole->franco_arabe);
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
