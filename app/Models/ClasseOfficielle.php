<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClasseOfficielle extends Model
{
    protected $table = 'classes_officielles';
    protected $primaryKey = 'id_classe_officielle';
    public $timestamps = false;

    protected $fillable = [
        'nom_classe_officielle',
        'ordre_enseignement',
    ];

    public function classes()
    {
        return $this->hasMany(Classe::class, 'id_classe_officielle', 'id_classe_officielle');
    }

}
