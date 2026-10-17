{{-- En-tête officiel : ministère, académie et CAP à gauche ; république et devise à droite (comme les bulletins). --}}
@if($officielGauche || $officielDroite || $drapeau)
    <div class="abs officiel">
        <div class="og">
            @foreach($officielGauche as $ligne)<div class="{{ $loop->first ? 'fort' : '' }}">{{ $ligne }}</div>@endforeach
        </div>
        @if($drapeau)<span class="drapeau"><span class="vert"></span><span class="or"></span><span class="rouge"></span></span>@endif
        <div class="od">
            @foreach($officielDroite as $ligne)<div class="{{ $loop->first ? 'fort' : '' }}">{{ $ligne }}</div>@endforeach
        </div>
    </div>
@endif
