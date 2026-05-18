<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgrammeClasse extends Model
{
    protected $table = 'programme_classes';
    protected $primaryKey = 'id_programme_classe';
    public $timestamps = false;

    protected $fillable = [
        'id_programme',
        'id_classe',
        'id_matiere',
        'pour_toutes_ecoles',
    ];

    public function programme()
    {
        return $this->belongsTo(ProgrammeOfficiel::class, 'id_programme', 'id_programme');
    }

    public function classeOfficielle()
    {
        return $this->belongsTo(ClasseOfficielle::class, 'id_classe', 'id_classe_officielle');
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class, 'id_matiere', 'id_matiere');
    }

    public function lecons()
    {
        return $this->hasMany(ProgrammeLecon::class, 'id_programme_classe', 'id_programme_classe');
    }
}
