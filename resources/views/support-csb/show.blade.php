@extends('layouts.app')

@section('content')
    @include('support-csb._styles')
    @include('support-csb._header', ['page' => $ticket['number']])

    <div class="card theme-card shadow-sm">
        <div class="card-header theme-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="mb-0 fw-bold"><i class="bi bi-headset me-2"></i>{{ $ticket['subject'] }}</h5>
            <span class="support-statut {{ $ticket['is_open'] ? '' : 'is-closed' }}">{{ $ticket['status_label'] }}</span>
        </div>
        <div class="card-body p-4">
            <div class="d-flex flex-wrap gap-2 small mb-3">
                <span class="support-tag">{{ $ticket['number'] }}</span>
                <span class="support-tag">{{ $ticket['category_label'] }}</span>
                <span class="support-tag">{{ __('support_csb.urgence') }} : {{ $ticket['priority_label'] }}</span>
                @if($ticket['device'])<span class="support-tag"><i class="bi bi-laptop me-1"></i>{{ $ticket['device'] }}</span>@endif
            </div>

            @foreach($ticket['messages'] as $message)
                @php($fromMe = $message['from'] === 'reporter')
                <div class="d-flex {{ $fromMe ? 'justify-content-end' : 'justify-content-start' }} mb-3">
                    <div class="support-bulle {{ $fromMe ? 'support-bulle-moi' : 'support-bulle-csb' }}">
                        <div class="small fw-semibold mb-1">
                            @if($fromMe)
                                <i class="bi bi-person me-1"></i>{{ __('support_csb.vous') }}
                            @else
                                <i class="bi bi-headset me-1"></i>{{ $message['author'] }} · CSB
                            @endif
                            <span class="support-bulle-date fw-normal ms-2">{{ \Illuminate\Support\Carbon::parse($message['created_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</span>
                        </div>
                        <div class="text-break">{!! nl2br(e($message['body'])) !!}</div>
                        @if($message['attachment'])
                            <a href="{{ route('support-csb.attachment', [$ticket['number'], $message['id']]) }}" class="btn btn-sm mt-2 support-bulle-pj"><i class="bi bi-paperclip me-1"></i>{{ $message['attachment']['name'] }}</a>
                        @endif
                    </div>
                </div>
            @endforeach

            @if(!$ticket['is_open'])
                <div class="alert alert-secondary small">{{ __('support_csb.ferme') }}</div>
            @elseif($ticket['awaiting_reporter'])
                <div class="alert alert-warning small"><i class="bi bi-bell-fill me-1"></i>{{ __('support_csb.a_vous') }}</div>
            @endif

            <form method="POST" action="{{ route('support-csb.reply', $ticket['number']) }}" enctype="multipart/form-data" class="mt-3">
                @csrf
                <label for="body" class="form-label fw-semibold">{{ __('support_csb.repondre') }}</label>
                <textarea class="form-control mb-2 @error('body') is-invalid @enderror" id="body" name="body" rows="4" maxlength="10000" required>{{ old('body') }}</textarea>
                <div class="d-flex flex-wrap gap-2 justify-content-between">
                    <input type="file" class="form-control form-control-sm w-auto" name="attachment" aria-label="{{ __('support_csb.piece_jointe') }}">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-send me-1"></i>{{ __('support_csb.envoyer') }}</button>
                </div>
            </form>
        </div>
    </div>
@endsection
