<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 8px; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111; margin: 0; }
        .center { text-align: center; }
        .right { text-align: right; }
        .line { border-top: 1px dashed #111; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .title { font-weight: bold; font-size: 12px; text-transform: uppercase; }
        .amount { font-weight: bold; font-size: 12px; }
        .qr { width: 92px; height: 92px; margin-top: 5px; }
    </style>
</head>
<body>
    <div class="center">
        <div class="title">{{ $ecole?->nomEcole ?? __('finances.ecole_fallback') }}</div>
        <div>{{ $ecole?->adresseEcole ?? $ecole?->adresse ?? '' }}</div>
        <div>{{ $ecole?->telephoneEcole ?? $ecole?->telephone ?? '' }}</div>
        <div class="line"></div>
        <div><strong>{{ __('finances.recu_paiement_title') }}</strong></div>
        <div>{{ __('finances.num_hash', ['num' => $paiement->numero_recu]) }}</div>
        <div>{{ __('finances.ref_prefix', ['reference' => $paiement->reference]) }}</div>
    </div>

    <div class="line"></div>
    <table>
        <tr><td>{{ __('finances.th_date') }}</td><td class="right">{{ optional($paiement->date_paiement)->format('d/m/Y H:i') }}</td></tr>
        <tr><td>{{ __('finances.th_eleve') }}</td><td class="right">{{ $paiement->eleve?->nom_eleve }} {{ $paiement->eleve?->prenom_eleve }}</td></tr>
        <tr><td>{{ __('finances.label_classe') }}</td><td class="right">{{ $paiement->classe?->nom_classe }}</td></tr>
        <tr><td>{{ __('finances.th_motif') }}</td><td class="right">{{ $paiement->motif }}</td></tr>
        <tr><td>{{ __('finances.th_payeur') }}</td><td class="right">{{ $paiement->nom_payeur }}</td></tr>
        <tr><td>{{ __('finances.th_telephone') }}</td><td class="right">{{ $paiement->telephone }}</td></tr>
    </table>
    <div class="line"></div>
    <table>
        <tr>
            <td>{{ __('finances.total_paye') }}</td>
            <td class="right amount">{{ \App\Support\Devise::format((float) ($paiement->montant_paye ?? $paiement->montant), $paiement->ecole ?? null) }}</td>
        </tr>
    </table>
    <div class="line"></div>
    <div class="center">
        <img src="{{ $qrCode }}" class="qr" alt="QR Code">
        <div>{{ __('finances.scan_verify') }}</div>
    </div>
    <div class="line"></div>
    <div class="center">
        {{ __('finances.thank_you_payment') }}<br>
        KalanNet - {{ now()->format('d/m/Y H:i') }}
    </div>
</body>
</html>
