(function () {
    const root = document.getElementById('kalanbot-assistant');
    if (!root) return;

    const toggle = root.querySelector('.kalanbot-toggle');
    const closeButton = root.querySelector('[data-kalanbot-close]');
    const clearButton = root.querySelector('[data-kalanbot-clear]');
    const form = root.querySelector('.kalanbot-form');
    const input = root.querySelector('.kalanbot-input');
    const sendButton = root.querySelector('.kalanbot-send');
    const messagesEl = root.querySelector('.kalanbot-messages');
    const typing = root.querySelector('.kalanbot-typing');
    const micButton = root.querySelector('[data-kalanbot-mic]');
    const voiceToggle = root.querySelector('[data-kalanbot-voice-toggle]');
    const voiceStatus = root.querySelector('[data-kalanbot-voice-status]');
    const voiceStatusText = root.querySelector('[data-kalanbot-voice-status-text]');
    const endpoint = root.dataset.endpoint;
    const csrf = root.dataset.csrf;
    const history = [];

    // --- Voix : reconnaissance vocale (micro) et synthèse vocale (réponse) ---
    const SpeechRecognitionImpl = window.SpeechRecognition || window.webkitSpeechRecognition;
    const speechSynthesisAvailable = 'speechSynthesis' in window;
    let recognition = null;
    let recognizing = false;
    let lastMessageWasVoice = false;
    let voiceOutputEnabled = localStorage.getItem('kalanbot-voice-output') !== 'off';

    function updateVoiceToggleIcon() {
        if (!voiceToggle) return;
        const icon = voiceToggle.querySelector('i');
        icon.className = voiceOutputEnabled ? 'bi bi-volume-up-fill' : 'bi bi-volume-mute-fill';
        voiceToggle.setAttribute('aria-pressed', String(voiceOutputEnabled));
        voiceToggle.title = voiceOutputEnabled ? 'Désactiver la réponse vocale' : 'Activer la réponse vocale';
    }

    function speak(text) {
        if (!speechSynthesisAvailable || !voiceOutputEnabled || !text) return;
        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(text);
        utterance.lang = 'fr-FR';
        utterance.rate = 1;
        window.speechSynthesis.speak(utterance);
    }

    function setRecognizing(state) {
        recognizing = state;
        if (micButton) {
            micButton.classList.toggle('recording', state);
            micButton.setAttribute('aria-pressed', String(state));
        }
        if (voiceStatus) {
            voiceStatus.classList.toggle('d-none', !state);
        }
    }

    if (SpeechRecognitionImpl && micButton) {
        micButton.classList.remove('d-none');
        recognition = new SpeechRecognitionImpl();
        recognition.lang = 'fr-FR';
        recognition.continuous = false;
        recognition.interimResults = true;

        recognition.addEventListener('start', () => {
            setRecognizing(true);
            if (voiceStatusText) voiceStatusText.textContent = 'Je vous écoute…';
        });

        recognition.addEventListener('result', (event) => {
            let transcript = '';
            for (let i = 0; i < event.results.length; i++) {
                transcript += event.results[i][0].transcript;
            }
            input.value = transcript;
            sendButton.classList.toggle('d-none', transcript.trim() === '');

            const lastResult = event.results[event.results.length - 1];
            if (lastResult && lastResult.isFinal) {
                const message = transcript.trim();
                if (message) {
                    lastMessageWasVoice = true;
                    input.value = '';
                    sendButton.classList.add('d-none');
                    sendMessage(message);
                }
            }
        });

        recognition.addEventListener('error', (event) => {
            setRecognizing(false);
            if (event.error === 'not-allowed' || event.error === 'service-not-allowed') {
                appendMessage('assistant', micUnavailableMessage());
            }
        });

        recognition.addEventListener('end', () => setRecognizing(false));
    }

    if (speechSynthesisAvailable && voiceToggle) {
        voiceToggle.classList.remove('d-none');
        updateVoiceToggleIcon();
        voiceToggle.addEventListener('click', () => {
            voiceOutputEnabled = !voiceOutputEnabled;
            localStorage.setItem('kalanbot-voice-output', voiceOutputEnabled ? 'on' : 'off');
            updateVoiceToggleIcon();
            if (!voiceOutputEnabled) window.speechSynthesis.cancel();
        });
    }

    // Chrome/Edge exigent un contexte sécurisé (HTTPS ou localhost) pour le micro : sur une
    // IP locale en HTTP (ex: développement via réseau), le navigateur refuse silencieusement,
    // ce qui remonte comme une erreur "not-allowed" sans rapport avec un blocage volontaire.
    function micUnavailableMessage() {
        if (!window.isSecureContext) {
            return "Le micro nécessite une connexion sécurisée (HTTPS). Cette page est chargée en HTTP simple : "
                + "le navigateur bloque l'accès au micro par sécurité, indépendamment de vos réglages. "
                + "Utilisez l'adresse HTTPS du site, ou continuez à l'écrit.";
        }

        return "Je n'ai pas accès au micro. Vérifiez les autorisations de votre navigateur (icône à côté de "
            + "l'adresse du site), ou continuez à l'écrit.";
    }

    if (micButton && recognition) {
        micButton.addEventListener('click', () => {
            if (recognizing) {
                recognition.stop();
                return;
            }
            if (!window.isSecureContext) {
                appendMessage('assistant', micUnavailableMessage());
                return;
            }
            if (speechSynthesisAvailable) window.speechSynthesis.cancel();
            try {
                recognition.start();
            } catch (error) {
                setRecognizing(false);
            }
        });
    }

    function setOpen(open) {
        root.classList.toggle('open', open);
        if (open) {
            setTimeout(() => input.focus(), 80);
        }
    }

    function appendMessage(role, content) {
        const row = document.createElement('div');
        row.className = 'kalanbot-message ' + role;
        const bubble = document.createElement('div');
        bubble.className = 'kalanbot-bubble';
        bubble.textContent = content;
        row.appendChild(bubble);
        messagesEl.appendChild(row);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function setLoading(loading) {
        input.disabled = loading;
        sendButton.disabled = loading;
        typing.classList.toggle('d-none', !loading);
        messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    function visibleHistory() {
        return history.slice(-8);
    }

    function pageContext() {
        return {
            route: root.dataset.route || '',
            path: root.dataset.path || window.location.pathname,
            title: document.title || '',
        };
    }

    async function sendMessage(message) {
        const previousHistory = visibleHistory();
        appendMessage('user', message);
        history.push({role: 'user', content: message});
        setLoading(true);

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({
                    message,
                    history: previousHistory,
                    context: pageContext(),
                }),
            });

            const data = await response.json();
            const reply = data.reply || 'KalanBot est indisponible pour le moment.';
            appendMessage('assistant', reply);
            history.push({role: 'assistant', content: reply});
            if (lastMessageWasVoice) speak(reply);
        } catch (error) {
            const errorMessage = 'KalanBot est momentanément indisponible. Vérifiez la connexion ou réessayez plus tard.';
            appendMessage('assistant', errorMessage);
            if (lastMessageWasVoice) speak(errorMessage);
        } finally {
            setLoading(false);
            lastMessageWasVoice = false;
        }
    }

    toggle.addEventListener('click', () => setOpen(!root.classList.contains('open')));
    closeButton.addEventListener('click', () => {
        setOpen(false);
        if (speechSynthesisAvailable) window.speechSynthesis.cancel();
        if (recognizing) recognition.stop();
    });
    clearButton.addEventListener('click', () => {
        history.splice(0, history.length);
        messagesEl.innerHTML = '';
        appendMessage('assistant', "Conversation effacée. Comment puis-je vous guider dans KalanNet ?");
        if (speechSynthesisAvailable) window.speechSynthesis.cancel();
        input.focus();
    });

    input.addEventListener('input', function () {
        sendButton.classList.toggle('d-none', input.value.trim() === '');
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const message = input.value.trim();
        if (!message || input.disabled) return;
        input.value = '';
        sendButton.classList.add('d-none');
        sendMessage(message);
    });
})();
