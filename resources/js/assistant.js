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
    const endpoint = root.dataset.endpoint;
    const csrf = root.dataset.csrf;
    const history = [];

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
        } catch (error) {
            appendMessage('assistant', 'KalanBot est momentanément indisponible. Vérifiez la connexion ou réessayez plus tard.');
        } finally {
            setLoading(false);
        }
    }

    toggle.addEventListener('click', () => setOpen(!root.classList.contains('open')));
    closeButton.addEventListener('click', () => setOpen(false));
    clearButton.addEventListener('click', () => {
        history.splice(0, history.length);
        messagesEl.innerHTML = '';
        appendMessage('assistant', "Conversation effacée. Comment puis-je vous guider dans KalanNet ?");
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
