<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToSchool;

class Trimestre extends Model
{
    use BelongsToSchool;
    protected $table = 'trimestre';
    protected $primaryKey = 'id_trimestre';
    public $timestamps = false;

    protected $fillable = [
        'nom_trimestre',
        'id_ecole',
    ];
}
