<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgrammeOfficiel extends Model
{
    protected $table = 'programmes_officiels';
    protected $primaryKey = 'id_programme';
    public $timestamps = false;

    protected $fillable = [
        'date_creation',
        'id_utilisateur',
        'officiel',
    ];

    public function classes()
    {
        return $this->hasMany(ProgrammeClasse::class, 'id_programme', 'id_programme');
    }
}
