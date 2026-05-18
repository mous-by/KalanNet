<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Planification extends Model
{
    protected $table = 'planification';
    protected $primaryKey = 'id_planification';
    public $timestamps = false;

    protected $fillable = [
        'motif',
        'id_classe',
        'id_annee',
        'date_debut',
        'date_fin',
        'montant_planification',
    ];

    public function classe()
    {
        return $this->belongsTo(Classe::class, 'id_classe', 'id_classe');
    }

    public function anneeScolaire()
    {
        return $this->belongsTo(AnneeScolaire::class, 'id_annee', 'id_anneeScolaire');
    }
}
