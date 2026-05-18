<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bulletin - {{ $apercu->nom_eleve }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; margin: 0; padding: 10px; }
        .header { width: 100%; margin-bottom: 10px; border-bottom: 1px solid #000; padding-bottom: 5px; }
        .header td { vertical-align: top; }
        .logo { width: 50px; height: 50px; border-radius: 50%; }
        .title { text-align: center; font-weight: bold; font-size: 14px; text-decoration: underline; margin: 10px 0; }
        .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .info-table td { border: 1px solid #000; padding: 5px; }
        .main-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .main-table th, .main-table td { border: 1px solid #000; padding: 4px; text-align: center; }
        .main-table th { background-color: #f2f2f2; }
        .footer-table { width: 100%; margin-top: 20px; font-weight: bold; }
        .text-left { text-align: left; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td width="50%">
                <strong>{{ $ecole->nomEcole }}</strong><br>
                Académie d'Enseignement de {{ $ecole->academie ?? '' }}<br>
                CAP de {{ $ecole->cap ?? '' }}
            </td>
            <td width="50%" class="text-right">
                <strong>RÉPUBLIQUE DU MALI</strong><br>
                Un Peuple - Un But - Une Foi
            </td>
        </tr>
    </table>

    <div class="title">
        @if($ordre == 'fondamentale1')
            COMPOSITION DU MOIS {{ strtoupper($apercu->mois_nom ?? '') }} {{ $apercu->annee }}
        @else
            BULLETIN DU {{ strtoupper($apercu->nom_trimestre ?? '') }} {{ $apercu->annee }}
        @endif
    </div>

    <table class="info-table">
        <tr>
            <td><strong>Nom & Prénom:</strong> {{ $apercu->nom_eleve }} {{ $apercu->prenom_eleve }}</td>
            <td width="15%"><strong>Sexe:</strong> {{ $apercu->genre_eleve }}</td>
            <td width="30%"><strong>Classe:</strong> {{ $apercu->nom_classe }}</td>
        </tr>
        <tr>
            <td><strong>Matricule:</strong> {{ $apercu->matricule }}</td>
            <td colspan="2"><strong>Année Scolaire:</strong> {{ $apercu->annee }}</td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            @if($ordre == 'fondamentale1')
                <tr>
                    <th class="text-left">Matière</th>
                    <th>Note</th>
                    <th>Coefficient</th>
                </tr>
            @else
                <tr>
                    <th class="text-left">Matière</th>
                    <th>M.Class</th>
                    <th>M.Compo</th>
                    <th>M.Gle</th>
                    <th>Coef</th>
                    <th>M.Coef</th>
                    <th>Appréciation</th>
                </tr>
            @endif
        </thead>
        <tbody>
            @foreach($matieres as $m)
                @if($ordre == 'fondamentale1')
                    <tr>
                        <td class="text-left">{{ $m->nom_matiere }}</td>
                        <td>{{ number_format($m->M_Gle, 2) }}</td>
                        <td>1.00</td>
                    </tr>
                @else
                    <tr>
                        <td class="text-left">{{ $m->nom_matiere }}</td>
                        <td>{{ number_format($m->M_Class, 2) }}</td>
                        <td>{{ number_format($m->M_Compo, 2) }}</td>
                        <td>{{ number_format($m->M_Gle, 2) }}</td>
                        <td>{{ number_format($m->coef, 2) }}</td>
                        <td>{{ number_format($m->M_Coef, 2) }}</td>
                        <td>{{ $m->appreciation }}</td>
                    </tr>
                @endif
            @endforeach

            @if($ordre != 'fondamentale1' && $note_conduite !== null)
                <tr>
                    <td class="text-left">Conduite</td>
                    <td></td>
                    <td></td>
                    <td>{{ number_format($note_conduite, 2) }}</td>
                    <td>1.00</td>
                    <td>{{ number_format($note_conduite, 2) }}</td>
                    <td>{{ $appreciation_conduite }}</td>
                </tr>
            @endif
        </tbody>
        <tfoot>
            <tr style="font-weight: bold; background: #f9f9f9;">
                <td class="text-left">TOTAL</td>
                @if($ordre == 'fondamentale1')
                    <td>{{ number_format($matieres->sum('M_Gle'), 2) }}</td>
                    <td>{{ $matieres->count() }}</td>
                @else
                    <td colspan="3"></td>
                    <td>{{ number_format($matieres->sum('coef') + ($note_conduite !== null ? 1 : 0), 2) }}</td>
                    <td>{{ number_format($matieres->sum('M_Coef') + ($note_conduite ?? 0), 2) }}</td>
                    <td></td>
                @endif
            </tr>
        </tfoot>
    </table>

    <table width="100%" style="font-weight: bold; margin-top: 10px;">
        <tr class="text-center">
            <td>MOYENNE: {{ number_format($moyenne_periode, 2) }}</td>
            <td>RANG: {{ $rang }} / {{ $total_eleves }}</td>
            <td>PREMIER: {{ number_format($moyenne_premier, 2) }}</td>
        </tr>
    </table>

    <table class="footer-table">
        <tr>
            <td width="50%">LE DIRECTEUR GÉNÉRAL</td>
            <td width="50%" class="text-right">LE TUTEUR</td>
        </tr>
        <tr>
            <td height="60"></td>
            <td></td>
        </tr>
    </table>

    <div style="text-align: center; font-size: 8px; color: #888; margin-top: 30px;">
        Généré le {{ date('d/m/Y H:i') }} - GESCO Alliance Team
    </div>
</body>
</html>
