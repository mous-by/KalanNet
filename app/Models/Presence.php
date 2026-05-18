<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToSchool;

class Presence extends Model
{
    use BelongsToSchool;
    protected $table = 'presences';
    protected $primaryKey = 'id_presence';
    public $timestamps = false;

    protected $fillable = [
        'id_enseignant',
        'id_classe',
        'date_presence',
        'nombre_heure',
        'id_trimestre',
        'id_anneeScolaire',
        'id_ecole',
        'valide',
    ];

    protected $casts = [
        'date_presence' => 'datetime',
        'valide' => 'boolean',
    ];

    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class, 'id_enseignant', 'id_enseignant');
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class, 'id_classe', 'id_classe');
    }

    public function trimestre()
    {
        return $this->belongsTo(Trimestre::class, 'id_trimestre', 'id_trimestre');
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class, 'id_anneeScolaire', 'id_anneeScolaire');
    }

    public function lecons()
    {
        return $this->hasMany(LeconPresence::class, 'id_presence', 'id_presence');
    }
}
