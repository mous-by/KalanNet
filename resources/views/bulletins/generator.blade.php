<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Génération des bulletins – KalanNet</title>
    <script src="https://cdn.jsdelivr.net/npm/jszip@3.10.1/dist/jszip.min.js"></script>
    <script src="https://unpkg.com/pdf-lib@1.17.1/dist/pdf-lib.min.js"></script>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --green:  #16a34a;
            --amber:  #f59e0b;
            --red:    #ef4444;
            --bg:     #0b2416;
            --card:   #112a1b;
            --border: #1e4330;
            --text:   #e8f5ee;
            --muted:  #7eaa8e;
            --white:  #ffffff;
        }

        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: var(--bg);
            color: var(--text);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* ─── HEADER ─────────────────────────────────────────── */
        .kn-header {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 20px;
            background: var(--card);
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }
        .kn-logo-mark {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, #0a3d20, #198754);
            border-radius: 9px;
            display: flex; align-items: center; justify-content: center;
            font-size: 15px; font-weight: 900; color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,.4);
        }
        .kn-header-text h1 { font-size: 15px; font-weight: 700; color: var(--white); }
        .kn-header-text p  { font-size: 11px; color: var(--muted); margin-top: 1px; }

        /* ─── PROGRESS SECTION ───────────────────────────────── */
        .kn-progress-section {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 14px 20px;
            background: var(--card);
            border-bottom: 1px solid var(--border);
            flex-shrink: 0;
        }

        /* Tri-color SVG spinner */
        .kn-spinner-wrap { flex-shrink: 0; }
        .kn-spinner-svg  { width: 72px; height: 72px; display: block; }

        @keyframes spin-cw  { to { transform: rotate( 360deg); } }
        @keyframes spin-ccw { to { transform: rotate(-360deg); } }

        .arc-green { animation: spin-cw  1.6s linear infinite; transform-origin: 70px 70px; }
        .arc-amber { animation: spin-ccw 1.1s linear infinite; transform-origin: 70px 70px; }
        .arc-red   { animation: spin-cw  0.7s linear infinite; transform-origin: 70px 70px; }

        body.done .arc-green,
        body.done .arc-amber,
        body.done .arc-red { animation-play-state: paused; }

        .kn-progress-info { flex: 1; min-width: 0; }

        .kn-progress-label {
            font-size: 13px;
            font-weight: 600;
            color: var(--white);
            margin-bottom: 7px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .kn-progress-track {
            height: 13px;
            background: rgba(255,255,255,.08);
            border-radius: 99px;
            overflow: hidden;
        }
        .kn-progress-fill {
            height: 100%;
            width: 0%;
            border-radius: 99px;
            background: linear-gradient(90deg, var(--green) 0%, var(--amber) 55%, var(--red) 100%);
            transition: width .4s ease;
            position: relative;
        }
        .kn-progress-fill::after {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,.22), transparent);
            background-size: 200% 100%;
            animation: shimmer 1.6s infinite;
        }
        @keyframes shimmer {
            0%   { background-position: -200% 0; }
            100% { background-position:  200% 0; }
        }
        .kn-progress-stats {
            margin-top: 5px;
            font-size: 11px;
            color: var(--muted);
            display: flex;
            justify-content: space-between;
        }

        /* ─── MAIN 3 COLUMNS ─────────────────────────────────── */
        .kn-main {
            display: flex;
            flex: 1;
            overflow: hidden;
            min-height: 0;
        }

        /* Column shared styles */
        .kn-col {
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .kn-col + .kn-col { border-left: 1px solid var(--border); }

        .kn-col-pending   { width: 230px; min-width: 180px; }
        .kn-col-done      { width: 260px; min-width: 200px; }
        .kn-col-preview   { flex: 1; background: #111; }

        .kn-col-title {
            padding: 8px 14px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .9px;
            color: var(--muted);
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            gap: 6px;
            flex-shrink: 0;
        }
        .kn-badge {
            margin-left: auto;
            border-radius: 99px;
            padding: 1px 8px;
            font-size: 10px;
            font-weight: 700;
        }
        .kn-badge-pending { background: rgba(245,158,11,.2); color: var(--amber); }
        .kn-badge-done    { background: rgba(22,163,74,.2);  color: var(--green); }

        /* Scrollable list area */
        .kn-scroll {
            flex: 1;
            overflow-y: auto;
            padding: 4px 0;
        }
        .kn-scroll::-webkit-scrollbar { width: 5px; }
        .kn-scroll::-webkit-scrollbar-track { background: transparent; }
        .kn-scroll::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

        /* ── Pending items ── */
        @keyframes slideRemove {
            0%   { opacity:1; max-height:46px; transform:translateX(0); }
            55%  { opacity:0; max-height:46px; transform:translateX(-20px); }
            100% { opacity:0; max-height:0; padding:0; border:none; }
        }
        .kn-pending-item {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 7px 14px;
            border-bottom: 1px solid rgba(255,255,255,.03);
        }
        .kn-pending-item.active  { background: rgba(245,158,11,.08); }
        .kn-pending-item.removing {
            overflow: hidden;
            animation: slideRemove .32s ease forwards;
            pointer-events: none;
        }
        .kn-status-dot {
            width: 20px; height: 20px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 11px; font-weight: 700;
            flex-shrink: 0;
        }
        .kn-status-dot.waiting  { background: rgba(255,255,255,.07); color: var(--muted); }
        .kn-status-dot.spinning { background: rgba(245,158,11,.18); color: var(--amber); animation: pulse .9s infinite; }
        .kn-status-dot.done     { background: rgba(22,163,74,.2);   color: var(--green); }
        .kn-status-dot.error    { background: rgba(239,68,68,.15);  color: var(--red);   }
        @keyframes pulse { 0%,100%{opacity:1}50%{opacity:.4} }

        .kn-pname {
            font-size: 12px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: var(--text);
        }
        .kn-pname.done  { color: var(--green); }
        .kn-pname.error { color: var(--red);   }

        /* ── Completed items (download links) ── */
        @keyframes slideIn {
            from { opacity:0; transform:translateX(20px); }
            to   { opacity:1; transform:translateX(0);    }
        }
        .kn-done-item {
            display: flex;
            align-items: center;
            gap: 9px;
            padding: 6px 14px;
            border-bottom: 1px solid rgba(255,255,255,.03);
            animation: slideIn .25s ease;
        }
        .kn-done-item:hover { background: rgba(22,163,74,.06); }

        .kn-done-name {
            flex: 1;
            font-size: 12px;
            color: var(--text);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .kn-dl-link {
            flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            width: 28px; height: 28px;
            border-radius: 7px;
            background: rgba(22,163,74,.15);
            color: var(--green);
            text-decoration: none;
            font-size: 14px;
            transition: background .15s;
        }
        .kn-dl-link:hover { background: rgba(22,163,74,.3); }
        .kn-view-link {
            flex-shrink: 0;
            display: flex; align-items: center; justify-content: center;
            width: 28px; height: 28px;
            border-radius: 7px;
            background: rgba(255,255,255,.06);
            color: var(--muted);
            text-decoration: none;
            font-size: 13px;
            cursor: pointer;
            border: none;
            transition: background .15s;
        }
        .kn-view-link:hover { background: rgba(255,255,255,.12); color: var(--white); }

        /* Empty state messages */
        .kn-empty {
            padding: 28px 14px;
            text-align: center;
            font-size: 12px;
            color: var(--muted);
            line-height: 1.6;
        }
        .kn-empty.success { color: var(--green); font-weight: 600; }

        /* ── Preview column ── */
        .kn-preview-wrap {
            flex: 1;
            position: relative;
            overflow: hidden;
        }
        .kn-preview-placeholder {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            color: #555;
            padding: 20px;
            text-align: center;
        }
        .kn-preview-placeholder span { font-size: 44px; opacity: .3; }
        .kn-preview-placeholder p    { font-size: 12px; max-width: 220px; line-height: 1.5; }

        #kn-preview-iframe {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            border: none;
            display: none;
        }

        /* ─── FOOTER ─────────────────────────────────────────── */
        .kn-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 20px;
            background: var(--card);
            border-top: 1px solid var(--border);
            gap: 10px;
            flex-shrink: 0;
        }
        .kn-footer-status { font-size: 12px; color: var(--muted); }
        .kn-footer-status.done  { color: var(--green); font-weight: 600; }
        .kn-footer-status.error { color: var(--amber); }

        .kn-footer-actions { display: flex; gap: 8px; align-items: center; }

        .kn-btn {
            padding: 7px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: opacity .2s, transform .1s;
            text-decoration: none;
        }
        .kn-btn:active  { transform: scale(.97); }
        .kn-btn:disabled { opacity: .35; cursor: not-allowed; transform: none; }

        .kn-btn-merge {
            background: linear-gradient(135deg, #16a34a, #15803d);
            color: #fff;
            box-shadow: 0 2px 8px rgba(22,163,74,.35);
        }
        .kn-btn-merge:hover:not(:disabled) { opacity: .88; }

        .kn-btn-zip {
            background: rgba(245,158,11,.12);
            color: #fbbf24;
            border: 1px solid rgba(245,158,11,.3);
        }
        .kn-btn-zip:hover:not(:disabled) { background: rgba(245,158,11,.22); }

        .kn-btn-close {
            background: rgba(255,255,255,.07);
            color: var(--text);
            border: 1px solid var(--border);
        }
        .kn-btn-close:hover { background: rgba(255,255,255,.12); }

        /* Overlay (ZIP / fusion) */
        .kn-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.65);
            backdrop-filter: blur(4px);
            z-index: 999;
            align-items: center;
            justify-content: center;
        }
        .kn-overlay.show { display: flex; }
        .kn-overlay-box {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 32px 40px;
            text-align: center;
            min-width: 300px;
        }
        .kn-overlay-box h3 { font-size: 15px; margin-bottom: 6px; color: var(--white); }
        .kn-overlay-box p  { font-size: 12px; color: var(--muted); margin-top: 8px; line-height: 1.5; }
        .kn-overlay-spinner {
            width: 48px; height: 48px;
            border: 5px solid rgba(255,255,255,.1);
            border-top-color: var(--green);
            border-radius: 50%;
            animation: spin-cw .8s linear infinite;
            margin: 0 auto 16px;
        }
        .kn-overlay-progress {
            height: 6px;
            background: rgba(255,255,255,.08);
            border-radius: 99px;
            margin-top: 14px;
            overflow: hidden;
        }
        .kn-overlay-bar {
            height: 100%;
            width: 0%;
            background: var(--green);
            border-radius: 99px;
            transition: width .3s ease;
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <header class="kn-header">
        <div class="kn-logo-mark">KN</div>
        <div class="kn-header-text">
            <h1>Génération des bulletins</h1>
            <p>{{ $classe->nom_classe }}</p>
        </div>
    </header>

    <!-- PROGRESS -->
    <div class="kn-progress-section">
        <div class="kn-spinner-wrap">
            <svg class="kn-spinner-svg" viewBox="0 0 140 140" xmlns="http://www.w3.org/2000/svg">
                <circle class="arc-green" cx="70" cy="70" r="60"
                    fill="none" stroke="#16a34a" stroke-width="12"
                    stroke-dasharray="265 112" stroke-linecap="round"/>
                <circle class="arc-amber" cx="70" cy="70" r="46"
                    fill="none" stroke="#f59e0b" stroke-width="12"
                    stroke-dasharray="185 103" stroke-linecap="round"/>
                <circle class="arc-red" cx="70" cy="70" r="32"
                    fill="none" stroke="#ef4444" stroke-width="12"
                    stroke-dasharray="115 87" stroke-linecap="round"/>
                <circle cx="70" cy="70" r="18" fill="#0a3d20"/>
                <text x="70" y="75" text-anchor="middle" fill="#fff"
                    font-size="12" font-weight="900"
                    font-family="Arial Black, Arial, sans-serif">KN</text>
            </svg>
        </div>
        <div class="kn-progress-info">
            <div class="kn-progress-label" id="kn-label">Chargement de la liste des élèves...</div>
            <div class="kn-progress-track">
                <div class="kn-progress-fill" id="kn-fill"></div>
            </div>
            <div class="kn-progress-stats">
                <span id="kn-count">0 / 0 bulletins</span>
                <span id="kn-pct">0%</span>
            </div>
        </div>
    </div>

    <!-- MAIN : 3 colonnes -->
    <div class="kn-main">

        <!-- Colonne 1 : En attente -->
        <div class="kn-col kn-col-pending">
            <div class="kn-col-title">
                En attente
                <span class="kn-badge kn-badge-pending" id="kn-badge-pending">0</span>
            </div>
            <div class="kn-scroll" id="kn-pending-list">
                <div class="kn-pending-item">
                    <div class="kn-status-dot spinning">⟳</div>
                    <span class="kn-pname">Chargement...</span>
                </div>
            </div>
        </div>

        <!-- Colonne 2 : Bulletins téléchargeables -->
        <div class="kn-col kn-col-done">
            <div class="kn-col-title">
                Téléchargements
                <span class="kn-badge kn-badge-done" id="kn-badge-done">0</span>
            </div>
            <div class="kn-scroll" id="kn-done-list">
                <div class="kn-empty" id="kn-done-empty">Les bulletins générés<br>apparaîtront ici</div>
            </div>
        </div>

        <!-- Colonne 3 : Aperçu PDF -->
        <div class="kn-col kn-col-preview">
            <div class="kn-col-title">Aperçu</div>
            <div class="kn-preview-wrap">
                <div class="kn-preview-placeholder" id="kn-placeholder">
                    <span>📄</span>
                    <p>Cliquez sur 👁 pour prévisualiser un bulletin</p>
                </div>
                <iframe id="kn-preview-iframe" title="Aperçu bulletin"></iframe>
            </div>
        </div>

    </div>

    <!-- FOOTER -->
    <div class="kn-footer">
        <div class="kn-footer-status" id="kn-status">⏳ Génération en cours...</div>
        <div class="kn-footer-actions">
            <button id="kn-btn-merge" class="kn-btn kn-btn-merge" disabled>
                📄 Télécharger en 1 seul PDF
            </button>
            <button id="kn-btn-zip" class="kn-btn kn-btn-zip" disabled>
                📦 Télécharger en ZIP
            </button>
            <button onclick="window.close()" class="kn-btn kn-btn-close">✕ Fermer</button>
        </div>
    </div>

    <!-- Overlay traitement (fusion / zip) -->
    <div class="kn-overlay" id="kn-overlay">
        <div class="kn-overlay-box">
            <div class="kn-overlay-spinner"></div>
            <h3 id="kn-overlay-title">Traitement en cours...</h3>
            <p id="kn-overlay-msg"></p>
            <div class="kn-overlay-progress">
                <div class="kn-overlay-bar" id="kn-overlay-bar"></div>
            </div>
        </div>
    </div>

<script>
(function () {
    const CLASS_NAME    = @json($classe->nom_classe);
    const STUDENTS_URL  = @json(route('pedagogie.bulletins.students', $classe->id_classe));
    const PARAMS = {
        id_annee:     @json($id_annee),
        id_trimestre: @json($id_trimestre),
        mois:         @json($mois),
        ids:          @json($ids),
    };

    // Elements
    const elLabel       = document.getElementById('kn-label');
    const elFill        = document.getElementById('kn-fill');
    const elCount       = document.getElementById('kn-count');
    const elPct         = document.getElementById('kn-pct');
    const elPendingList = document.getElementById('kn-pending-list');
    const elDoneList    = document.getElementById('kn-done-list');
    const elDoneEmpty   = document.getElementById('kn-done-empty');
    const elBadgePend   = document.getElementById('kn-badge-pending');
    const elBadgeDone   = document.getElementById('kn-badge-done');
    const elStatus      = document.getElementById('kn-status');
    const elBtnMerge    = document.getElementById('kn-btn-merge');
    const elBtnZip      = document.getElementById('kn-btn-zip');
    const elIframe      = document.getElementById('kn-preview-iframe');
    const elPh          = document.getElementById('kn-placeholder');
    const elOverlay     = document.getElementById('kn-overlay');
    const elOverlayTitle= document.getElementById('kn-overlay-title');
    const elOverlayMsg  = document.getElementById('kn-overlay-msg');
    const elOverlayBar  = document.getElementById('kn-overlay-bar');

    let doneCount  = 0;
    let total      = 0;
    let remaining  = 0;
    const blobs    = [];   // {name, blob} pour le ZIP

    // ── Progress ──────────────────────────────────────────────
    function setProgress(done, tot, labelText) {
        const pct = tot ? Math.round((done / tot) * 100) : 0;
        elFill.style.width      = pct + '%';
        elCount.textContent     = done + ' / ' + tot + ' bulletin' + (tot > 1 ? 's' : '');
        elPct.textContent       = pct + '%';
        if (labelText) elLabel.textContent = labelText;
    }

    // ── Sanitize filename ──────────────────────────────────────
    function safeName(str) {
        return str.normalize('NFD').replace(/[̀-ͯ]/g, '')
                  .replace(/[^a-zA-Z0-9_\- ]/g, '').trim();
    }

    // ── Pending list ───────────────────────────────────────────
    function buildPendingRow(student) {
        const item = document.createElement('div');
        item.className = 'kn-pending-item';
        item.id = 'pend-' + student.id;

        const dot = document.createElement('div');
        dot.className = 'kn-status-dot waiting';
        dot.textContent = '○';

        const name = document.createElement('span');
        name.className = 'kn-pname';
        name.textContent = student.prenom + ' ' + student.nom;

        item.appendChild(dot);
        item.appendChild(name);
        return item;
    }

    function setPendingStatus(id, status) {
        const row = document.getElementById('pend-' + id);
        if (!row) return;
        const dot  = row.querySelector('.kn-status-dot');
        const name = row.querySelector('.kn-pname');
        row.classList.remove('active');
        dot.className = 'kn-status-dot ' + status;
        if (status === 'waiting')  { dot.textContent = '○'; }
        if (status === 'spinning') { dot.textContent = '⟳'; row.classList.add('active'); }
        if (status === 'done')  {
            dot.textContent = '✓';
            name.classList.add('done');
            setTimeout(() => removePendingRow(id), 480);
        }
        if (status === 'error') {
            dot.textContent = '✗';
            name.classList.add('error');
            setTimeout(() => removePendingRow(id), 750);
        }
    }

    function removePendingRow(id) {
        const row = document.getElementById('pend-' + id);
        if (!row) return;
        row.classList.add('removing');
        row.addEventListener('animationend', () => {
            row.remove();
            remaining--;
            elBadgePend.textContent = remaining;
            if (remaining === 0 && document.body.classList.contains('done')) {
                elPendingList.innerHTML = '<div class="kn-empty success">✅ Tout généré !</div>';
            }
        }, { once: true });
    }

    // ── Done list (with download + view links) ─────────────────
    function addDoneItem(student, blobUrl, hasError) {
        elDoneEmpty && (elDoneEmpty.style.display = 'none');

        const item = document.createElement('div');
        item.className = 'kn-done-item';

        if (hasError) {
            item.innerHTML = `
                <span style="font-size:14px;flex-shrink:0;">✗</span>
                <span class="kn-done-name" style="color:var(--red);">${escHtml(student.prenom + ' ' + student.nom)}</span>`;
        } else {
            const fname = safeName(student.prenom + '_' + student.nom) + '.pdf';

            const viewBtn = document.createElement('button');
            viewBtn.className = 'kn-view-link';
            viewBtn.title = 'Aperçu';
            viewBtn.textContent = '👁';
            viewBtn.addEventListener('click', () => showPreview(blobUrl));

            const dlLink = document.createElement('a');
            dlLink.className = 'kn-dl-link';
            dlLink.href = blobUrl;
            dlLink.download = fname;
            dlLink.title = 'Télécharger';
            dlLink.textContent = '⬇';

            const nameSpan = document.createElement('span');
            nameSpan.className = 'kn-done-name';
            nameSpan.textContent = student.prenom + ' ' + student.nom;

            item.appendChild(viewBtn);
            item.appendChild(nameSpan);
            item.appendChild(dlLink);
        }

        // Insérer en haut pour voir les derniers générés en premier
        elDoneList.insertBefore(item, elDoneList.firstChild);
        doneCount++;
        elBadgeDone.textContent = doneCount;
        elBtnMerge.disabled = false;
        elBtnZip.disabled   = false;
    }

    // ── Preview ────────────────────────────────────────────────
    function showPreview(blobUrl) {
        elPh.style.display     = 'none';
        elIframe.style.display = 'block';
        elIframe.src           = blobUrl;
    }

    // ── Fetch blob ─────────────────────────────────────────────
    async function fetchBlob(url) {
        const res = await fetch(url, { credentials: 'same-origin' });
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return await res.blob();
    }

    // ── Overlay helpers ────────────────────────────────────────
    function showOverlay(title, msg, pct) {
        elOverlayTitle.textContent = title;
        elOverlayMsg.textContent   = msg;
        elOverlayBar.style.width   = (pct || 0) + '%';
        elOverlay.classList.add('show');
    }
    function hideOverlay() { elOverlay.classList.remove('show'); }
    function setOverlayPct(pct) { elOverlayBar.style.width = pct + '%'; }

    // ── Fusion PDF client-side (pdf-lib) ───────────────────────
    async function mergePDF() {
        if (!blobs.length) return;
        const fname = safeName(CLASS_NAME) + '_Bulletins.pdf';
        showOverlay('Fusion des bulletins en cours...', fname, 0);
        try {
            const { PDFDocument } = PDFLib;
            const merged = await PDFDocument.create();
            for (let i = 0; i < blobs.length; i++) {
                setOverlayPct(Math.round((i / blobs.length) * 90));
                const ab  = await blobs[i].blob.arrayBuffer();
                const src = await PDFDocument.load(ab, { ignoreEncryption: true });
                const pages = await merged.copyPages(src, src.getPageIndices());
                pages.forEach(p => merged.addPage(p));
            }
            setOverlayPct(95);
            const bytes   = await merged.save();
            const pdfBlob = new Blob([bytes], { type: 'application/pdf' });
            setOverlayPct(100);
            triggerDownload(URL.createObjectURL(pdfBlob), fname);
        } catch (e) {
            alert('Erreur lors de la fusion PDF : ' + e.message);
        } finally {
            hideOverlay();
        }
    }

    // ── ZIP download ───────────────────────────────────────────
    async function downloadZip() {
        if (!blobs.length) return;
        const fname = safeName(CLASS_NAME) + '_Bulletins.zip';
        showOverlay('Création du dossier ZIP...', fname, 0);
        try {
            const zip    = new JSZip();
            const folder = zip.folder(safeName(CLASS_NAME) + '_Bulletins');
            blobs.forEach(({ name, blob }) => folder.file(name, blob));
            const zipBlob = await zip.generateAsync(
                { type: 'blob', compression: 'DEFLATE', compressionOptions: { level: 6 } },
                meta => setOverlayPct(Math.round(meta.percent))
            );
            triggerDownload(URL.createObjectURL(zipBlob), fname);
        } catch (e) {
            alert('Erreur lors de la création du ZIP : ' + e.message);
        } finally {
            hideOverlay();
        }
    }

    function triggerDownload(url, filename) {
        const a = document.createElement('a');
        a.href = url; a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }

    function escHtml(v) {
        return String(v).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c]));
    }

    // ── Main generation loop ───────────────────────────────────
    async function run() {
        const qp = new URLSearchParams();
        if (PARAMS.id_annee)                   qp.set('id_annee',     PARAMS.id_annee);
        if (PARAMS.id_trimestre)               qp.set('id_trimestre', PARAMS.id_trimestre);
        if (PARAMS.mois)                       qp.set('mois',         PARAMS.mois);
        if (PARAMS.ids && PARAMS.ids !== '[]') qp.set('ids',          PARAMS.ids);

        let students;
        try {
            const res = await fetch(STUDENTS_URL + '?' + qp.toString(), { credentials: 'same-origin' });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            students = await res.json();
        } catch (e) {
            elLabel.textContent = '❌ Impossible de charger la liste des élèves.';
            elStatus.textContent = '❌ Erreur de chargement';
            elStatus.className = 'kn-footer-status error';
            elPendingList.innerHTML = '<div class="kn-empty" style="color:var(--red)">Erreur réseau</div>';
            return;
        }

        if (!students.length) {
            elLabel.textContent = 'Aucun élève trouvé pour cette sélection.';
            elStatus.textContent = '⚠ Aucun bulletin disponible';
            elStatus.className = 'kn-footer-status error';
            elPendingList.innerHTML = '<div class="kn-empty">Aucun élève</div>';
            return;
        }

        total     = students.length;
        remaining = students.length;
        elBadgePend.textContent = remaining;
        setProgress(0, total, 'Préparation de ' + total + ' bulletin' + (total > 1 ? 's' : '') + '...');

        // Construire la liste d'attente
        elPendingList.innerHTML = '';
        students.forEach(s => elPendingList.appendChild(buildPendingRow(s)));

        // Générer un par un
        for (let i = 0; i < students.length; i++) {
            const s = students[i];
            setPendingStatus(s.id, 'spinning');
            setProgress(doneCount, total, 'Génération : ' + s.prenom + ' ' + s.nom + '...');

            try {
                const blob    = await fetchBlob(s.url);
                const blobUrl = URL.createObjectURL(blob);

                // Stocker pour ZIP
                blobs.push({ name: safeName(s.prenom + '_' + s.nom) + '.pdf', blob });

                setPendingStatus(s.id, 'done');
                addDoneItem(s, blobUrl, false);

                // Auto-aperçu du dernier bulletin généré
                showPreview(blobUrl);
            } catch (err) {
                setPendingStatus(s.id, 'error');
                addDoneItem(s, null, true);
            }

            setProgress(doneCount, total,
                i + 1 < students.length
                    ? 'Génération : ' + students[i + 1].prenom + ' ' + students[i + 1].nom + '...'
                    : 'Finalisation...'
            );
        }

        // Terminé
        setProgress(doneCount, total);
        if (blobs.length > 0) {
            elBtnMerge.disabled = false;
            elBtnZip.disabled   = false;
        }

        setTimeout(() => {
            document.body.classList.add('done');
            const errors = total - doneCount;
            if (errors === 0) {
                elLabel.textContent  = '✅ ' + doneCount + ' bulletin' + (doneCount > 1 ? 's générés' : ' généré') + ' avec succès !';
                elStatus.textContent = '✅ ' + doneCount + ' bulletin' + (doneCount > 1 ? 's générés' : ' généré');
                elStatus.className   = 'kn-footer-status done';
            } else {
                elLabel.textContent  = '⚠ ' + doneCount + '/' + total + ' générés – ' + errors + ' erreur(s)';
                elStatus.textContent = '⚠ ' + errors + ' erreur(s)';
                elStatus.className   = 'kn-footer-status error';
            }
            if (remaining === 0) {
                elPendingList.innerHTML = '<div class="kn-empty success">✅ Tout généré !</div>';
            }
        }, 850);
    }

    elBtnMerge.addEventListener('click', mergePDF);
    elBtnZip.addEventListener('click', downloadZip);
    run();
})();
</script>
</body>
</html>
