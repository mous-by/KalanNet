@if(session('show_pwa_install_modal'))
@php session()->forget('show_pwa_install_modal'); @endphp

<!-- PWA Install Modal -->
<div class="modal fade" id="pwaInstallModal" tabindex="-1" aria-labelledby="pwaInstallModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 430px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 18px; overflow: hidden;">

            <!-- Header -->
            <div class="d-flex align-items-center gap-3 px-4 pt-4 pb-3" style="background: linear-gradient(135deg, var(--theme-accent-dark, #0a3d20), var(--theme-accent, #146c43));">
                <div class="rounded-3 overflow-hidden shadow-sm flex-shrink-0" style="width:54px;height:54px;background:#fff;">
                    <img src="{{ asset('assets/images/icons/icon-192x192.png') }}" alt="KalanNet" style="width:100%;height:100%;object-fit:cover;">
                </div>
                <div class="text-white flex-grow-1">
                    <h5 class="mb-0 fw-bold" id="pwaInstallModalLabel" style="font-size:1.05rem;">Installer KalanNet</h5>
                    <small style="opacity:.75;font-size:.8rem;">Application de gestion scolaire</small>
                </div>
                <button type="button" id="pwaInstallClose" style="background:none;border:none;color:rgba(255,255,255,.7);font-size:1.3rem;line-height:1;padding:0 0 4px 4px;" aria-label="Fermer">&times;</button>
            </div>

            <!-- Body -->
            <div class="modal-body px-4 py-3">
                <p class="mb-3" style="font-size:.88rem;color:#555;">
                    Installez l'application sur votre appareil pour un accès rapide depuis le bureau :
                </p>

                <!-- Options d'installation -->
                <div class="mb-3" style="font-size:.86rem;">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="pwaOptTaskbar" checked>
                        <label class="form-check-label" for="pwaOptTaskbar">
                            <i class="bi bi-layout-sidebar-reverse text-primary me-1"></i>
                            Épingler à la barre des tâches
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="pwaOptDesktop" checked>
                        <label class="form-check-label" for="pwaOptDesktop">
                            <i class="bi bi-display text-success me-1"></i>
                            Créer un raccourci sur le bureau
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="pwaOptStartup">
                        <label class="form-check-label" for="pwaOptStartup">
                            <i class="bi bi-power text-warning me-1"></i>
                            Se démarrer automatiquement à la connexion
                        </label>
                    </div>
                </div>

                <!-- Message post-installation Ubuntu -->
                <div id="pwaPostInstallMsg" class="alert alert-success py-2 px-3 mb-0 d-none" style="font-size:.82rem;border-radius:10px;">
                    <strong><i class="bi bi-check-circle me-1"></i>Installé !</strong>
                    <span id="pwaPostInstallText"></span>
                </div>

                <!-- Message navigateur non supporté -->
                <div id="pwaNotSupportedMsg" class="alert alert-warning py-2 px-3 mb-0 d-none" style="font-size:.82rem;border-radius:10px;">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Sur Ubuntu avec Chrome : cliquez l'icône <strong>⊕</strong> dans la barre d'adresse, puis <strong>"Installer KalanNet"</strong>.
                </div>
            </div>

            <!-- Footer -->
            <div class="px-4 pb-4 pt-1 d-flex gap-2">
                <button type="button" class="btn btn-outline-secondary flex-fill" id="pwaInstallLater" style="border-radius:10px;font-size:.9rem;">
                    Plus tard
                </button>
                <button type="button" class="btn btn-success flex-fill fw-semibold" id="pwaInstallBtn" style="border-radius:10px;font-size:.9rem;">
                    <i class="bi bi-download me-1"></i> Installer
                </button>
            </div>

        </div>
    </div>
</div>

<script>
(function () {
    const STORAGE_KEY  = 'kalannet_pwa_dismissed_until';
    const INSTALLED_KEY = 'kalannet_pwa_installed';

    // Déjà en mode standalone (déjà installé)
    if (window.matchMedia('(display-mode: standalone)').matches || navigator.standalone === true) return;

    // Déjà installé via notre bouton
    if (localStorage.getItem(INSTALLED_KEY) === '1') return;

    // Repoussé récemment
    const dismissedUntil = parseInt(localStorage.getItem(STORAGE_KEY) || '0', 10);
    if (dismissedUntil && Date.now() < dismissedUntil) return;

    let deferredPrompt = null;

    window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
    });

    // Afficher le modal ~1,2 s après chargement
    window.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            const modalEl = document.getElementById('pwaInstallModal');
            if (!modalEl) return;
            const modal = new bootstrap.Modal(modalEl);
            modal.show();
        }, 1200);
    });

    // ---- Helpers UI ----
    function showPostInstall(msg) {
        document.getElementById('pwaPostInstallText').textContent = ' ' + msg;
        document.getElementById('pwaPostInstallMsg').classList.remove('d-none');
        document.getElementById('pwaInstallBtn').classList.add('d-none');
        document.getElementById('pwaInstallLater').textContent = 'Fermer';
    }

    function desktopInstructions() {
        // Instructions après install Chrome/Ubuntu
        const wantsDesktop = document.getElementById('pwaOptDesktop')?.checked;
        const wantsTaskbar = document.getElementById('pwaOptTaskbar')?.checked;
        const wantsStartup = document.getElementById('pwaOptStartup')?.checked;

        let parts = [];
        if (wantsDesktop || wantsTaskbar) {
            parts.push('Retrouvez KalanNet dans le menu Applications, puis faites glisser sur le bureau ou épinglez au dock.');
        }
        if (wantsStartup) {
            parts.push('Pour le démarrage auto : Paramètres système → Applications de démarrage → Ajouter KalanNet.');
        }
        return parts.join(' ') || 'Retrouvez KalanNet dans le menu Applications.';
    }

    // ---- Bouton Installer ----
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#pwaInstallBtn')) return;

        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then(function (choice) {
                deferredPrompt = null;
                if (choice.outcome === 'accepted') {
                    localStorage.setItem(INSTALLED_KEY, '1');
                    showPostInstall(desktopInstructions());
                } else {
                    // Refusé dans le prompt natif → reporter
                    localStorage.setItem(STORAGE_KEY, String(Date.now() + 7 * 24 * 60 * 60 * 1000));
                    bootstrap.Modal.getInstance(document.getElementById('pwaInstallModal'))?.hide();
                }
            });
        } else {
            // Pas de prompt disponible : guide manuel Ubuntu
            document.getElementById('pwaNotSupportedMsg').classList.remove('d-none');
            document.getElementById('pwaInstallBtn').classList.add('d-none');
        }
    });

    // ---- Bouton Plus tard / Fermer ----
    document.addEventListener('click', function (e) {
        if (!e.target.closest('#pwaInstallLater') && !e.target.closest('#pwaInstallClose')) return;
        localStorage.setItem(STORAGE_KEY, String(Date.now() + 7 * 24 * 60 * 60 * 1000));
        bootstrap.Modal.getInstance(document.getElementById('pwaInstallModal'))?.hide();
    });
})();
</script>

@endif
