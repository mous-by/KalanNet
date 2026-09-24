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
                        <label class="form-label">{{ __('configuration.menu_academies') }}</label>
                        <select name="id_academie" class="form-select" required>
                            <option value="">{{ __('configuration.selectionner') }}</option>
                            @foreach($academies as $academie)
                                <option value="{{ $academie->id_academie }}" @selected(old('id_academie', $cap->id_academie ?? null) == $academie->id_academie)>
                                    {{ $academie->nom_academie }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('configuration.cap_nom_label') }}</label>
                        <input type="text" name="nom_cap" class="form-control" value="{{ old('nom_cap', $cap->nom_cap ?? '') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('configuration.aca_th_code') }}</label>
                        <input type="text" name="code_cap" class="form-control" value="{{ old('code_cap', $cap->code_cap ?? '') }}" required>
                    </div>
                    <div>
                        <label class="form-label">{{ __('configuration.aca_th_localite') }}</label>
                        <input type="text" name="localite_cap" class="form-control" value="{{ old('localite_cap', $cap->localite_cap ?? '') }}" required>
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
