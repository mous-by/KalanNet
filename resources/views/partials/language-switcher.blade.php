@php
    $switcherId = $switcherId ?? 'languageSelector';
    $switcherClass = $switcherClass ?? 'kalannet-language-btn';
    $menuClass = $menuClass ?? 'dropdown-menu-end';
    $showLabel = $showLabel ?? true;
    $locales = $supportedLocales ?? config('app.supported_locales', []);
@endphp

<div class="dropdown language-switcher">
    <button class="btn btn-sm dropdown-toggle dropdown-toggle-nocaret {{ $switcherClass }}" id="{{ $switcherId }}" data-bs-toggle="dropdown" type="button" aria-expanded="false" title="{{ __('messages.language.change') }}">
        <i class="bi bi-translate"></i>
        @if($showLabel)
            <span class="language-label">{{ __('messages.language.label') }}</span>
        @endif
        <strong>{{ strtoupper(app()->getLocale()) }}</strong>
    </button>
    <ul class="dropdown-menu {{ $menuClass }} p-2" aria-labelledby="{{ $switcherId }}" style="min-width: 190px;">
        <li><h6 class="dropdown-header text-uppercase fw-bold small mb-2">{{ __('messages.language.available') }}</h6></li>
        @foreach($locales as $locale => $localeConfig)
            <li>
                <form method="POST" action="{{ route('language.update', $locale) }}">
                    @csrf
                    <button type="submit" class="dropdown-item d-flex align-items-center gap-2 rounded-2 mb-1 {{ app()->getLocale() === $locale ? 'active-theme' : '' }}">
                        <i class="bi bi-check-lg {{ app()->getLocale() === $locale ? '' : 'invisible' }}"></i>
                        <span lang="{{ $locale }}" dir="auto">{{ $localeConfig['native'] }}</span>
                    </button>
                </form>
            </li>
        @endforeach
    </ul>
</div>
