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
        .logo { position: absolute; top: 8px; width: 42px; height: 38px; object-fit: contain; z-index: 20; }
        .logo.school { right: 10px; }
        .flag-corner {
            position: absolute;
            left: 10px;
            top: 10px;
            z-index: 20;
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

        /* Modèles horizontaux : photo, informations, puis QR et signature dans leur propre colonne (rien ne se chevauche). */
        .tpl-institutionnel, .tpl-moderne, .tpl-epure, .tpl-horizon {
            width: 48%;
            height: 232px;
            margin: 0 1% 12px 0;
            border: 2px solid {{ $config['primary_color'] }};
            border-radius: 10px;
            background: #fff;
        }
        .tpl-institutionnel .head, .tpl-moderne .head, .tpl-epure .head, .tpl-horizon .head { text-align: center; padding: 8px 54px 5px; min-height: 52px; color: #111827; }
        .tpl-institutionnel .body, .tpl-moderne .body, .tpl-epure .body, .tpl-horizon .body { padding: 6px 10px 0; }
        .side-cell { width: 60px; text-align: center; vertical-align: bottom !important; }
        .side-cell .qr img { width: 50px; height: 50px; }
        .side-cell .sign { width: 60px; font-size: 8px; margin-top: 2px; }
        .line { margin-bottom: 1px; }

        /* Institutionnel : en-tête plein à la couleur principale, filet de la couleur secondaire. */
        .tpl-institutionnel .head { background: {{ $config['primary_color'] }}; color: #fff; border-radius: 8px 8px 0 0; border-bottom: 3px solid {{ $config['secondary_color'] }}; }
        .tpl-institutionnel .head div, .tpl-institutionnel .head .school-contact { color: #fff; }
        .tpl-institutionnel .photo { width: 68px; height: 82px; line-height: 82px; border: 2px solid {{ $config['primary_color'] }}; }

        /* Moderne : bande verticale à gauche, en-tête aligné à gauche, nom en grand. */
        .tpl-moderne { border-width: 1px; border-radius: 14px; }
        .tpl-moderne .bande { position: absolute; left: 0; top: 0; bottom: 0; width: 12px; background: {{ $config['primary_color'] }}; }
        .tpl-moderne .bande-fine { position: absolute; left: 12px; top: 0; bottom: 0; width: 4px; background: {{ $config['secondary_color'] }}; }
        .tpl-moderne .head { text-align: left; padding: 8px 54px 4px 68px; }
        .tpl-moderne .body { padding-left: 26px; }
        .tpl-moderne .title { color: {{ $config['primary_color'] }}; }
        .tpl-moderne .photo { width: 66px; height: 80px; line-height: 80px; border-radius: 12px; border: 2px solid {{ $config['secondary_color'] }}; }
        .tpl-moderne .grand-nom { font-size: 12px; font-weight: bold; text-transform: uppercase; color: {{ $config['primary_color'] }}; margin-bottom: 2px; }
        .tpl-moderne .flag-corner { left: 22px; }

        /* Épuré : sobre, beaucoup de blanc, coins très arrondis. */
        .tpl-epure { border-radius: 16px; border-width: 1.5px; }
        .tpl-epure .photo { width: 70px; height: 84px; line-height: 84px; border-radius: 8px; border: 2px solid {{ $config['secondary_color'] }}; }
        .tpl-epure .title { letter-spacing: 1px; }

        /* Horizon : photo à droite, bandeau du bas avec le matricule et la classe. */
        .tpl-horizon .head { border-top: 6px solid {{ $config['secondary_color'] }}; border-radius: 8px 8px 0 0; }
        .tpl-horizon .photo { width: 68px; height: 80px; line-height: 80px; border: 2px solid {{ $config['primary_color'] }}; border-radius: 4px; }
        .tpl-horizon .student-photo-cell { padding-right: 0; padding-left: 8px; }
        .tpl-horizon .bandeau { position: absolute; left: 0; bottom: 0; width: 100%; height: 24px; background: {{ $config['primary_color'] }}; border-radius: 0 0 8px 8px; z-index: 15; }
        .tpl-horizon .bandeau table { width: 100%; border-collapse: collapse; }
        .tpl-horizon .bandeau td { color: #fff; font-weight: bold; font-size: 9px; padding: 6px 12px 0; }
        .tpl-horizon .bandeau td.droite { text-align: right; }

        .tpl-vertical {
            width: 30.6%;
            height: 324px;
            margin: 0 2% 12px 0;
            border: 2px solid {{ $config['primary_color'] }};
            border-radius: 12px;
            text-align: center;
            background: #ffffff;
        }
        .tpl-vertical .head { color: #111827; padding: 10px 8px 6px; min-height: 56px; }
        .tpl-vertical .photo { width: 76px; height: 86px; line-height: 86px; border-radius: 12px; margin: 5px auto 4px; border: 2px solid {{ $config['secondary_color'] }}; }
        .tpl-vertical .meta { text-align: left; padding: 4px 13px; line-height: 1.3; font-size: 8.5px; color: #111827; }
        .tpl-vertical .meta div { color: #111827; }
        .tpl-vertical .pied { width: 100%; margin-top: 2px; border-collapse: collapse; }
        .tpl-vertical .pied td { padding: 0 12px; vertical-align: bottom; width: 50%; }
        .tpl-vertical .pied .qr img { width: 42px; height: 42px; }
        .tpl-vertical .pied .sign { font-size: 8px; text-align: right; }
        .tpl-vertical .pied .signature-label { text-align: right; }
        .tpl-vertical .pied .signature-img { margin: 1px 0 0 auto; }

        .tpl-compact {
            width: 32%;
            height: 182px;
            margin: 0 1% 10px 0;
            border: 2px solid {{ $config['primary_color'] }};
            border-radius: 8px;
            background: #fff;
        }
        .tpl-compact .head { color: #111827; text-align: center; padding: 6px 46px 4px; min-height: 42px; font-size: 7.5px; }
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
        @if($config['template'] === 'moderne')
            <div class="bande"></div><div class="bande-fine"></div>
        @endif
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
                @if($ecole?->pays?->entete_document_droite)
                    <div>{!! nl2br(e($ecole->pays->entete_document_droite)) !!}</div>
                @endif
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
                    <div><span class="label">Né(e) :</span> {{ $eleve->date_naissance ? \Carbon\Carbon::parse($eleve->date_naissance)->format('d/m/Y') : 'Non renseigné' }}{{ $eleve->lieu_naiss ? ' à '.$eleve->lieu_naiss : '' }}</div>
                </div>
                <table class="pied">
                    <tr>
                        <td style="text-align:left">@if($qrCode)<div class="qr"><img src="{{ $qrCode }}" alt="QR"></div>@endif</td>
                        <td style="text-align:right">
                            <div class="sign">
                                <span class="signature-label">{{ $config['signature_label'] }}</span>
                                @if(!empty($config['signature_image']))<img src="{{ $config['signature_image'] }}" class="signature-img" alt="">@endif
                            </div>
                        </td>
                    </tr>
                </table>
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
                @php
                    $photoCell = '<td class="student-photo-cell"><div class="photo">'.($hasPhoto ? '<img src="'.e($photoPath).'" alt="">' : 'PHOTO').'</div></td>';
                    $naissance = ($eleve->date_naissance ? \Carbon\Carbon::parse($eleve->date_naissance)->format('d/m/Y') : 'Non renseigné').($eleve->lieu_naiss ? ' à '.$eleve->lieu_naiss : '');
                @endphp
                <div class="body">
                    <table class="student-layout">
                        <tr>
                            @if($config['template'] !== 'horizon'){!! $photoCell !!}@endif
                            <td class="student-info-cell info">
                                @if($config['template'] === 'moderne')
                                    <div class="grand-nom">{{ $fullName }}</div>
                                @else
                                    <div class="line"><span class="label">Prénom :</span> {{ $eleve->prenom_eleve }}</div>
                                    <div class="line"><span class="label">Nom :</span> {{ strtoupper($eleve->nom_eleve) }}</div>
                                @endif
                                <div class="line"><span class="label">Né(e) le :</span> {{ $naissance }}</div>
                                <div class="line"><span class="label">Sexe :</span> {{ $eleve->genre_eleve }}</div>
                                @if($config['template'] !== 'horizon')
                                    <div class="line"><span class="label">Classe :</span> {{ $classe->nom_classe }}</div>
                                    <div class="line"><span class="label">MAT :</span> {{ $eleve->matricule ?: 'Non renseigné' }}</div>
                                @endif
                            </td>
                            <td class="side-cell">
                                @if($qrCode)<div class="qr"><img src="{{ $qrCode }}" alt="QR"></div>@endif
                                <div class="sign">
                                    <span class="signature-label">{{ $config['signature_label'] }}</span>
                                    @if(!empty($config['signature_image']))<img src="{{ $config['signature_image'] }}" class="signature-img" alt="">@endif
                                </div>
                            </td>
                            @if($config['template'] === 'horizon'){!! $photoCell !!}@endif
                        </tr>
                    </table>
                </div>
                @if($config['template'] === 'horizon')
                    <div class="bandeau"><table><tr><td>MAT : {{ $eleve->matricule ?: 'Non renseigné' }}</td><td class="droite">{{ $classe->nom_classe }}</td></tr></table></div>
                @endif
            @endif
        </div>

        @if($config['template'] === 'compact')
            @if($qrCode)
                <div class="qr"><img src="{{ $qrCode }}" alt="QR"></div>
            @endif
            <div class="sign">
                <span class="signature-label">{{ $config['signature_label'] }}</span>
                @if(!empty($config['signature_image']))
                    <img src="{{ $config['signature_image'] }}" class="signature-img" alt="">
                @endif
            </div>
        @endif
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
