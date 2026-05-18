/**
 * Convertit la valeur d'un input en majuscules en utilisant le locale FR
 * et tente de préserver la position du curseur/selection.
 * Utiliser en oninput: oninput="toUppercaseWithLocale(this)"
 */
function toUppercaseWithLocale(el) {
    if (!el) return;
    // récupérer positions de sélection/caret
    var start = el.selectionStart;
    var end = el.selectionEnd;
    // utiliser toLocaleUpperCase pour respecter les accents (fr-FR)
    var newVal = el.value.toLocaleUpperCase('fr-FR');
    if (newVal !== el.value) {
        el.value = newVal;
        try {
            // restaurer la sélection si possible
            if (typeof start === 'number' && typeof end === 'number') {
                el.setSelectionRange(start, end);
            }
        } catch (e) {
            // certains éléments ou navigateurs peuvent lever une exception
        }
    }
}

// Initialiser automatiquement les inputs marqués avec data-uppercase="fr"
document.addEventListener('DOMContentLoaded', function() {
    var els = document.querySelectorAll('input[data-uppercase="fr"], textarea[data-uppercase="fr"]');
    els.forEach(function(el) {
        el.addEventListener('input', function() { toUppercaseWithLocale(el); });
    });
});
