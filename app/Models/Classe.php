<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToSchool;

class Classe extends Model
{
    use BelongsToSchool;
    protected $table = 'classe';
    protected $primaryKey = 'id_classe';
    public $timestamps = false;

    /** Classes qui ont des notes et des bulletins (le Jardin n'en a pas). */
    public function scopeAvecNotes($query)
    {
        return $query->where(fn ($q) => $q->whereNull('ordreEnseignement')->orWhere('ordreEnseignement', '!=', \App\Support\Ordres::JARDIN));
    }

    protected $fillable = [
        'nom_classe',
        'ordreEnseignement',
        'idEcole',
        'id_classe_officielle',
        'id_filiere',
        'deux_maitres',
        'id_maitre_arabe',
        'id_maitre_francais',
        'modele_bulletin',
        'note_classe_mode',
        'serie',
    ];

    protected $casts = [
        'deux_maitres' => 'boolean',
    ];

    public function ecole()
    {
        return $this->belongsTo(Ecole::class, 'idEcole', 'idEcole');
    }

    public function filiere()
    {
        return $this->belongsTo(Filiere::class, 'id_filiere', 'id_filiere');
    }

    public function eleves()
    {
        return $this->hasMany(Eleve::class, 'id_classe', 'id_classe');
    }

    public function ligneClasses()
    {
        return $this->hasMany(LigneClasse::class, 'id_classe', 'id_classe');
    }

    public function classeOfficielle()
    {
        return $this->belongsTo(ClasseOfficielle::class, 'id_classe_officielle', 'id_classe_officielle');
    }
}
