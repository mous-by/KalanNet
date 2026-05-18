<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Traits\BelongsToSchool;

class Note extends Model
{
    use BelongsToSchool;

    protected $table = 'note';
    protected $primaryKey = 'id_note';
    public $timestamps = false;

    protected $fillable = [
        'typeNote',
        'codeNote',
        'valeur',
        'id_ecole',
    ];

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'id_ecole', 'idEcole');
    }
}
