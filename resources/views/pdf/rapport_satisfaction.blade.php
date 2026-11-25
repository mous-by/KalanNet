<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport de satisfaction KalanNet</title>
    <style>
        @page { margin: 32px 36px; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 11px; line-height: 1.4; }
        h1 { font-size: 20px; margin: 0; color: #831843; }
        .sous-titre { color: #6b7280; margin: 4px 0 18px; }
        .tuiles { width: 100%; border-collapse: separate; border-spacing: 8px 0; margin: 0 -8px 18px; }
        .tuiles td { border: 1px solid #e5e7eb; border-top: 3px solid #831843; padding: 10px; width: 25%; vertical-align: top; }
        .tuile-label { font-size: 8.5px; text-transform: uppercase; color: #6b7280; font-weight: bold; }
        .tuile-valeur { font-size: 22px; font-weight: bold; }
        h2 { font-size: 13px; margin: 16px 0 8px; }
        .barres td { padding: 3px 4px; }
        .piste { background: #eef0f3; height: 10px; width: 260px; }
        .barre { background: #831843; height: 10px; }
        .citation { border-left: 3px solid #831843; padding: 6px 10px; margin-bottom: 8px; background: #fdf2f8; }
        .auteur { color: #6b7280; font-size: 9.5px; margin-top: 3px; }
        .pied { margin-top: 24px; color: #6b7280; font-size: 9px; border-top: 1px solid #e5e7eb; padding-top: 6px; }
    </style>
</head>
<body>
    @php
        $libelles = \App\Support\Satisfaction::LIBELLES_NOTE;
        $max = max(1, max($rapport['repartition']));
        $periode = $rapport['depuis'] ? 'du '.\Carbon\Carbon::parse($rapport['depuis'])->format('d/m/Y') : 'depuis le début';
        $periode .= $rapport['jusqua'] ? ' au '.\Carbon\Carbon::parse($rapport['jusqua'])->format('d/m/Y') : ' à aujourd\'hui';
    @endphp

    <h1>Satisfaction des utilisateurs de KalanNet</h1>
    <div class="sous-titre">Avis recueillis dans l'application auprès des écoles, {{ $periode }} — Computer Service Barry</div>

    <table class="tuiles">
        <tr>
            <td><div class="tuile-label">Note moyenne</div><div class="tuile-valeur">{{ $rapport['moyenne'] !== null ? number_format($rapport['moyenne'], 1, ',', '') : '—' }} / 5</div></td>
            <td><div class="tuile-label">Utilisateurs satisfaits</div><div class="tuile-valeur">{{ $rapport['pct_satisfaits'] !== null ? $rapport['pct_satisfaits'].' %' : '—' }}</div></td>
            <td><div class="tuile-label">Avis reçus</div><div class="tuile-valeur">{{ $rapport['total'] }}</div></td>
            <td><div class="tuile-label">Écoles</div><div class="tuile-valeur">{{ $rapport['nb_ecoles'] }}</div></td>
        </tr>
    </table>

    <h2>Répartition des notes</h2>
    <table class="barres">
        @foreach(array_reverse($rapport['repartition'], true) as $note => $n)
            <tr>
                <td style="width: 120px;">{{ $note }} — {{ $libelles[$note] }}</td>
                <td><div class="piste"><div class="barre" style="width: {{ round(260 * $n / $max) }}px;"></div></div></td>
                <td><strong>{{ $n }}</strong></td>
            </tr>
        @endforeach
    </table>

    @if($rapport['points_forts'])
        <h2>Ce que les écoles apprécient le plus</h2>
        <ul>
            @foreach(array_slice($rapport['points_forts'], 0, 5) as $p)
                <li>{{ $p['libelle'] }} ({{ $p['nombre'] }})</li>
            @endforeach
        </ul>
    @endif

    @if($citations->isNotEmpty())
        <h2>Ils en parlent</h2>
        @foreach($citations as $c)
            <div class="citation">
                « {{ $c->commentaire }} »
                <div class="auteur">{{ $c->nomPrenom }}{{ $c->fonction ? ', '.$c->fonction : '' }}{{ $c->nomEcole ? ' — '.$c->nomEcole : '' }} · {{ $c->note }}/5</div>
            </div>
        @endforeach
    @endif

    <div class="pied">Rapport établi le {{ now()->format('d/m/Y') }}. Les citations sont publiées avec l'accord de leurs auteurs. KalanNet — Computer Service Barry, computerservicebarry.com</div>
</body>
</html>
