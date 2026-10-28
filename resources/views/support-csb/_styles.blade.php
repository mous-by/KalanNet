{{-- Couleurs des pages « Support CSB » tirées du thème choisi (variables de public/css/themes.css), jamais de couleur fixe. --}}
@push('styles')
    <style>
        .support-bulle { max-width: 85%; padding: .85rem 1rem; border-radius: 14px; }
        .support-bulle-moi { background: var(--theme-primary, #001529); color: var(--text-on-accent, #fff); border-bottom-right-radius: 4px; }
        .support-bulle-csb { background: var(--bg-main, #f1f5f9); color: var(--text-main, #1e293b); border: 1px solid var(--border-color, #e2e8f0); border-left: 4px solid var(--theme-accent, #001529); border-bottom-left-radius: 4px; }
        .support-bulle-date { opacity: .75; }
        .support-bulle-pj { border: 1px solid currentColor; color: inherit; }
        .support-bulle-pj:hover { background: var(--theme-accent, #001529); border-color: var(--theme-accent, #001529); color: var(--text-on-accent, #fff); }
        .support-tag { display: inline-block; padding: .25rem .6rem; border-radius: 999px; font-size: .8rem; font-weight: 600; background: var(--bg-main, #f1f5f9); color: var(--text-main, #1e293b); border: 1px solid var(--border-color, #e2e8f0); }
        .support-statut { display: inline-block; padding: .25rem .75rem; border-radius: 999px; font-size: .8rem; font-weight: 700; background: transparent; border: 1px solid currentColor; color: inherit; }
        .support-statut-liste { background: var(--theme-primary, #001529); border-color: var(--theme-primary, #001529); color: var(--text-on-accent, #fff) !important; }
        .support-statut.is-closed { opacity: .6; }
    </style>
@endpush
