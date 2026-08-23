/**
 * Ajoute automatiquement un spinner sur le bouton "submit" de tout formulaire
 * envoyé nativement (connexion, enregistrement, etc.), sans avoir besoin
 * de modifier chaque vue individuellement.
 *
 * - Ignoré si le formulaire est intercepté par du JS (event.preventDefault()),
 *   pour ne jamais entrer en conflit avec un flux AJAX qui gère déjà son propre état.
 * - Le désactivage du bouton est différé (setTimeout 0) pour que sa paire
 *   name/value soit bien incluse dans les données du formulaire envoyé.
 * - Un filet de sécurité réactive le bouton après quelques secondes, pour les
 *   formulaires qui déclenchent un téléchargement (export Excel/PDF, etc.)
 *   et ne provoquent donc pas de navigation.
 *
 * Pour exclure un formulaire ou un bouton précis : ajouter data-no-spinner.
 */
(function () {
    'use strict';

    var SAFETY_TIMEOUT_MS = 10000;

    function resetButton(submitter) {
        if (!submitter || submitter.dataset.spinnerApplied !== '1') {
            return;
        }
        var spinner = submitter.querySelector('[data-spinner-element]');
        if (spinner) {
            spinner.remove();
        }
        submitter.disabled = submitter.dataset.spinnerOriginalDisabled === '1';
        submitter.classList.remove('disabled');
        delete submitter.dataset.spinnerApplied;
        delete submitter.dataset.spinnerOriginalDisabled;
    }

    function showSpinner(submitter) {
        if (!submitter || submitter.dataset.spinnerApplied === '1') {
            return;
        }
        submitter.dataset.spinnerApplied = '1';
        submitter.dataset.spinnerOriginalDisabled = submitter.disabled ? '1' : '0';

        var spinner = document.createElement('span');
        spinner.className = 'spinner-border spinner-border-sm me-2';
        spinner.setAttribute('role', 'status');
        spinner.setAttribute('aria-hidden', 'true');
        spinner.setAttribute('data-spinner-element', '1');
        submitter.prepend(spinner);

        window.setTimeout(function () {
            submitter.disabled = true;
            submitter.classList.add('disabled');
        }, 0);

        window.setTimeout(function () {
            resetButton(submitter);
        }, SAFETY_TIMEOUT_MS);
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-spinner')) {
            return;
        }
        // Un gestionnaire attaché au formulaire a déjà annulé la soumission
        // (flux AJAX) : on ne touche à rien, il gère son propre état.
        if (event.defaultPrevented) {
            return;
        }

        var submitter = event.submitter || form.querySelector('button[type="submit"]');
        if (!submitter || submitter.hasAttribute('data-no-spinner')) {
            return;
        }

        showSpinner(submitter);
    });

    // Réinitialise les boutons restés bloqués si la page est restaurée
    // depuis le cache de navigation (bouton "précédent" du navigateur).
    window.addEventListener('pageshow', function (event) {
        if (!event.persisted) {
            return;
        }
        document.querySelectorAll('[data-spinner-applied="1"]').forEach(resetButton);
    });
})();
