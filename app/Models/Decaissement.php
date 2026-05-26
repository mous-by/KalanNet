<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Decaissement extends Model
{
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('school', function (\Illuminate\Database\Eloquent\Builder $builder) {
            if (\Illuminate\Support\Facades\Auth::check()) {
                $user = \Illuminate\Support\Facades\Auth::user();
                if ($user->droit === 'SupAdmin') {
                    return;
                }
                $builder->whereHas('caisse');
            }
        });
    }
    protected $table = 'decaissement';
    protected $primaryKey = 'id_decaissement';
    public $timestamps = false;

    protected $fillable = [
        'montant_decaissement',
        'date_decaissement',
        'motif_decaissement',
        'id_annee_scolaire',
        'id_caisse',
        'idUtilisateur',
        'valide',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'date_decaissement' => 'date',
        'montant_decaissement' => 'decimal:2',
        'valide' => 'boolean',
        'validated_at' => 'datetime',
    ];

    public function caisse()
    {
        return $this->belongsTo(Caisse::class, 'id_caisse', 'id_caisse');
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'idUtilisateur', 'idUtilisateur');
    }

    public function validateur()
    {
        return $this->belongsTo(User::class, 'validated_by', 'idUtilisateur');
    }
}
