{{-- Croquis du modèle : donne l'allure générale sans charger de PDF. --}}
@switch($code)
    @case('standard')
        @for($e = 0; $e < 2; $e++)
            <div class="ex">
                <div class="d-flex justify-content-between"><i style="width:35%"></i><i style="width:35%"></i></div>
                <i class="w60 c"></i>
                <i class="band" style="background:transparent; border:1.5px double #475569; height:8px"></i>
                <div class="rows">@for($i = 0; $i < 7; $i++)<i class="{{ $i % 2 ? 'w80' : '' }}" style="background:#cbd5e1"></i>@endfor</div>
            </div>
        @endfor
        @break
    @case('classique')
        <i class="w60 c"></i><i class="w40 c"></i>
        <i class="band"></i>
        <div class="rows">@for($i = 0; $i < 9; $i++)<i class="{{ $i % 3 ? 'w80' : '' }}"></i>@endfor</div>
        <div class="boxes"><b class="f"></b><b></b></div>
        @break
    @case('domaines')
        <div class="d-flex justify-content-between"><i style="width:35%"></i><i style="width:30%"></i></div>
        <i class="band"></i>
        <div class="rows">
            @for($d = 0; $d < 4; $d++)<i class="dom"></i>@for($i = 0; $i < 2; $i++)<i class="w80"></i>@endfor @endfor
        </div>
        <div class="boxes"><b></b><b></b></div>
        <div class="boxes" style="margin-top:2px"><b class="f" style="height:7px"></b></div>
        @break
    @case('essentiel')
        <i class="w60"></i><i class="band"></i>
        <div class="rows">@for($i = 0; $i < 8; $i++)<i class="{{ $i % 2 ? 'w80' : '' }}"></i>@endfor</div>
        <div class="boxes"><b class="f"></b><b></b><b></b></div>
        @break
    @case('domaines_compact')
        <div class="d-flex justify-content-between"><i style="width:35%"></i><i style="width:30%"></i></div>
        <i class="band"></i>
        <div class="rows">@for($d = 0; $d < 4; $d++)<i class="dom"></i><i class="w80"></i>@endfor</div>
        <div class="boxes"><b></b><b></b></div>
        @break
    @case('moderne')
        @for($e = 0; $e < 2; $e++)
            <div class="ex">
                <i class="w60 c"></i>
                <div class="d-flex align-items-center gap-1"><span class="avatar"></span><i style="width:45%"></i></div>
                @foreach([70, 55, 40, 80, 25, 60] as $largeur)
                    <div class="bar"><i style="flex:0 0 30%"></i><s style="width:{{ $largeur }}%; max-width:55%; background:#222"></s></div>
                @endforeach
            </div>
        @endfor
        @break
@endswitch
