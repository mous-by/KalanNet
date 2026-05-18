<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToSchool;

class ParentModel extends Model
{
    use BelongsToSchool;
    protected $table = 'parents';
    protected $primaryKey = 'id_parent';
    public $timestamps = false;

    protected $fillable = [
        'nom_prenom_parent',
        'email_parent',
        'telephone_parent',
        'genre',
        'idEcole',
        'pwd'
    ];

    public function eleves()
    {
        return $this->belongsToMany(Eleve::class, 'ligneparents_eleves', 'id_parent', 'id_eleve')
            ->withPivot(['informer', 'lien_parent']);
    }

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'idEcole', 'idEcole');
    }
}
