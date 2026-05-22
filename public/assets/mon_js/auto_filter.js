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
            : 'select, input[type="date"], input[type="month"]';
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
            form.requestSubmit ? form.requestSubmit() : form.submit();
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
            if (!field.matches('select, input[type="date"], input[type="month"]')) {
                return;
            }

            field.addEventListener('change', submitForm);
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
