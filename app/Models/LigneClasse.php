<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LigneClasse extends Model
{
    protected $table = 'ligneclasse';
    protected $primaryKey = 'id_ligneclasse';
    public $timestamps = false;

    protected $fillable = [
        'id_matiere',
        'id_classe',
        'id_enseignants',
        'coefficient',
    ];

    public function classe()
    {
        return $this->belongsTo(Classe::class, 'id_classe', 'id_classe');
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class, 'id_matiere', 'id_matiere');
    }

    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class, 'id_enseignants', 'id_enseignant');
    }
}
