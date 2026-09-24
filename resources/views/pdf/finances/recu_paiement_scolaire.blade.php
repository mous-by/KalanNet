<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th, td { border: 1px solid #333; padding: 8px; }
        th { background: #f2f2f2; }
        .title { text-align: center; font-size: 18px; font-weight: bold; text-transform: uppercase; }
        .muted { color: #666; }
        .right { text-align: right; }
    </style>
</head>
<body>
    <div style="display:flex; justify-content:space-between;">
        <div>
            @if($ecole?->pays?->entete_document_gauche)
                <strong>{{ $ecole->pays->entete_document_gauche }}</strong><br>
            @endif
            {{ $ecole?->academie }}<br>{{ $ecole?->cap }}
        </div>
        <div class="right">
            @if($ecole?->pays?->entete_document_droite)
                <strong>{!! nl2br(e($ecole->pays->entete_document_droite)) !!}</strong>
            @endif
        </div>
    </div>
    <p class="title">{{ $ecole?->nomEcole ?? __('finances.ecole_fallback') }}</p>
    <p class="title">{{ __('finances.recu_paiement_num', ['num' => $paiement->numero_recu]) }}</p>
    <p class="muted">{{ __('finances.reference_date_line', ['reference' => $paiement->reference, 'date' => optional($paiement->date_paiement)->format('d/m/Y')]) }}</p>

    <table>
        <tr><th>{{ __('finances.th_eleve') }}</th><td>{{ $paiement->eleve?->nom_eleve }} {{ $paiement->eleve?->prenom_eleve }}</td></tr>
        <tr><th>{{ __('finances.label_classe') }}</th><td>{{ $paiement->classe?->nom_classe }}</td></tr>
        <tr><th>{{ __('finances.th_echeance') }}</th><td>{{ $paiement->echeance?->libelle ?? $paiement->motif }}</td></tr>
        <tr><th>{{ __('finances.th_montant_paye') }}</th><td><strong>{{ \App\Support\Devise::format($paiement->montant_paye ?? $paiement->montant, $paiement->ecole ?? null) }}</strong></td></tr>
        <tr><th>{{ __('finances.th_payeur') }}</th><td>{{ $paiement->nom_payeur }} {{ $paiement->telephone ? ' - '.$paiement->telephone : '' }}</td></tr>
        <tr><th>{{ __('finances.th_mode_reglement') }}</th><td>{{ $paiement->mode_reglement }}</td></tr>
    </table>

    <div style="margin-top:40px; text-align:right;">
        {{ __('finances.fait_par', ['nom' => auth()->user()->nomPrenom ?? '']) }}<br><br><br>
        {{ __('finances.signature_label') }}
    </div>
</body>
</html>
