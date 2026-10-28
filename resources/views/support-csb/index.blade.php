@extends('layouts.app')

@section('content')
    @include('support-csb._styles')
    @include('support-csb._header')

    <div class="card theme-card shadow-sm">
        <div class="card-header theme-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h5 class="mb-0 fw-bold"><i class="bi bi-headset me-2"></i>{{ __('support_csb.titre') }}</h5>
            <a href="{{ route('support-csb.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>{{ __('support_csb.nouveau') }}</a>
        </div>
        <div class="card-body p-4">
            <p class="text-secondary">{{ __('support_csb.sous_titre') }}</p>
            @if(empty($tickets))
                <div class="text-center py-5">
                    <i class="bi bi-chat-square-heart fs-1 text-secondary"></i>
                    <p class="mt-3 mb-3">{{ __('support_csb.aucune') }}</p>
                    <a href="{{ route('support-csb.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>{{ __('support_csb.nouveau') }}</a>
                </div>
            @else
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>{{ __('support_csb.activite') }}</th>
                                <th>{{ __('support_csb.objet') }}</th>
                                <th>{{ __('support_csb.statut') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($tickets as $ticket)
                                <tr>
                                    <td class="text-nowrap">{{ \Illuminate\Support\Carbon::parse($ticket['last_activity_at'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</td>
                                    <td>
                                        {{ $ticket['subject'] }}
                                        <div class="small text-secondary">{{ $ticket['number'] }} · {{ $ticket['category_label'] }}</div>
                                    </td>
                                    <td>
                                        <span class="support-statut support-statut-liste {{ $ticket['is_open'] ? '' : 'is-closed' }}">{{ $ticket['status_label'] }}</span>
                                        @if($ticket['awaiting_reporter'])
                                            <div class="small text-warning fw-semibold mt-1"><i class="bi bi-bell-fill me-1"></i>{{ __('support_csb.a_vous') }}</div>
                                        @endif
                                    </td>
                                    <td class="text-end"><a href="{{ route('support-csb.show', $ticket['number']) }}" class="btn btn-outline-primary btn-sm"><i class="bi bi-eye"></i></a></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
@endsection
