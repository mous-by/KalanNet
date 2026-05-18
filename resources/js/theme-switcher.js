/**
 * KalanNet — Theme Switcher
 * Gère les 7 thèmes via data-theme sur <html>
 * Persist : localStorage (tous) + AJAX (utilisateurs connectés)
 */
(function () {
    'use strict';

    const THEMES = [
        { key: 'bleu-sombre', label: '🔵 Bleu Sombre',     swatch: 'swatch-bleu-sombre' },
        { key: 'light',       label: '⚪ Light (Clair)',    swatch: 'swatch-light'       },
        { key: 'dark',        label: '⚫ Dark (Sombre)',    swatch: 'swatch-dark'        },
        { key: 'vert',        label: '🟢 Vert',             swatch: 'swatch-vert'        },
        { key: 'violet',      label: '🟣 Violet',           swatch: 'swatch-violet'      },
        { key: 'rouge',       label: '🔴 Rouge / Bordeaux', swatch: 'swatch-rouge'       },
        { key: 'orange',      label: '🟠 Orange',           swatch: 'swatch-orange'      },
    ];

    const LS_KEY     = 'kalannet_theme';
    const DEFAULT    = 'bleu-sombre';
    const SAVE_URL   = window.KALANNET_THEME_SAVE_URL || null; // Défini dans le layout Blade
    const CSRF_TOKEN = window.KALANNET_CSRF_TOKEN     || null;

    // ---- Applique le thème immédiatement ----
    function applyTheme(themeKey) {
        const valid = THEMES.find(t => t.key === themeKey);
        const key   = valid ? themeKey : DEFAULT;

        if (key === DEFAULT) {
            document.documentElement.removeAttribute('data-theme');
        } else {
            document.documentElement.setAttribute('data-theme', key);
        }

        localStorage.setItem(LS_KEY, key);
        updateDropdownUI(key);
    }

    // ---- Met à jour l'affichage du dropdown ----
    function updateDropdownUI(activeKey) {
        document.querySelectorAll('.theme-option').forEach(el => {
            const k = el.getAttribute('data-theme-key');
            el.classList.toggle('active-theme', k === activeKey);
            const check = el.querySelector('.theme-check');
            if (check) check.style.display = (k === activeKey) ? 'inline' : 'none';
        });

        // Mettre à jour le libellé du bouton
        const activeTheme = THEMES.find(t => t.key === activeKey);
        const btnLabel = document.getElementById('current-theme-label');
        if (btnLabel && activeTheme) {
            btnLabel.textContent = activeTheme.label;
        }
    }

    // ---- Sauvegarde en BDD via AJAX (si connecté) ----
    async function saveThemeToServer(themeKey) {
        if (!SAVE_URL || !CSRF_TOKEN) return;
        try {
            await fetch(SAVE_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ theme: themeKey }),
            });
        } catch (e) {
            // Silencieux — la préférence locale est déjà sauvegardée
            console.warn('[KalanNet Theme] Impossible de sauvegarder en base:', e.message);
        }
    }

    // ---- Initialisation ----
    function init() {
        // 1. Lire le thème sauvegardé (data-attr du serveur > localStorage > défaut)
        const serverTheme = document.documentElement.getAttribute('data-initial-theme');
        const stored = serverTheme || localStorage.getItem(LS_KEY) || DEFAULT;
        applyTheme(stored);

        // 2. Construire le menu dropdown de thèmes (s'il n'est pas déjà rendu par Blade)
        buildThemeMenu();

        // 3. Écouter les clics
        document.addEventListener('click', function (e) {
            const option = e.target.closest('.theme-option');
            if (!option) return;
            const themeKey = option.getAttribute('data-theme-key');
            if (!themeKey) return;
            applyTheme(themeKey);
            saveThemeToServer(themeKey);

            // Fermer le dropdown Bootstrap
            const dropdown = option.closest('.dropdown-menu');
            if (dropdown) {
                const toggle = document.querySelector('[data-bs-toggle="dropdown"][aria-controls="' + dropdown.id + '"], [data-bs-target="#' + dropdown.id + '"]');
                if (toggle && typeof bootstrap !== 'undefined') {
                    const bsDropdown = bootstrap.Dropdown.getOrCreateInstance(toggle);
                    bsDropdown.hide();
                }
            }
        });
    }

    // ---- Construit le menu (injecté dans #theme-dropdown-menu s'il existe) ----
    function buildThemeMenu() {
        const container = document.getElementById('theme-dropdown-menu');
        if (!container) return; // Géré dans le Blade
        if (container.children.length > 0) return; // Déjà rendu

        const currentTheme = localStorage.getItem(LS_KEY) || DEFAULT;
        THEMES.forEach(theme => {
            const li = document.createElement('li');
            const a  = document.createElement('a');
            a.className = 'dropdown-item theme-option' + (theme.key === currentTheme ? ' active-theme' : '');
            a.setAttribute('data-theme-key', theme.key);
            a.href = '#';
            a.innerHTML = `
                <span class="theme-swatch ${theme.swatch} me-2"></span>
                ${theme.label}
                <span class="theme-check ms-auto" style="display:${theme.key === currentTheme ? 'inline' : 'none'}">✓</span>
            `;
            li.appendChild(a);
            container.appendChild(li);
        });
    }

    // ---- Appliquer le thème AVANT le rendu (évite le flash) ----
    const savedTheme = document.documentElement.getAttribute('data-initial-theme')
                    || localStorage.getItem(LS_KEY)
                    || DEFAULT;
    if (savedTheme && savedTheme !== DEFAULT) {
        document.documentElement.setAttribute('data-theme', savedTheme);
    }

    // ---- Lancer à DOMContentLoaded ----
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    // Exposer pour usage externe
    window.KalanNetTheme = { apply: applyTheme, themes: THEMES };

})();
