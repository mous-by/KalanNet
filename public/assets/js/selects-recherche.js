/**
 * Listes déroulantes avec champ de recherche (Select2), partout dans l'application.
 *
 * - Toute <select> de la page (et celles ajoutées ensuite par JavaScript) reçoit
 *   un champ de recherche. Exclues : listes multiples affichées en bloc (size > 1),
 *   listes des tableaux DataTables, et celles marquées data-sans-recherche.
 * - Le choix d'une valeur envoie un vrai événement « change » : les pages qui
 *   écoutent la liste (addEventListener, onchange, jQuery) réagissent comme avant.
 * - Une valeur ou des options modifiées par JavaScript sont aussitôt affichées.
 */
(function ($) {
    if (!$ || !$.fn || !$.fn.select2) {
        return;
    }

    var EXCLUSIONS = '[data-sans-recherche], .swal2-popup select, [size]:not([size="0"]):not([size="1"]), .dataTables_length select, .dt-length select, .select2-hidden-accessible';

    function estEnrichie(el) {
        return el && el.tagName === 'SELECT' && el.classList.contains('select2-hidden-accessible');
    }

    function rafraichir(el) {
        // Met à jour l'affichage sans relancer les écouteurs de la page.
        if (estEnrichie(el)) {
            $(el).trigger('change.select2');
        }
    }

    // Select2 signale un choix par un « change » jQuery, que addEventListener
    // n'entend pas : on le remplace par un vrai événement du navigateur, qui
    // atteint aussi les écouteurs jQuery et les attributs onchange.
    var triggerOrigine = $.fn.trigger;
    $.fn.trigger = function (evenement, donnees) {
        if (evenement === 'change' && donnees === undefined) {
            var natifs = this.filter(function () { return estEnrichie(this); });
            if (natifs.length) {
                natifs.each(function () {
                    this.dispatchEvent(new Event('change', { bubbles: true }));
                });
                var autres = this.not(natifs);
                return autres.length ? triggerOrigine.call(autres, evenement, donnees) && this : this;
            }
        }
        return triggerOrigine.apply(this, arguments);
    };

    // Valeur choisie par JavaScript (select.value = …, selectedIndex, option.selected) : affichage à jour.
    function surveiller(proto, prop) {
        var desc = Object.getOwnPropertyDescriptor(proto, prop);
        if (!desc || !desc.set) {
            return;
        }
        Object.defineProperty(proto, prop, {
            configurable: true,
            enumerable: desc.enumerable,
            get: desc.get,
            set: function (v) {
                desc.set.call(this, v);
                var select = this.tagName === 'OPTION' ? this.closest('select') : this;
                if (estEnrichie(select)) {
                    rafraichir(select);
                }
            }
        });
    }
    surveiller(HTMLSelectElement.prototype, 'value');
    surveiller(HTMLSelectElement.prototype, 'selectedIndex');
    surveiller(HTMLOptionElement.prototype, 'selected');

    function texteVide(select) {
        var premiere = select.options.length ? select.options[0] : null;
        return premiere && premiere.value === '' ? premiere.textContent.trim() : '';
    }

    function enrichir(select) {
        if (!select || select.tagName !== 'SELECT' || select.multiple && select.size > 1 || $(select).is(EXCLUSIONS)) {
            return;
        }
        var modal = select.closest('.modal');
        var largeur = select.style.width || (select.classList.contains('form-select') || select.classList.contains('form-control') ? '100%' : 'resolve');
        var options = {
            theme: 'bootstrap4',
            width: largeur,
            dropdownParent: modal ? $(modal) : $(document.body),
            minimumResultsForSearch: 0,
            dropdownAutoWidth: true,
            language: {
                noResults: function () { return 'Aucun résultat'; },
                searching: function () { return 'Recherche…'; }
            }
        };
        var vide = texteVide(select);
        if (vide) {
            options.placeholder = vide;
            options.allowClear = !select.required && !select.multiple;
        }
        try {
            $(select).select2(options);
        } catch (e) {
            return;
        }
        // Garde l'apparence de la liste d'origine (taille, bordure du thème).
        var conteneur = $(select).next('.select2-container');
        if (select.classList.contains('form-select-sm')) {
            conteneur.addClass('select2-sm');
        }
        var style = select.getAttribute('style');
        if (style && /border|font-size/.test(style)) {
            conteneur.find('.select2-selection').attr('style', style.replace(/width\s*:[^;]+;?/g, ''));
        }

        // Options remplacées par JavaScript : affichage à jour.
        new MutationObserver(function () { rafraichir(select); })
            .observe(select, { childList: true, subtree: true });
    }

    function enrichirDans(racine) {
        if (racine.tagName === 'SELECT') {
            enrichir(racine);
            return;
        }
        if (racine.querySelectorAll) {
            racine.querySelectorAll('select').forEach(enrichir);
        }
    }

    // La recherche reçoit le curseur dès l'ouverture de la liste.
    $(document).on('select2:open', function () {
        setTimeout(function () {
            var champ = document.querySelector('.select2-container--open .select2-search__field');
            if (champ) {
                champ.focus();
            }
        }, 0);
    });

    function demarrer() {
        enrichirDans(document.body);
        new MutationObserver(function (mutations) {
            mutations.forEach(function (m) {
                m.addedNodes.forEach(function (n) {
                    if (n.nodeType === 1 && !n.closest('.select2-container')) {
                        enrichirDans(n);
                    }
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', demarrer);
    } else {
        demarrer();
    }
})(window.jQuery);
