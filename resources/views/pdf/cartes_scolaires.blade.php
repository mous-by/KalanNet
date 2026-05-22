<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 16px; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 9.5px; }
        .clearfix { clear: both; }
        .flag { width: 36px; height: 18px; border: 1px solid rgba(0,0,0,.18); vertical-align: middle; overflow: hidden; font-size: 0; }
        .flag span { display: inline-block; width: 12px; height: 18px; vertical-align: top; }
        .green { background: #14b53a; } .gold { background: #fcd116; } .red { background: #ce1126; }
        .motto span:nth-child(1) { color: #15803d; }
        .motto span:nth-child(2) { color: #b45309; }
        .motto span:nth-child(3) { color: #b91c1c; }
        .logo { position: absolute; top: 8px; width: 42px; height: 38px; object-fit: contain; z-index: 2; }
        .logo.school { right: 10px; }
        .flag-corner {
            position: absolute;
            left: 10px;
            top: 10px;
            z-index: 3;
            box-shadow: 0 1px 2px rgba(17,24,39,.22);
        }
        .flag-watermark {
            position: absolute;
            left: -44px;
            top: 88px;
            width: 360px;
            height: 42px;
            transform: rotate(-34deg);
            z-index: -1;
            border: 0;
            overflow: hidden;
        }
        .flag-watermark span { display: block; float: left; width: 120px; height: 42px; }
        .flag-watermark .green { background: #d9f3df; }
        .flag-watermark .gold { background: #fff4b8; }
        .flag-watermark .red { background: #f8d6d9; }
        .tpl-vertical .flag-watermark {
            left: -78px;
            top: 132px;
            width: 340px;
            height: 38px;
        }
        .tpl-vertical .flag-watermark span { width: 113px; height: 38px; }
        .tpl-compact .flag-watermark {
            left: -58px;
            top: 72px;
            width: 300px;
            height: 34px;
        }
        .tpl-compact .flag-watermark span { width: 100px; height: 34px; }
        .diagonal-flag-band {
            position: absolute;
            left: -45px;
            width: 365px;
            height: 14px;
            transform: rotate(-34deg);
            z-index: 0;
        }
        .diagonal-flag-band.green { top: 82px; background: #d9f3df; }
        .diagonal-flag-band.gold { top: 96px; background: #fff4b8; }
        .diagonal-flag-band.red { top: 110px; background: #f8d6d9; }
        .tpl-vertical .diagonal-flag-band { left: -78px; width: 340px; height: 13px; }
        .tpl-vertical .diagonal-flag-band.green { top: 126px; }
        .tpl-vertical .diagonal-flag-band.gold { top: 139px; }
        .tpl-vertical .diagonal-flag-band.red { top: 152px; }
        .tpl-compact .diagonal-flag-band { left: -58px; width: 300px; height: 12px; }
        .tpl-compact .diagonal-flag-band.green { top: 66px; }
        .tpl-compact .diagonal-flag-band.gold { top: 78px; }
        .tpl-compact .diagonal-flag-band.red { top: 90px; }
        .card-content { position: relative; z-index: 10; color: #111827; }
        .photo { background: #f3f4f6; color: #6b7280; text-align: center; overflow: hidden; }
        .photo img { width: 100%; height: 100%; object-fit: cover; }
        .student-layout { width: 100%; border-collapse: collapse; color: #111827; }
        .student-layout td { vertical-align: top; color: #111827; }
        .student-photo-cell { width: 92px; padding-right: 10px; }
        .student-info-cell { line-height: 1.5; color: #111827; }
        .student-info-cell div { color: #111827; }
        .qr img { width: 52px; height: 52px; }
        .qr, .sign { z-index: 2; }
        .signature-img { display: block; max-width: 76px; max-height: 24px; margin: 1px auto 0; }
        .signature-label { display: block; font-size: 7.5px; line-height: 1.1; text-align: center; color: #374151; }
        .label { font-weight: bold; }
        .card {
            float: left;
            overflow: hidden;
            page-break-inside: avoid;
            position: relative;
            border-color: {{ $config['primary_color'] }} !important;
            box-shadow: inset 0 0 0 2px {{ $config['secondary_color'] }};
            color: #111827;
        }
        .school-name { font-weight: bold; text-transform: uppercase; line-height: 1.25; }
        .title { font-weight: bold; text-transform: uppercase; }
        .school-contact { font-size: 7.5px; line-height: 1.2; color: #374151; }
        .name { font-weight: bold; text-transform: uppercase; color: #111827; }

        .tpl-alliance_pro {
            width: 48%;
            height: 220px;
            margin: 0 1% 13px 0;
            border-radius: 15px;
            border: 2px solid {{ $config['primary_color'] }};
            background: #ffffff;
        }
        .tpl-alliance_pro .head { text-align: center; padding: 8px 58px 4px; min-height: 55px; }
        .tpl-alliance_pro .body { padding: 8px 12px 0; }
        .tpl-alliance_pro .photo { width: 78px; height: 88px; line-height: 88px; border-radius: 8px; border: 2px solid {{ $config['secondary_color'] }}; }
        .tpl-alliance_pro .info { line-height: 1.5; color: #111827; }
        .tpl-alliance_pro .name { font-size: 12px; color: #111827; }
        .tpl-alliance_pro .mat { clear: both; margin: 8px 12px 0; }
        .tpl-alliance_pro .qr { position: absolute; right: 12px; bottom: 10px; }
        .tpl-alliance_pro .sign { position: absolute; right: 76px; bottom: 10px; width: 82px; font-size: 9px; text-align: center; }

        .tpl-institutionnel, .tpl-moderne, .tpl-horizon {
            width: 48%;
            height: 220px;
            margin: 0 1% 13px 0;
            border: 2px solid {{ $config['primary_color'] }};
            border-radius: 9px;
            background: #fff;
        }
        .tpl-institutionnel .head, .tpl-moderne .head, .tpl-horizon .head { text-align: center; padding: 8px 55px 5px; min-height: 54px; }
        .tpl-institutionnel .head, .tpl-moderne .head, .tpl-horizon .head { color: #111827; }
        .tpl-institutionnel .body, .tpl-moderne .body, .tpl-horizon .body { padding: 9px 10px; }
        .tpl-institutionnel .photo, .tpl-moderne .photo, .tpl-horizon .photo { width: 68px; height: 80px; line-height: 80px; border: 2px solid {{ $config['secondary_color'] }}; }
        .tpl-moderne .photo { border-radius: 9px; }
        .tpl-institutionnel .line, .tpl-moderne .line, .tpl-horizon .line { margin-bottom: 3px; }
        .tpl-institutionnel .qr, .tpl-moderne .qr, .tpl-horizon .qr { position: absolute; right: 10px; bottom: 10px; }
        .tpl-institutionnel .sign, .tpl-moderne .sign, .tpl-horizon .sign { position: absolute; right: 72px; bottom: 10px; width: 82px; font-size: 9px; text-align: center; }

        .tpl-vertical {
            width: 30.6%;
            height: 315px;
            margin: 0 2% 14px 0;
            border: 2px solid {{ $config['primary_color'] }};
            border-radius: 12px;
            text-align: center;
            background: #ffffff;
        }
        .tpl-vertical .head { color: #111827; padding: 10px 8px 6px; min-height: 56px; }
        .tpl-vertical .photo { width: 88px; height: 100px; line-height: 100px; border-radius: 12px; margin: 10px auto 7px; border: 2px solid {{ $config['secondary_color'] }}; }
        .tpl-vertical .meta { text-align: left; padding: 6px 13px; line-height: 1.45; color: #111827; }
        .tpl-vertical .meta div { color: #111827; }
        .tpl-vertical .qr { position: absolute; left: 12px; bottom: 10px; }
        .tpl-vertical .sign { position: absolute; right: 12px; bottom: 10px; width: 76px; font-size: 8px; text-align: center; }

        .tpl-compact {
            width: 32%;
            height: 182px;
            margin: 0 1% 10px 0;
            border: 2px solid {{ $config['primary_color'] }};
            border-radius: 8px;
            background: #fff;
        }
        .tpl-compact .head { color: #111827; text-align: center; padding: 6px 42px 4px; min-height: 42px; }
        .tpl-compact .school-name { font-size: 7.5px; }
        .tpl-compact .title { font-size: 7px; }
        .tpl-compact .body { padding: 8px; }
        .tpl-compact .student-photo-cell { width: 62px; padding-right: 7px; }
        .tpl-compact .photo { width: 54px; height: 64px; line-height: 64px; border: 2px solid {{ $config['secondary_color'] }}; }
        .tpl-compact .info { line-height: 1.35; color: #111827; }
        .tpl-compact .name { font-size: 9px; color: #111827; }
        .tpl-compact .qr { position: absolute; right: 7px; bottom: 7px; }
        .tpl-compact .qr img { width: 38px; height: 38px; }
        .tpl-compact .sign { display: none; }
    </style>
</head>
<body>
@foreach($eleves as $eleve)
    @php
        $photoPath = $eleve->image ? public_path($eleve->image) : null;
        $hasPhoto = $photoPath && file_exists($photoPath);
        $fullName = trim($eleve->prenom_eleve . ' ' . $eleve->nom_eleve);
        $tpl = 'tpl-' . $config['template'];
        $qrCode = $qrCodes[$eleve->id_eleve] ?? null;
    @endphp

    <div class="card {{ $tpl }}">
        @if($config['show_mali_flag'] && $config['mali_flag_style'] === 'watermark')
            <div class="diagonal-flag-band green"></div>
            <div class="diagonal-flag-band gold"></div>
            <div class="diagonal-flag-band red"></div>
        @endif
        @if($config['show_school_logo'] && $schoolLogoPath)
            <img src="{{ $schoolLogoPath }}" class="logo school" alt="">
        @endif
        @if($config['show_mali_flag'] && $config['mali_flag_style'] === 'corner')
            <div class="flag flag-corner"><span class="green"></span><span class="gold"></span><span class="red"></span></div>
        @endif

        <div class="card-content">
            <div class="head">
                <div>RÉPUBLIQUE DU MALI</div>
                <div class="motto"><span>Un Peuple</span> - <span>Un But</span> - <span>Une Foi</span></div>
                <div class="school-name">{{ $schoolName }}</div>
                @if(!empty($adminPhone))
                    <div class="school-contact">Tél : {{ $adminPhone }}</div>
                @endif
                <div class="title">{{ $config['card_title'] }} ({{ $annee->annee }})</div>
            </div>

            @if($config['template'] === 'vertical')
                <div class="photo">@if($hasPhoto)<img src="{{ $photoPath }}" alt="">@else PHOTO @endif</div>
                <div class="name">{{ $fullName }}</div>
                <div class="meta">
                    <div><span class="label">MAT :</span> {{ $eleve->matricule ?: 'Non renseigné' }}</div>
                    <div><span class="label">Classe :</span> {{ $classe->nom_classe }}</div>
                    <div><span class="label">Sexe :</span> {{ $eleve->genre_eleve }}</div>
                    <div><span class="label">Né(e) :</span> {{ $eleve->date_naissance ? \Carbon\Carbon::parse($eleve->date_naissance)->format('d/m/Y') : 'Non renseigné' }} à {{ $eleve->lieu_naiss ?: 'Non renseigné' }}</div>
                </div>
            @elseif($config['template'] === 'compact')
                <div class="body">
                    <table class="student-layout">
                        <tr>
                            <td class="student-photo-cell">
                                <div class="photo">@if($hasPhoto)<img src="{{ $photoPath }}" alt="">@else PHOTO @endif</div>
                            </td>
                            <td class="student-info-cell info">
                                <div class="name">{{ $fullName }}</div>
                                <div><span class="label">MAT :</span> {{ $eleve->matricule ?: 'Non renseigné' }}</div>
                                <div><span class="label">Sexe :</span> {{ $eleve->genre_eleve }}</div>
                                <div><span class="label">Classe :</span> {{ $classe->nom_classe }}</div>
                            </td>
                        </tr>
                    </table>
                </div>
            @else
                <div class="body">
                    <table class="student-layout">
                        <tr>
                            <td class="student-photo-cell">
                                <div class="photo">@if($hasPhoto)<img src="{{ $photoPath }}" alt="">@else PHOTO @endif</div>
                            </td>
                            <td class="student-info-cell info">
                                <div class="line"><span class="label">Nom :</span> {{ strtoupper($eleve->nom_eleve) }}</div>
                                <div class="line"><span class="label">Prénom :</span> {{ $eleve->prenom_eleve }}</div>
                                <div class="line"><span class="label">Né(e) le :</span> {{ $eleve->date_naissance ? \Carbon\Carbon::parse($eleve->date_naissance)->format('d/m/Y') : 'Non renseigné' }} à {{ $eleve->lieu_naiss ?: 'Non renseigné' }}</div>
                                <div class="line"><span class="label">Sexe :</span> {{ $eleve->genre_eleve }}</div>
                                <div class="line"><span class="label">Classe :</span> {{ $classe->nom_classe }}</div>
                            </td>
                        </tr>
                    </table>
                    <div class="mat"><span class="label">MAT :</span> {{ $eleve->matricule ?: 'Non renseigné' }}</div>
                </div>
            @endif
        </div>

        @if($qrCode)
            <div class="qr"><img src="{{ $qrCode }}" alt="QR"></div>
        @endif
        <div class="sign">
            <span class="signature-label">{{ $config['signature_label'] }}</span>
            @if(!empty($config['signature_image']))
                <img src="{{ $config['signature_image'] }}" class="signature-img" alt="">
            @endif
        </div>
    </div>

    @if($config['template'] === 'vertical' && $loop->iteration % 3 === 0)
        <div class="clearfix"></div>
    @elseif($config['template'] === 'compact' && $loop->iteration % 3 === 0)
        <div class="clearfix"></div>
    @elseif(!in_array($config['template'], ['vertical', 'compact'], true) && $loop->iteration % 2 === 0)
        <div class="clearfix"></div>
    @endif
@endforeach
</body>
</html>
