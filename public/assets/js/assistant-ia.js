(function(){
  if (!document) return;
  const toggle = document.getElementById('assistant-ia-toggle');
  const modalEl = document.getElementById('assistantIaModal');
  const chat = document.getElementById('assistantIaChat');
  const input = document.getElementById('assistantIaInput');
  const sendBtn = document.getElementById('assistantIaSend');
  const examples = document.querySelectorAll('.ai-examples .example');
  let bsModal = null;

  function esc(s){ return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
  // dedupe: avoid rendering duplicate **bot** bubbles while allowing user echoes
  // (bugfix) don't suppress a bot reply when the last rendered message was the *user* —
  // this prevented short chitchat replies (ex. user: "salut" → bot: "Salut ! …") from showing.
  let _lastRenderedText = null;
  let _lastRenderedSpeaker = null; // 'user' | 'bot'
  let _usedSuggestions = new Set();
  let _lastConcise = null;
  function appendMsg(text, who='bot'){
    if (!text && text !== 0) return;
    const normalized = String(text).replace(/\s+/g,' ').trim();

    // Suppress only when the *previous rendered message* was from the bot *and* identical.
    // This preserves: (a) repeated user messages, and (b) bot replies that echo a user's short greeting.
    if (who !== 'user' && _lastRenderedSpeaker === 'bot' && _lastRenderedText === normalized) {
      // keep a lightweight debug trace for developers (no effect in production UI)
      if (window && window.console && window.console.debug) window.console.debug('[AssistantIA] suppressed duplicate bot bubble:', normalized);
      return;
    }

    // update last-rendered tracking
    _lastRenderedText = normalized;
    _lastRenderedSpeaker = who === 'user' ? 'user' : 'bot';

    const div = document.createElement('div');
    div.className = 'ai-msg ' + (who==='user' ? 'user' : 'bot');
    const bubble = document.createElement('div');
    bubble.className = 'bubble';
    bubble.innerHTML = esc(text).replace(/\n/g,'<br>');
    div.appendChild(bubble);
    chat.appendChild(div);
    chat.scrollTop = chat.scrollHeight;
  }

  // ensure spinner is visible for at least `minVisibleMs` to be perceptible
  (function(){
    let _loadingSince = 0;
    const minVisibleMs = 200;
    window.__assistant_setLoading = function setLoading(on){
      const spinner = sendBtn.querySelector('.spinner-border');
      const text = sendBtn.querySelector('.btn-text');
      sendBtn.disabled = on;
      input.disabled = on;
      sendBtn.setAttribute('aria-busy', on ? 'true' : 'false');

      if (on) {
        _loadingSince = Date.now();
        spinner.classList.remove('visually-hidden');
        if (text) text.textContent = 'En cours…';
        return Promise.resolve();
      }

      const elapsed = Math.max(0, Date.now() - _loadingSince);
      const wait = Math.max(0, minVisibleMs - elapsed);
      return new Promise(resolve => setTimeout(() => {
        spinner.classList.add('visually-hidden');
        if (text) text.textContent = 'Envoyer';
        resolve();
      }, wait));
    };
  })();

  // Backward-compatible alias: older code in this file calls `setLoading(...)`.
  // The IIFE above exposes `window.__assistant_setLoading` — provide a safe
  // shim so calls won't throw (fixes: ReferenceError: setLoading is not defined).
  function setLoading(on){
    try {
      if (typeof window !== 'undefined' && typeof window.__assistant_setLoading === 'function') {
        return window.__assistant_setLoading(on);
      }
    } catch (e) {
      // swallow — degrade gracefully in older/hostile environments
      if (window && window.console && window.console.warn) window.console.warn('[AssistantIA] setLoading shim error', e);
    }
    return Promise.resolve();
  }

  async function ask(question){
    if (!question || question.trim().length === 0) return;
    appendMsg(question, 'user');
    // show spinner and allow browser to paint before starting the network request
    setLoading(true);
    await new Promise(r => requestAnimationFrame(()=>requestAnimationFrame(r)));

    // Feature flag: show extended debug output when URL contains ?debug_assistant=1
    const debugMode = (new URLSearchParams(window.location.search)).has('debug_assistant');

    // Build candidate endpoints (server-provided first, then common fallbacks)
    const candidates = [];
    // Prefer server-provided global, then modal data-attribute (robust to load order), then fallbacks
    const modalEndpoint = modalEl?.dataset?.endpoint;
    // Force a reliable default: front-controller path based on window.ROOT when available
    const forcedFrontController = (window.ROOT || '').replace(/\/$/, '') + '/index.php?url=AssistantIA/ask';

    // Ensure global variable exists and points to the front-controller if nothing else present
    if (!window.ASSISTANT_IA_ENDPOINT) {
      window.ASSISTANT_IA_ENDPOINT = modalEndpoint || (window.ROOT ? forcedFrontController : undefined);
    }

    if (window.ASSISTANT_IA_ENDPOINT) candidates.push(window.ASSISTANT_IA_ENDPOINT);
    if (modalEndpoint && modalEndpoint !== window.ASSISTANT_IA_ENDPOINT) candidates.push(modalEndpoint);

    // common fallbacks for different deployments
    const origin = window.location.origin;
    candidates.push(origin + '/AssistantIA/ask');
    candidates.push(origin + '/public/AssistantIA/ask');
    // keep forced front-controller as a candidate if not already present
    if (forcedFrontController && !candidates.includes(forcedFrontController)) candidates.unshift(forcedFrontController);

    if (debugMode) console.debug('[AssistantIA] candidates:', candidates);
    // try index.php?url= style (works when mod_rewrite is not configured)
    if (window.ROOT) {
      candidates.push(window.ROOT.replace(/\/$/, '') + '/index.php?url=AssistantIA/ask');
    }
    candidates.push(origin + '/index.php?url=AssistantIA/ask');

    // if app served from a subfolder, try to infer correct base path (look for 'public' or include first 2 segments)
    (function(){
      const parts = window.location.pathname.split('/').filter(Boolean); // remove empty
      let inferredBase = '';
      const publicIdx = parts.indexOf('public');
      if (publicIdx !== -1) {
        inferredBase = '/' + parts.slice(0, publicIdx + 1).join('/'); // include 'public'
      } else if (parts.length >= 2) {
        inferredBase = '/' + parts.slice(0, 2).join('/');
      } else if (parts.length === 1) {
        inferredBase = '/' + parts[0];
      }
      if (inferredBase && inferredBase !== '/') {
        candidates.push(origin + inferredBase + '/AssistantIA/ask');
        candidates.push(origin + inferredBase + '/index.php?url=AssistantIA/ask');
      }
    })();

    if (debugMode) appendMsg('Debug endpoints: ' + candidates.join(' | '));

    let lastError = null;
    for (const endpoint of candidates){
      try{
        console.debug('[AssistantIA] trying endpoint', endpoint);
        const res = await fetch(endpoint, {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ question }),
          credentials: 'same-origin'
        });

        // If we get an HTTP response, decide whether to stop or try the next candidate
        if (!res.ok){
          const status = res.status;
          console.debug('[AssistantIA] endpoint', endpoint, 'returned', status);
          // If 404 => try the next candidate silently
          if (status === 404) {
            lastError = new Error('404 Not Found at ' + endpoint);
            // continue to next candidate
            continue;
          }

          // For auth/permission errors or server errors, surface to the user
          let body = '';
          try { body = await res.text(); } catch(e) { /* ignore */ }
          const msg = `Erreur HTTP ${status} — ${res.statusText}` + (body ? ` (body: ${body.substring(0,200)})` : '');
          appendMsg(msg);
          setLoading(false);
          return;
        }

        // Inspect content-type first — HTML usually means a login page or server error
        const ct = (res.headers.get('content-type') || '').toLowerCase();
        if (ct.indexOf('text/html') !== -1) {
          const txt = await res.text().catch(()=>'<unreadable html>');
          const hint = 'Réponse HTML reçue — vous êtes peut‑être déconnecté ou une erreur serveur est survenue.';
          console.warn('[AssistantIA] html-response from', endpoint, txt.substring(0,800));
          if (debugMode) {
            appendMsg(hint + '\n' + txt.substring(0,1200));
          } else {
            appendMsg(hint + ' Rechargez la page ou vérifiez la session.');
          }
          setLoading(false);
          return; // don't try other candidates when we get HTML (likely redirect to login)
        }

        // Parse JSON safely — if parsing fails, show raw body so developers can see PHP notices
        let payload = null;
        try {
          payload = await res.json();
        } catch (parseErr) {
          const txt = await res.text().catch(()=>'<unreadable body>');
          const dbg = `Réponse invalide (non-JSON) depuis ${endpoint}: ` + txt.substring(0,1500);
          console.warn(dbg, parseErr);
          if (debugMode) appendMsg(dbg);
          // treat as failure and continue to next candidate
          lastError = parseErr;
          continue;
        }

        // If server returned an error payload, surface that to the user
        if (payload && payload.error) {
          appendMsg('Erreur: ' + (payload.error || 'serveur'));
          setLoading(false);
          return;
        }

        // If server indicates this question was already answered recently, show a short notice + expand affordance
        if (payload.already_shown || (_lastConcise && payload.concise && payload.concise === _lastConcise)) {
          appendMsg(payload.concise || payload.answer || 'Déjà expliqué.');

          if (payload.prompt_expand || payload.expanded !== true) {
            const moreBtn = document.createElement('button');
            moreBtn.className = 'btn btn-sm btn-outline-secondary mt-2';
            moreBtn.textContent = 'Voir plus';
            moreBtn.addEventListener('click', async () => {
              setLoading(true);
              try {
                const res = await fetch(window.ASSISTANT_IA_ENDPOINT || (window.ROOT || '') + '/index.php?url=AssistantIA/ask', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                  credentials: 'same-origin',
                  body: JSON.stringify({ question: payload.intent || input.value || '', expand: true, context: { last_intent: payload.intent || null } })
                });
                const expanded = await res.json();
                if (expanded.answer) appendMsg(expanded.answer);
                if (Array.isArray(expanded.steps) && expanded.steps.length) renderStepsOnce(expanded.steps);
                if (Array.isArray(expanded.pitfalls) && expanded.pitfalls.length) appendMsg('Points d\'attention :\n• ' + expanded.pitfalls.join('\n• '));
                setLoading(false);
              } catch(e) {
                appendMsg('Impossible de récupérer les détails — réessayez.');
                setLoading(false);
              }
            });
            const wrapper = document.createElement('div');
            wrapper.className = 'ai-more';
            wrapper.appendChild(moreBtn);
            chat.appendChild(wrapper);
          }

          if (Array.isArray(payload.follow_up) && payload.follow_up.length) renderFollowUps(payload.follow_up);
          _lastConcise = payload.concise || _lastConcise;
          setLoading(false);
          return;
        }

        // Normal rendering: show concise then allow expand for details
        if (payload.concise) {
          appendMsg(payload.concise);
        } else if (payload.answer) {
          appendMsg(payload.answer);
        }

        // helper: render steps only once (avoid duplicates)
        function renderStepsOnce(steps) {
          if (!Array.isArray(steps) || !steps.length) return;
          const lastSteps = chat.querySelector('.ai-steps[data-steps-hash]')?.getAttribute('data-steps-hash') || null;
          const hash = steps.join('\n');
          if (lastSteps === hash) return; // already shown
          const text = 'Étapes :\n' + steps.map((s,i)=> (i+1)+') '+s).join('\n');
          const container = document.createElement('div');
          container.className = 'ai-steps';
          container.setAttribute('data-steps-hash', hash);
          const bubble = document.createElement('div');
          bubble.className = 'bubble';
          bubble.innerHTML = esc(text).replace(/\n/g,'<br>');
          container.appendChild(bubble);
          chat.appendChild(container);
          chat.scrollTop = chat.scrollHeight;
        }

        if (Array.isArray(payload.steps) && payload.steps.length) {
          // show a compact expand control instead of dumping steps immediately
          const moreBtn = document.createElement('button');
          moreBtn.className = 'btn btn-sm btn-outline-secondary mt-2';
          moreBtn.textContent = 'Voir plus';
          moreBtn.addEventListener('click', ()=> renderStepsOnce(payload.steps));
          const wrapper = document.createElement('div');
          wrapper.className = 'ai-more';
          wrapper.appendChild(moreBtn);
          chat.appendChild(wrapper);
        }

        if (Array.isArray(payload.pitfalls) && payload.pitfalls.length) {
          appendMsg('Points d\'attention :\n• ' + payload.pitfalls.join('\n• '));
        }

        // render follow ups (deduped)
        function renderFollowUps(list) {
          if (!Array.isArray(list) || !list.length) return;
          const ul = document.createElement('div');
          ul.className = 'ai-followups';
          list.forEach(q => {
            const qNorm = q.replace(/\s+/g,' ').trim();
            if (_usedSuggestions.has(qNorm)) return;
            _usedSuggestions.add(qNorm);
            const ex = document.createElement('div');
            ex.className = 'ai-suggestion example';
            ex.textContent = q;
            ex.style.cursor = 'pointer';
            ex.addEventListener('click', ()=>{ _usedSuggestions.add(qNorm); input.value = q; ask(q); ex.remove(); });
            ul.appendChild(ex);
          });
          if (ul.children.length) chat.appendChild(ul);
          chat.scrollTop = chat.scrollHeight;
        }

        // examples (backwards compatible)
        if (Array.isArray(payload.examples) && payload.examples.length) {
          // show examples as light suggestions, but dedup with follow_up
          renderFollowUps(payload.examples);
        }

        // store concise to detect repeated questions client-side
        _lastConcise = payload.concise || _lastConcise;

        setLoading(false);
        return; // success
      }catch(err){
        console.warn('[AssistantIA] endpoint failed', endpoint, err);
        lastError = err;
        // continue to next candidate
      }
    }

    // all attempts failed
    if (debugMode) {
      appendMsg('Toutes les tentatives ont échoué. Endpoints testés: ' + candidates.join(', '));
      appendMsg('Détail erreur (console) — copiez/collez dans votre ticket.');
    } else {
      appendMsg('Erreur réseau — réessayez.');
    }
    console.error('[AssistantIA] all endpoints failed', lastError);
    setLoading(false);
  }

  // primary trigger: navbar `#assistant-ia-toggle`. Legacy sidebar selector `.assistant-ia-toggle` retained for backward compatibility.
  const toggles = Array.from(document.querySelectorAll('#assistant-ia-toggle, .assistant-ia-toggle'));
  if (toggles.length > 0 && modalEl){
    // bootstrap modal lazy init (works with bootstrap 5 bundle already loaded)
    modalEl.addEventListener('shown.bs.modal', ()=> { input.focus(); });

    toggles.forEach(toggleEl => {
      toggleEl.addEventListener('click', (e)=>{
        e.preventDefault();
        if (!bsModal) bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();
        // start with an empty chat — the assistant replies when the user asks or uses suggestions provided by the server.
      });
    });

    sendBtn.addEventListener('click', ()=>{ const q = input.value.trim(); if (!q) return; input.value = ''; ask(q); });
    input.addEventListener('keydown', (ev)=>{ if(ev.key === 'Enter' && !ev.shiftKey){ ev.preventDefault(); sendBtn.click(); } });
    examples.forEach(b=> b.addEventListener('click', ()=>{ const q = b.textContent.trim(); input.value = q; ask(q); }));
  }

  // Accessibility: close modal on Escape handled by Bootstrap
})();
