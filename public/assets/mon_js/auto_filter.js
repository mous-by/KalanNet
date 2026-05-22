function initAutoFilters() {
    const forms = document.querySelectorAll('form[data-auto-filter="true"]');
    const escapeSelector = window.CSS && CSS.escape
        ? CSS.escape
        : function (value) {
            return value.replace(/"/g, '\\"');
        };

    forms.forEach(function (form) {
        if (form.dataset.autoFilterReady === 'true') {
            return;
        }

        form.dataset.autoFilterReady = 'true';
        let timer = null;
        const fieldNames = (form.dataset.autoFilterFields || '')
            .split(',')
            .map(function (name) {
                return name.trim();
            })
            .filter(Boolean);
        const fieldSelector = fieldNames.length
            ? fieldNames.map(function (name) {
                return '[name="' + escapeSelector(name) + '"]';
            }).join(',')
            : 'select, input[type="date"], input[type="month"], input[type="number"]';
        const searchSelector = fieldNames.length
            ? fieldNames.map(function (name) {
                return '[name="' + escapeSelector(name) + '"]';
            }).join(',')
            : 'input[type="search"], input[name="search"], input[data-auto-filter-search="true"]';

        function submitForm() {
            if (form.dataset.submitting === 'true') {
                return;
            }

            if (!form.checkValidity()) {
                return;
            }

            form.dataset.submitting = 'true';
            showFilterSpinner();
            form.requestSubmit ? form.requestSubmit() : form.submit();
        }

        function showFilterSpinner() {
            const submitButtons = form.querySelectorAll('button[type="submit"], button:not([type])');
            submitButtons.forEach(function (button) {
                button.disabled = true;
                button.dataset.originalHtml = button.innerHTML;
                button.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>' + (button.textContent.trim() || '');
            });

            if (form.querySelector('.auto-filter-loading')) {
                return;
            }

            const indicator = document.createElement('span');
            indicator.className = 'auto-filter-loading d-inline-flex align-items-center gap-2 small text-muted ms-2';
            indicator.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span><span>Chargement...</span>';
            form.appendChild(indicator);
        }

        form.addEventListener('submit', function () {
            if (form.method.toLowerCase() !== 'get') {
                return;
            }

            form.querySelectorAll('input, select, textarea').forEach(function (field) {
                if (!field.required && field.value === '') {
                    field.disabled = true;
                }
            });
        });

        form.querySelectorAll(fieldSelector).forEach(function (field) {
            if (!field.matches('select, input[type="date"], input[type="month"], input[type="number"]')) {
                return;
            }

            field.addEventListener(field.matches('input[type="number"]') ? 'input' : 'change', function () {
                clearTimeout(timer);
                timer = setTimeout(submitForm, field.matches('input[type="number"]') ? 550 : 0);
            });
        });

        form.querySelectorAll(searchSelector).forEach(function (field) {
            if (!field.matches('input[type="search"], input[name="search"], input[data-auto-filter-search="true"]')) {
                return;
            }

            field.addEventListener('input', function () {
                clearTimeout(timer);
                timer = setTimeout(submitForm, 550);
            });
        });
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAutoFilters);
} else {
    initAutoFilters();
}
