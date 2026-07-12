<div
    id="kalanbot-assistant"
    class="kalanbot"
    data-endpoint="{{ route('assistant.chat') }}"
    data-csrf="{{ csrf_token() }}"
    data-route="{{ optional(request()->route())->getName() }}"
    data-path="{{ request()->path() }}"
>
    <button type="button" class="kalanbot-toggle" aria-label="Ouvrir KalanBot">
        <i class="bi bi-chat-dots-fill"></i>
        <span class="kalanbot-dot"></span>
    </button>

    <section class="kalanbot-panel" aria-live="polite" aria-label="Assistant KalanBot">
        <header class="kalanbot-header">
            <div>
                <div class="kalanbot-title">KalanBot</div>
                <div class="kalanbot-subtitle">Assistant <span style="color:#4ade80;font-weight:700;">Kal</span><span style="color:#eab308;font-weight:700;">an</span><span style="color:#f87171;font-weight:700;">Net</span></div>
            </div>
            <div class="kalanbot-actions">
                <button type="button" class="kalanbot-icon-btn d-none" data-kalanbot-voice-toggle title="Désactiver la réponse vocale" aria-label="Activer/désactiver la réponse vocale" aria-pressed="true">
                    <i class="bi bi-volume-up-fill"></i>
                </button>
                <button type="button" class="kalanbot-icon-btn" data-kalanbot-clear title="Effacer la conversation" aria-label="Effacer">
                    <i class="bi bi-trash"></i>
                </button>
                <button type="button" class="kalanbot-icon-btn" data-kalanbot-close title="Réduire" aria-label="Réduire">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </header>

        <div class="kalanbot-messages">
            <div class="kalanbot-message assistant">
                <div class="kalanbot-bubble">Bonjour, je suis KalanBot. Posez-moi une question sur l'utilisation de <span style="color:#4ade80;font-weight:700;">Kal</span><span style="color:#eab308;font-weight:700;">an</span><span style="color:#f87171;font-weight:700;">Net</span>, ou donnez-moi une instruction à exécuter (au clavier ou au micro 🎤).</div>
            </div>
        </div>

        <div class="kalanbot-typing d-none">
            <span></span><span></span><span></span>
        </div>

        <div class="kalanbot-voice-status d-none" data-kalanbot-voice-status>
            <span class="kalanbot-voice-dot"></span>
            <span data-kalanbot-voice-status-text>Je vous écoute…</span>
        </div>

        <form class="kalanbot-form">
            <button type="button" class="kalanbot-icon-btn kalanbot-mic d-none" data-kalanbot-mic title="Dicter un message" aria-label="Dicter un message" aria-pressed="false">
                <i class="bi bi-mic-fill"></i>
            </button>
            <input type="text" class="kalanbot-input" maxlength="1200" placeholder="Écrire un message..." autocomplete="off">
            <button type="submit" class="kalanbot-send d-none" aria-label="Envoyer">
                <i class="bi bi-arrow-up-circle-fill"></i>
            </button>
        </form>
    </section>
</div>

<style>{!! file_get_contents(resource_path('css/assistant.css')) !!}</style>

@push('scripts')
    <script>{!! file_get_contents(resource_path('js/assistant.js')) !!}</script>
@endpush
