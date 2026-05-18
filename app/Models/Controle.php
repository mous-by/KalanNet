<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToSchool;

class Controle extends Model
{
    use BelongsToSchool;

    protected $table = 'controle';
    protected $primaryKey = 'id_controle';
    public $timestamps = false;

    protected $fillable = [
        'type_controle',
        'alertControle',
        'penalite_conduite',
        'id_ecole',
    ];

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'id_ecole', 'idEcole');
    }
}
