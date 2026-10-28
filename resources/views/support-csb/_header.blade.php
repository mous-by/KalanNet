{{-- En-tête des pages « Support CSB » : fil d'Ariane et messages. $page : libellé de la page courante (facultatif). --}}
<div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
    <div class="breadcrumb-title pe-3">{{ __('support_csb.titre') }}</div>
    <div class="ps-3">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 p-0">
                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}"><i class="bi bi-house-door"></i></a></li>
                @if(!empty($page))
                    <li class="breadcrumb-item"><a href="{{ route('support-csb.index') }}">{{ __('support_csb.mes_demandes') }}</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $page }}</li>
                @else
                    <li class="breadcrumb-item active" aria-current="page">{{ __('support_csb.mes_demandes') }}</li>
                @endif
            </ol>
        </nav>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success border-0 shadow-sm">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="alert alert-danger border-0 shadow-sm">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="alert alert-danger border-0 shadow-sm">{{ $errors->first() }}</div>
@endif
