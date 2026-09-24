<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <form action="{{ $action }}" method="POST">
                @csrf
                @if($method !== 'POST')
                    @method($method)
                @endif
                <div class="modal-header theme-header">
                    <h5 class="modal-title fw-bold">{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('configuration.fermer') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('configuration.aca_nom_label') }}</label>
                        <input type="text" name="nom_academie" class="form-control" value="{{ old('nom_academie', $academie->nom_academie ?? '') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('configuration.aca_th_code') }}</label>
                        <input type="text" name="code_academie" class="form-control" value="{{ old('code_academie', $academie->code_academie ?? '') }}" required>
                    </div>
                    <div>
                        <label class="form-label">{{ __('configuration.aca_th_localite') }}</label>
                        <input type="text" name="localite_academie" class="form-control" value="{{ old('localite_academie', $academie->localite_academie ?? '') }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">{{ __('configuration.annuler') }}</button>
                    <button type="submit" class="btn btn-primary px-4">{{ __('configuration.enregistrer') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
