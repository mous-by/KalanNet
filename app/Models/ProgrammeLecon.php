<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgrammeLecon extends Model
{
    protected $table = 'programme_lecons';
    protected $primaryKey = 'id_lecon';
    public $timestamps = false;

    protected $fillable = [
        'id_programme_classe',
        'numero',
        'titre',
    ];

    public function programmeClasse()
    {
        return $this->belongsTo(ProgrammeClasse::class, 'id_programme_classe', 'id_programme_classe');
    }
}
