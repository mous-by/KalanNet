<?php

namespace App\Services\Paiements;

use App\Models\PaiementSequence;
use Illuminate\Support\Facades\DB;

class ReferencePaiementService
{
    public function nextReference(): string
    {
        do {
            $reference = 'PAIEMENT-' . str_pad((string) $this->nextNumber(null, 'reference'), 3, '0', STR_PAD_LEFT);
        } while (DB::table('paiement')->where('reference', $reference)->exists());

        return $reference;
    }

    /**
     * Les numéros déjà présents en base (paiements importés ou générés sans
     * passer par ce service) sont sautés, sinon l'index unique (école,
     * numéro de reçu) ferait échouer chaque nouveau paiement.
     */
    public function nextReceiptNumber(int $ecoleId): int
    {
        $numero = $this->nextNumber($ecoleId, 'recu');
        $recus = fn () => DB::table('paiement')->where('idEcole', $ecoleId);
        if ($recus()->where('numero_recu', $numero)->exists()) {
            // Compteur en retard : on le place directement après le plus grand numéro.
            $numero = (int) $recus()->max('numero_recu') + 1;
            PaiementSequence::query()->where('type', 'recu')->where('ecole_id', $ecoleId)->update(['dernier_numero' => $numero]);
        }

        return $numero;
    }

    private function nextNumber(?int $ecoleId, string $type): int
    {
        $sequence = PaiementSequence::query()
            ->where('type', $type)
            ->where('ecole_id', $ecoleId)
            ->lockForUpdate()
            ->first();

        if (!$sequence) {
            // Première utilisation : on repart du plus grand numéro de reçu existant.
            $sequence = PaiementSequence::create([
                'ecole_id' => $ecoleId,
                'type' => $type,
                'dernier_numero' => $type === 'recu' && $ecoleId
                    ? (int) DB::table('paiement')->where('idEcole', $ecoleId)->max('numero_recu')
                    : 0,
            ]);
            $sequence->refresh();
        }

        $sequence->dernier_numero = ((int) $sequence->dernier_numero) + 1;
        $sequence->save();

        return (int) $sequence->dernier_numero;
    }
}
