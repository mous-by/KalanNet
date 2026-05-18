<?php

namespace App\Services\Paiements;

use App\Models\PaiementSequence;

class ReferencePaiementService
{
    public function nextReference(): string
    {
        $number = $this->nextNumber(null, 'reference');

        return 'PAIEMENT-' . str_pad((string) $number, 3, '0', STR_PAD_LEFT);
    }

    public function nextReceiptNumber(int $ecoleId): int
    {
        return $this->nextNumber($ecoleId, 'recu');
    }

    private function nextNumber(?int $ecoleId, string $type): int
    {
        $sequence = PaiementSequence::query()
            ->where('type', $type)
            ->where('ecole_id', $ecoleId)
            ->lockForUpdate()
            ->first();

        if (!$sequence) {
            $sequence = PaiementSequence::create([
                'ecole_id' => $ecoleId,
                'type' => $type,
                'dernier_numero' => 0,
            ]);
            $sequence->refresh();
        }

        $sequence->dernier_numero = ((int) $sequence->dernier_numero) + 1;
        $sequence->save();

        return (int) $sequence->dernier_numero;
    }
}
