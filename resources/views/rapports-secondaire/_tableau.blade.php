{{-- Un tableau du rapport, en saisie : une case par ligne et colonne ; totaux recalculés pendant la saisie. --}}
@php
    $texte = ($tableau['type'] ?? 'nombre') === 'texte';
    $colonnes = collect($tableau['colonnes'])->flatMap(fn ($g) => $g['cols'])->all();
    $groupee = collect($tableau['colonnes'])->contains(fn ($g) => $g['libelle'] !== null);
    $totaux = $tableau['totaux'] ?? null;
    $valeurs = $donnees['t'][$tableau['code']] ?? [];
@endphp
<div class="card theme-card shadow-sm mb-3">
    <div class="card-header theme-header"><h6 class="mb-0 fw-bold">{{ $tableau['numero'] ?? $tableau['code'] }} : {{ $tableau['titre'] }}</h6></div>
    <div class="card-body">
        @if(!empty($tableau['liste']))<p class="small text-muted mb-2">Une ligne par élément ; laissez vides les lignes inutiles, elles ne sont pas imprimées.</p>@endif
        @if(!empty($tableau['note']))<p class="small text-muted">{{ $tableau['note'] }}</p>@endif
        <div class="table-responsive">
            <table class="table table-sm table-bordered align-middle text-center mb-0" data-tableau @if($totaux) data-totaux="{{ $totaux }}" @endif style="font-size: .85rem;">
                <thead>
                    @if($groupee)
                        <tr>
                            <th rowspan="2"></th>
                            @foreach($tableau['colonnes'] as $groupe)<th colspan="{{ count($groupe['cols']) }}">{{ $groupe['libelle'] }}</th>@endforeach
                            @if(in_array($totaux, ['colonne', 'les deux'], true))<th rowspan="2">Total</th>@endif
                        </tr>
                    @endif
                    <tr>
                        @unless($groupee)<th></th>@endunless
                        @foreach($colonnes as $libelle)<th class="text-nowrap">{{ $libelle }}</th>@endforeach
                        @if(!$groupee && in_array($totaux, ['colonne', 'les deux'], true))<th>Total</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($tableau['lignes'] as $ligne => $libelle)
                        <tr data-ligne>
                            <td class="text-start text-nowrap fw-semibold">{{ $libelle }}</td>
                            @foreach($colonnes as $colonne => $l)
                                <td style="min-width: {{ $texte ? 140 : 62 }}px;">
                                    <input type="{{ $texte ? 'text' : 'number' }}" @unless($texte) min="0" max="99999" @endunless maxlength="150" class="form-control form-control-sm {{ $texte ? '' : 'text-center' }}"
                                           data-t="{{ $tableau['code'] }}|{{ $ligne }}|{{ $colonne }}" value="{{ $valeurs[$ligne][$colonne] ?? '' }}" aria-label="{{ $libelle }} {{ $l }}">
                                </td>
                            @endforeach
                            @if(in_array($totaux, ['colonne', 'les deux'], true))<td class="fw-bold" data-total-ligne></td>@endif
                        </tr>
                    @endforeach
                </tbody>
                @if(in_array($totaux, ['ligne', 'les deux'], true))
                    <tfoot class="fw-bold">
                        <tr><td class="text-start">Total</td>@foreach($colonnes as $colonne => $l)<td data-total-colonne="{{ $loop->index }}"></td>@endforeach @if(in_array($totaux, ['colonne', 'les deux'], true))<td data-total-general></td>@endif</tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>
