<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Encaissement extends Model
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
    protected $table = 'encaissement';
    protected $primaryKey = 'id_encaissement';
    public $timestamps = false;

    protected $fillable = [
        'paiement_id',
        'type_operation',
        'date_encaissement',
        'motif_encaissement',
        'montant_encaissement',
        'statut',
        'id_annee_scolaire',
        'id_caisse',
        'idUtilisateur',
    ];

    protected $casts = [
        'montant_encaissement' => 'decimal:2',
        'date_encaissement' => 'date',
    ];

    public function caisse()
    {
        return $this->belongsTo(Caisse::class, 'id_caisse', 'id_caisse');
    }

    public function utilisateur()
    {
        return $this->belongsTo(User::class, 'idUtilisateur', 'idUtilisateur');
    }

    public function paiement()
    {
        return $this->belongsTo(Paiement::class, 'paiement_id', 'id_paiement');
    }
}
