{{-- Carte scolaire horizontale (85,6 × 54 mm). Variables : voir pdf/cartes_scolaires. --}}
<div class="carte m-{{ $modele }}">
    <img class="fond" src="{{ $decor }}" alt="">

    <div class="abs tete {{ $logo ? '' : 'sans-logo' }}">
        @if($logo)<img class="logo" src="{{ $logo }}" alt="">@endif
        <div class="textes">
            @if($entete !== '' || $drapeau)
                <div class="entete">@if($drapeau)<span class="drapeau"><span class="vert"></span><span class="or"></span><span class="rouge"></span></span>@endif{{ $entete }}</div>
            @endif
            <div class="nom-ecole">{{ $nomEcole }}</div>
            <div class="titre">{{ $titre }}@if($modele === 'franco_arabe') · <span class="arabe">{{ \App\Support\ArabePdf::texte('البطاقة المدرسية') }}</span>@endif</div>
        </div>
    </div>

    <div class="abs photo">
        @if($hasPhoto)<img src="{{ $photoPath }}" alt="">@else<div class="vide">PHOTO</div>@endif
    </div>

    <div class="abs infos">
        <div class="nom">{{ \App\Support\ArabePdf::texte($eleve->nom_eleve) }}</div>
        <div class="prenom">{{ \App\Support\ArabePdf::texte($eleve->prenom_eleve) }}</div>
        @if($naissance)<div><span class="lib">Né(e) le :</span> {{ $naissance }}</div>@endif
        @if($modele === 'sante')
            @if($filiere)<div><span class="lib">Filière :</span> {{ $filiere }}</div>@endif
            <div><span class="lib">Niveau :</span> {{ $classe->nom_classe }}</div>
        @else
            <div><span class="lib">Classe :</span> {{ $classe->nom_classe }}</div>
            @if($serie)<div><span class="lib">Série :</span> {{ $serie }}</div>@endif
        @endif
        <div><span class="lib">Année :</span> {{ $annee->annee }}</div>
    </div>

    @if($qrCode)
        <div class="abs qr"><img src="{{ $qrCode }}" alt=""></div>
    @endif

    @if($modele === 'elegant')
        @if($matricule)<div class="abs bandeau">{{ $matricule }}</div>@endif
    @elseif($matricule)
        <div class="abs codebarres"><img src="{{ \App\Support\CodeBarres::svg($matricule) }}" alt=""><div>{{ $matricule }}</div></div>
    @endif

    <div class="abs signature">
        <div>{{ $config['signature_label'] }}</div>
        @if(!empty($config['signature_image']))<div class="encre"><img src="{{ $config['signature_image'] }}" alt=""></div>@endif
    </div>
</div>
