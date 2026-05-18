<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluation extends Model
{
    protected $table = 'evaluation';
    protected $primaryKey = 'id_evaluation';
    public $timestamps = false;

    protected $fillable = [
        'libeller',
        'date_evaluation',
        'heure_debut',
        'heure_fin',
        'updated_at'
    ];

    public function lignes()
    {
        return $this->hasMany(LigneEvaluation::class, 'id_evaluation', 'id_evaluation');
    }
}
