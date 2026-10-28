@extends('layouts.app')

@section('content')
    @include('support-csb._header', ['page' => __('support_csb.nouveau')])

    <div class="card theme-card shadow-sm">
        <div class="card-header theme-header">
            <h5 class="mb-0 fw-bold"><i class="bi bi-headset me-2"></i>{{ __('support_csb.nouveau') }}</h5>
        </div>
        <div class="card-body p-4">
            <p class="text-secondary">{{ __('support_csb.sous_titre') }}</p>
            <form method="POST" action="{{ route('support-csb.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-12">
                        <label for="subject" class="form-label fw-semibold">{{ __('support_csb.objet') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('subject') is-invalid @enderror" id="subject" name="subject" value="{{ old('subject') }}" maxlength="200" required>
                        @error('subject')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="category" class="form-label fw-semibold">{{ __('support_csb.type') }} <span class="text-danger">*</span></label>
                        <select class="form-select @error('category') is-invalid @enderror" id="category" name="category" required>
                            @foreach($options['categories'] ?? [] as $value => $label)
                                <option value="{{ $value }}" @selected(old('category', 'logiciel') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="priority" class="form-label fw-semibold">{{ __('support_csb.urgence') }} <span class="text-danger">*</span></label>
                        <select class="form-select @error('priority') is-invalid @enderror" id="priority" name="priority" required>
                            @foreach($options['priorities'] ?? [] as $value => $label)
                                <option value="{{ $value }}" @selected(old('priority', 'normale') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('priority')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="device" class="form-label fw-semibold">{{ __('support_csb.appareil') }}</label>
                        <input type="text" class="form-control @error('device') is-invalid @enderror" id="device" name="device" value="{{ old('device') }}" maxlength="200" aria-describedby="device-help">
                        <div class="form-text" id="device-help">{{ __('support_csb.appareil_aide') }}</div>
                    </div>
                    <div class="col-12">
                        <label for="message" class="form-label fw-semibold">{{ __('support_csb.description') }} <span class="text-danger">*</span></label>
                        <textarea class="form-control @error('message') is-invalid @enderror" id="message" name="message" rows="6" maxlength="10000" required aria-describedby="message-help">{{ old('message') }}</textarea>
                        <div class="form-text" id="message-help">{{ __('support_csb.description_aide') }}</div>
                        @error('message')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12">
                        <label for="attachment" class="form-label fw-semibold">{{ __('support_csb.piece_jointe') }}</label>
                        <input type="file" class="form-control @error('attachment') is-invalid @enderror" id="attachment" name="attachment" aria-describedby="attachment-help">
                        <div class="form-text" id="attachment-help">{{ __('support_csb.piece_jointe_aide') }}</div>
                        @error('attachment')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-12 d-flex justify-content-end gap-2">
                        <a href="{{ route('support-csb.index') }}" class="btn btn-secondary">{{ __('support_csb.retour') }}</a>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>{{ __('support_csb.envoyer') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
