@php
    $uiTranslations = trans('ui');
    if (!is_array($uiTranslations)) {
        $uiTranslations = [];
    }
@endphp

@if(count($uiTranslations) > 0)
<script>
    window.KalanNetUiTranslations = @json($uiTranslations, JSON_UNESCAPED_UNICODE);

    document.addEventListener('DOMContentLoaded', function () {
        const dictionary = window.KalanNetUiTranslations || {};
        const locale = document.documentElement.lang || 'fr';
        const isArabic = locale === 'ar';
        const ignoredTags = new Set(['SCRIPT', 'STYLE', 'NOSCRIPT', 'TEXTAREA']);
        const translatableAttributes = [
            'placeholder',
            'title',
            'aria-label',
            'data-confirm-title',
            'data-confirm-text',
            'data-proof-title'
        ];

        function normalize(value) {
            return String(value || '').replace(/\s+/g, ' ').trim();
        }

        function translate(value) {
            const key = normalize(value);
            if (!key) return null;
            return Object.prototype.hasOwnProperty.call(dictionary, key) ? dictionary[key] : null;
        }

        const phraseKeys = Object.keys(dictionary)
            .filter(key => key.length >= 6)
            .sort((a, b) => b.length - a.length);

        function translateFragment(value) {
            const exact = translate(value);
            if (exact !== null) return exact;

            let output = String(value || '');
            phraseKeys.forEach(key => {
                if (!output.includes(key)) return;
                output = output.split(key).join(dictionary[key]);
            });

            return output === String(value || '') ? null : output;
        }

        function preserveSpacing(original, translated) {
            const leading = original.match(/^\s*/)?.[0] || '';
            const trailing = original.match(/\s*$/)?.[0] || '';
            return leading + translated + trailing;
        }

        function translateTextNode(node) {
            const parent = node.parentElement;
            if (!parent || ignoredTags.has(parent.tagName)) return;
            if (parent.closest('[data-no-ui-translate]')) return;
            const translated = translateFragment(node.nodeValue);
            if (translated === null) return;
            node.nodeValue = preserveSpacing(node.nodeValue, translated);
            if (isArabic) {
                parent.setAttribute('lang', 'ar');
            }
        }

        function translateAttributes(element) {
            translatableAttributes.forEach(attribute => {
                if (!element.hasAttribute(attribute)) return;
                const translated = translateFragment(element.getAttribute(attribute));
                if (translated !== null) {
                    element.setAttribute(attribute, translated);
                }
            });

            if (['INPUT', 'BUTTON'].includes(element.tagName)) {
                const type = (element.getAttribute('type') || '').toLowerCase();
                if (['submit', 'button', 'reset'].includes(type) && element.hasAttribute('value')) {
                    const translated = translateFragment(element.getAttribute('value'));
                    if (translated !== null) {
                        element.setAttribute('value', translated);
                    }
                }
            }
        }

        function translateElement(root) {
            if (!root || root.nodeType !== Node.ELEMENT_NODE) return;
            if (root.matches('[data-no-ui-translate]')) return;
            translateAttributes(root);
            root.querySelectorAll('*').forEach(translateAttributes);

            const walker = document.createTreeWalker(root, NodeFilter.SHOW_TEXT, {
                acceptNode(node) {
                    if (!normalize(node.nodeValue)) return NodeFilter.FILTER_REJECT;
                    const parent = node.parentElement;
                    if (!parent || ignoredTags.has(parent.tagName) || parent.closest('[data-no-ui-translate]')) {
                        return NodeFilter.FILTER_REJECT;
                    }
                    return NodeFilter.FILTER_ACCEPT;
                }
            });

            const nodes = [];
            while (walker.nextNode()) {
                nodes.push(walker.currentNode);
            }
            nodes.forEach(translateTextNode);
        }

        window.__kalanT = function (value) {
            return translateFragment(value) ?? value;
        };

        const nativeAlert = window.alert;
        window.alert = function (message) {
            return nativeAlert.call(window, window.__kalanT(message));
        };

        const nativeConfirm = window.confirm;
        window.confirm = function (message) {
            return nativeConfirm.call(window, window.__kalanT(message));
        };

        function patchSweetAlert() {
            if (!window.Swal || window.Swal.__kalanTranslated) return;
            const nativeFire = window.Swal.fire.bind(window.Swal);
            window.Swal.fire = function (...args) {
                if (typeof args[0] === 'string') {
                    args[0] = window.__kalanT(args[0]);
                    if (typeof args[1] === 'string') {
                        args[1] = window.__kalanT(args[1]);
                    }
                } else if (args[0] && typeof args[0] === 'object') {
                    ['title', 'text', 'html', 'confirmButtonText', 'cancelButtonText', 'denyButtonText'].forEach(key => {
                        if (typeof args[0][key] === 'string') {
                            args[0][key] = window.__kalanT(args[0][key]);
                        }
                    });
                }
                return nativeFire(...args);
            };
            window.Swal.__kalanTranslated = true;
        }

        translateElement(document.body);
        patchSweetAlert();

        new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === Node.TEXT_NODE) {
                        translateTextNode(node);
                        return;
                    }
                    translateElement(node);
                });
                if (mutation.type === 'attributes') {
                    translateAttributes(mutation.target);
                }
            });
            patchSweetAlert();
        }).observe(document.body, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: translatableAttributes
        });
    });
</script>
@endif
