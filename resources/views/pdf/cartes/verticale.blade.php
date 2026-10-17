{{-- Carte verticale (54 × 85,6 mm) : recto ($verso = false) ou dos ($verso = true). --}}
<div class="carte verticale v {{ $verso ? 'verso' : '' }}">
    <img class="fond" src="{{ $verso ? $decorVerso : $decor }}" alt="">
    @unless($verso)@include('pdf.cartes._officiel')@endunless

    <div class="abs tete {{ $logo && !$verso ? '' : 'sans-logo' }}">
        @if($logo && !$verso)<img class="logo" src="{{ $logo }}" alt="">@endif
        <div class="textes">
            <div class="nom-ecole {{ $nomLong ? 'long' : '' }}">{{ $nomEcole }}</div>
            @unless($verso)<div class="titre">{{ $titre }}</div>@endunless
        </div>
    </div>

    @if($verso)
        <div class="abs titre-verso">Informations utiles</div>
        <div class="abs lignes">
            <span class="lib">Élève</span><span class="val">{{ \App\Support\ArabePdf::texte(trim($eleve->prenom_eleve.' '.$eleve->nom_eleve)) }}</span>
            @if($telephoneEcole)<span class="lib">Téléphone de l’établissement</span><span class="val">{{ $telephoneEcole }}</span>@endif
            @if($ecole?->adresse)<span class="lib">Adresse</span><span class="val">{{ \Illuminate\Support\Str::limit($ecole->adresse, 60) }}</span>@endif
            @if($parent && ($parent->telephone_parent || $parent->nom_prenom_parent))
                <span class="lib">En cas d’urgence</span><span class="val">{{ trim(($parent->nom_prenom_parent ?? '').' '.($parent->telephone_parent ?? '')) }}</span>
            @endif
        </div>
        <div class="abs avis">Carte personnelle. Si vous la trouvez, merci de la rapporter à l’établissement.</div>
        @if($qrCode)<div class="abs qr"><img src="{{ $qrCode }}" alt=""></div>@endif
    @else
        <div class="abs photo">
            @if($hasPhoto)<img src="{{ $photoPath }}" alt="">@else<div class="vide">PHOTO</div>@endif
        </div>
        <div class="abs infos">
            <div class="nom">{{ \App\Support\ArabePdf::texte($eleve->nom_eleve) }}</div>
            <div class="prenom">{{ \App\Support\ArabePdf::texte($eleve->prenom_eleve) }}</div>
            @if($naissance)<div><span class="lib">Né(e) le :</span> {{ $naissance }}</div>@endif
            <div><span class="lib">Classe :</span> {{ $classe->nom_classe }}</div>
            @if($serie)<div><span class="lib">Série :</span> {{ $serie }}</div>@endif
            <div><span class="lib">Année :</span> {{ $annee->annee }}</div>
        </div>
        <div class="abs signature">
            <div>{{ $config['signature_label'] }}</div>
            @if(!empty($config['signature_image']))<img src="{{ $config['signature_image'] }}" alt="">@endif
        </div>
        @if($qrCode)<div class="abs qr"><img src="{{ $qrCode }}" alt=""></div>@endif
        @if($matricule)
            <div class="abs codebarres"><img src="{{ \App\Support\CodeBarres::svg($matricule) }}" alt=""><div>{{ $matricule }}</div></div>
        @endif
    @endif
</div>
