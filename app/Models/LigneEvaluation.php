<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LigneEvaluation extends Model
{
    protected $table = 'ligne_evaluation';
    protected $primaryKey = 'id_ligneEvaluation';
    public $timestamps = false;

    protected $fillable = [
        'id_evaluation',
        'id_classe',
        'id_matiere',
        'id_annee_scolaire',
        'id_trimestre',
        'id_note',
        'id_eleve',
        'note',
        'validation_status',
        'validated_by',
        'validated_at',
        'id_enseignant',
        'mois'
    ];

    protected $casts = [
        'validated_at' => 'datetime',
    ];

    public function evaluation()
    {
        return $this->belongsTo(Evaluation::class, 'id_evaluation', 'id_evaluation');
    }

    public function eleve()
    {
        return $this->belongsTo(Eleve::class, 'id_eleve', 'id_eleve');
    }

    public function classe()
    {
        return $this->belongsTo(Classe::class, 'id_classe', 'id_classe');
    }

    public function matiere()
    {
        return $this->belongsTo(Matiere::class, 'id_matiere', 'id_matiere');
    }

    public function enseignant()
    {
        return $this->belongsTo(Enseignant::class, 'id_enseignant', 'id_enseignant');
    }

    public function noteType()
    {
        return $this->belongsTo(Note::class, 'id_note', 'id_note');
    }

    public function trimestre()
    {
        return $this->belongsTo(Trimestre::class, 'id_trimestre', 'id_trimestre');
    }
}
