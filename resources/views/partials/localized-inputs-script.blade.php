<script>
    document.addEventListener('DOMContentLoaded', function () {
        const locale = document.documentElement.lang || 'fr';
        const isArabic = locale === 'ar';

        function isTextInput(field) {
            if (!field) return false;
            if (field.tagName === 'TEXTAREA') return true;
            if (field.tagName !== 'INPUT') return false;
            return ['text', 'search', 'email', 'tel', 'url', ''].includes((field.type || '').toLowerCase());
        }

        function isTechnicalField(field) {
            const name = (field.name || field.id || '').toLowerCase();
            return ['email', 'telephone', 'phone', 'tel', 'identifier', 'password', 'pwd', 'matricule', 'code'].some(key => name.includes(key));
        }

        function applyFieldDirection(field) {
            if (!isTextInput(field)) return;
            const technical = isTechnicalField(field);
            field.lang = technical ? 'fr' : locale;
            field.dir = isArabic && !technical ? 'rtl' : 'ltr';
            field.classList.toggle('arabic-input-ready', isArabic && !technical);
        }

        function applyAll(root = document) {
            root.querySelectorAll('input, textarea').forEach(applyFieldDirection);
        }

        applyAll();
        document.addEventListener('focusin', event => applyFieldDirection(event.target));
        new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType !== Node.ELEMENT_NODE) return;
                    if (node.matches && node.matches('input, textarea')) {
                        applyFieldDirection(node);
                    }
                    if (node.querySelectorAll) {
                        applyAll(node);
                    }
                });
            });
        }).observe(document.body, { childList: true, subtree: true });
    });
</script>