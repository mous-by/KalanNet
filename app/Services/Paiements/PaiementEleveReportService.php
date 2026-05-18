<?php

namespace App\Services\Paiements;

use App\Models\AnneeScolaire;
use App\Models\EcheancePaiement;
use App\Models\Paiement;
use App\Models\PlanPaiement;
use Barryvdh\DomPDF\Facade\Pdf;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaiementEleveReportService
{
    public function historyQuery(int $ecoleId, array $filters = [])
    {
        return PlanPaiement::with(['eleve', 'classe', 'anneeScolaire', 'echeances'])
            ->where('ecole_id', $ecoleId)
            ->when($filters['classe_id'] ?? null, fn ($q, $value) => $q->where('classe_id', $value))
            ->when($filters['annee_scolaire_id'] ?? null, fn ($q, $value) => $q->where('annee_scolaire_id', $value))
            ->orderByDesc('created_at');
    }

    public function decorateHistory($plans)
    {
        return $plans->map(function (PlanPaiement $plan) {
            $echeanceIds = $plan->echeances->pluck('id');
            $paid = Paiement::whereIn('echeance_id', $echeanceIds)
                ->where('statut', 'valide')
                ->sum(DB::raw('COALESCE(montant_paye, montant)'));
            $remaining = max(0, (float) $plan->montant_final - (float) $paid);
            $late = $plan->echeances->contains(fn (EcheancePaiement $e) => $e->date_limite->isPast() && $e->statut !== 'paye');
            $status = match (true) {
                $remaining <= 0 => 'paye',
                (float) $paid > 0 => 'partiel',
                $late => 'retard',
                default => 'non_paye',
            };

            $plan->resume_paiement = [
                'montant_attendu' => (float) $plan->montant_final,
                'montant_paye' => (float) $paid,
                'reste' => $remaining,
                'statut' => $status,
            ];

            return $plan;
        });
    }

    public function receiptPdf(Paiement $paiement)
    {
        $paiement->load(['eleve.ecole', 'classe', 'echeance.planPaiement', 'encaissement']);

        return Pdf::loadView('pdf.finances.recu_paiement_scolaire', [
            'paiement' => $paiement,
            'ecole' => $paiement->eleve?->ecole,
        ]);
    }

    public function thermalReceiptPdf(Paiement $paiement)
    {
        $paiement->load(['eleve.ecole', 'classe', 'encaissement']);
        $ecole = $paiement->eleve?->ecole;
        $qrPayload = implode('|', [
            'KALANNET',
            'RECU:' . $paiement->numero_recu,
            'REF:' . $paiement->reference,
            'ELEVE:' . trim(($paiement->eleve?->nom_eleve ?? '') . ' ' . ($paiement->eleve?->prenom_eleve ?? '')),
            'MONTANT:' . (string) ($paiement->montant_paye ?? $paiement->montant),
            'DATE:' . optional($paiement->date_paiement)->format('Y-m-d'),
        ]);

        $qr = (new Builder())->build(
            writer: new PngWriter(),
            data: $qrPayload,
            size: 180,
            margin: 5,
        );

        return Pdf::loadView('pdf.finances.recu_paiement_thermique', [
            'paiement' => $paiement,
            'ecole' => $ecole,
            'qrCode' => 'data:image/png;base64,' . base64_encode($qr->getString()),
        ])->setPaper([0, 0, 226.77, 650], 'portrait');
    }

    public function legacyHistoryQuery(int $ecoleId, array $filters = [])
    {
        return Paiement::with(['eleve', 'classe'])
            ->where('idEcole', $ecoleId)
            ->when($filters['classe_id'] ?? null, fn ($q, $value) => $q->where('id_classe', $value))
            ->when($filters['annee_scolaire_id'] ?? null, fn ($q, $value) => $q->where('id_annee', $value))
            ->when($filters['statut'] ?? null, fn ($q, $value) => $q->where('statut', $value))
            ->orderByDesc('date_paiement')
            ->orderByDesc('id_paiement');
    }

    public function legacyHistoryPdf($paiements)
    {
        return Pdf::loadView('pdf.finances.historique_paiements_legacy', [
            'paiements' => $paiements,
        ])->setPaper('A4', 'landscape');
    }

    public function legacyCsv($paiements): StreamedResponse
    {
        return response()->streamDownload(function () use ($paiements) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Reference', 'Recu', 'Eleve', 'Classe', 'Motif', 'Montant', 'Payeur', 'Telephone', 'Statut']);
            foreach ($paiements as $paiement) {
                fputcsv($out, $this->legacyRow($paiement));
            }
            fclose($out);
        }, 'historique_paiements_eleves.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function legacyXlsx($paiements): Response|StreamedResponse
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            return $this->legacyCsv($paiements);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Date', 'Reference', 'Recu', 'Eleve', 'Classe', 'Motif', 'Montant', 'Payeur', 'Telephone', 'Statut'], null, 'A1');

        $line = 2;
        foreach ($paiements as $paiement) {
            $sheet->fromArray($this->legacyRow($paiement), null, 'A' . $line);
            $line++;
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        }, 'historique_paiements_eleves.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function historyPdf($plans)
    {
        return Pdf::loadView('pdf.finances.historique_paiements_scolaires', [
            'plans' => $plans,
        ])->setPaper('A4', 'landscape');
    }

    public function csv($plans): StreamedResponse
    {
        return response()->streamDownload(function () use ($plans) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Eleve', 'Classe', 'Annee', 'Statut financier', 'Mode', 'Montant attendu', 'Montant paye', 'Reste', 'Statut']);
            foreach ($plans as $plan) {
                fputcsv($out, $this->row($plan));
            }
            fclose($out);
        }, 'historique_paiements_scolaires.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function xlsx($plans): Response|StreamedResponse
    {
        if (!class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            return $this->csv($plans);
        }

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->fromArray(['Eleve', 'Classe', 'Annee', 'Statut financier', 'Mode', 'Montant attendu', 'Montant paye', 'Reste', 'Statut'], null, 'A1');

        $line = 2;
        foreach ($plans as $plan) {
            $sheet->fromArray($this->row($plan), null, 'A' . $line);
            $line++;
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet))->save('php://output');
        }, 'historique_paiements_scolaires.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function row(PlanPaiement $plan): array
    {
        $resume = $plan->resume_paiement ?? ['montant_attendu' => 0, 'montant_paye' => 0, 'reste' => 0, 'statut' => '-'];

        return [
            trim(($plan->eleve?->nom_eleve ?? '') . ' ' . ($plan->eleve?->prenom_eleve ?? '')),
            $plan->classe?->nom_classe,
            $plan->anneeScolaire?->annee,
            $plan->statut_paiement,
            $plan->mode_paiement,
            $resume['montant_attendu'],
            $resume['montant_paye'],
            $resume['reste'],
            $resume['statut'],
        ];
    }

    private function legacyRow(Paiement $paiement): array
    {
        return [
            optional($paiement->date_paiement)->format('d/m/Y'),
            $paiement->reference,
            $paiement->numero_recu,
            trim(($paiement->eleve?->nom_eleve ?? '') . ' ' . ($paiement->eleve?->prenom_eleve ?? '')),
            $paiement->classe?->nom_classe,
            $paiement->motif,
            (float) ($paiement->montant_paye ?? $paiement->montant),
            $paiement->nom_payeur,
            $paiement->telephone,
            $paiement->statut ?? 'valide',
        ];
    }
}
