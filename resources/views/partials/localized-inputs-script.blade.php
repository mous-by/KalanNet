<script>
    document.addEventListener('DOMContentLoaded', function () {
        const locale = document.documentElement.lang || 'fr';
        const isArabic = locale === 'ar';
        const arabicDigits = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        const numericKeys = [
            ['1', '2', '3'],
            ['4', '5', '6'],
            ['7', '8', '9'],
            ['.', '0', 'backspace']
        ];
        const arabicLetterRows = [
            ['ض', 'ص', 'ث', 'ق', 'ف', 'غ', 'ع', 'ه', 'خ', 'ح', 'ج', 'د'],
            ['ش', 'س', 'ي', 'ب', 'ل', 'ا', 'ت', 'ن', 'م', 'ك', 'ط'],
            ['ئ', 'ء', 'ؤ', 'ر', 'لا', 'ى', 'ة', 'و', 'ز', 'ظ'],
            ['space', 'backspace']
        ];
        let activeField = null;
        let keyboard = null;

        function isTextInput(field) {
            if (!field) return false;
            if (field.tagName === 'TEXTAREA') return true;
            if (field.tagName !== 'INPUT') return false;
            return ['text', 'search', 'email', 'url', ''].includes((field.type || '').toLowerCase());
        }

        function isNumericInput(field) {
            if (!field || field.tagName !== 'INPUT') return false;
            const type = (field.type || '').toLowerCase();
            const name = (field.name || field.id || '').toLowerCase();
            return ['number', 'tel'].includes(type)
                || ['telephone', 'phone', 'tel', 'mobile'].some(key => name.includes(key));
        }

        function isTechnicalField(field) {
            const type = (field.type || '').toLowerCase();
            const name = (field.name || field.id || '').toLowerCase();
            return ['email', 'url', 'password'].includes(type)
                || ['email', 'identifier', 'password', 'pwd', 'matricule', 'code'].some(key => name.includes(key));
        }

        function applyFieldDirection(field) {
            if (!isTextInput(field) && !isNumericInput(field)) return;
            const technical = isTechnicalField(field);
            const numeric = isNumericInput(field);
            const arabicReady = isArabic && !technical;
            field.lang = arabicReady ? 'ar' : 'fr';
            field.dir = arabicReady && isTextInput(field) && !numeric ? 'rtl' : 'ltr';
            field.inputMode = arabicReady && numeric ? 'numeric' : field.inputMode;
            field.classList.toggle('arabic-input-ready', arabicReady && isTextInput(field) && !numeric);
            field.classList.toggle('arabic-number-ready', arabicReady && numeric);
        }

        function applyAll(root = document) {
            root.querySelectorAll('input, textarea').forEach(applyFieldDirection);
        }

        function addKeyboardStyles() {
            if (document.getElementById('kalannet-arabic-keyboard-style')) return;
            const style = document.createElement('style');
            style.id = 'kalannet-arabic-keyboard-style';
            style.textContent = `
                .kalannet-arabic-keyboard {
                    position: fixed;
                    right: 12px;
                    left: 12px;
                    bottom: 12px;
                    z-index: 1085;
                    display: none;
                    max-width: 760px;
                    margin: 0 auto;
                    padding: 10px;
                    border: 1px solid rgba(15, 23, 42, .14);
                    border-radius: 8px;
                    background: rgba(255, 255, 255, .98);
                    box-shadow: 0 18px 48px rgba(15, 23, 42, .22);
                }
                .kalannet-arabic-keyboard.is-open {
                    display: block;
                }
                .kalannet-arabic-keyboard__row {
                    display: flex;
                    justify-content: center;
                    gap: 6px;
                    margin-bottom: 6px;
                }
                .kalannet-arabic-keyboard__row:last-child {
                    margin-bottom: 0;
                }
                .kalannet-arabic-keyboard__key {
                    min-width: 38px;
                    height: 42px;
                    border: 1px solid rgba(15, 23, 42, .18);
                    border-radius: 6px;
                    background: #f8fafc;
                    color: #111827;
                    font-size: 18px;
                    font-weight: 700;
                    line-height: 1;
                    cursor: pointer;
                }
                .kalannet-arabic-keyboard__key:hover,
                .kalannet-arabic-keyboard__key:focus {
                    background: #e8f5ee;
                    border-color: var(--theme-accent, #14532d);
                    outline: none;
                }
                .kalannet-arabic-keyboard__key--wide {
                    min-width: 96px;
                }
                @media (max-width: 576px) {
                    .kalannet-arabic-keyboard {
                        right: 6px;
                        left: 6px;
                        bottom: 6px;
                        padding: 8px 6px;
                    }
                    .kalannet-arabic-keyboard__row {
                        gap: 4px;
                    }
                    .kalannet-arabic-keyboard__key {
                        min-width: 0;
                        flex: 1 1 0;
                        height: 40px;
                        padding: 0 4px;
                        font-size: 16px;
                    }
                    .kalannet-arabic-keyboard__key--wide {
                        flex: 2 1 0;
                    }
                }
            `;
            document.head.appendChild(style);
        }

        function createKeyboard() {
            if (keyboard) return keyboard;
            addKeyboardStyles();
            keyboard = document.createElement('div');
            keyboard.className = 'kalannet-arabic-keyboard';
            keyboard.setAttribute('aria-label', 'Clavier arabe');
            keyboard.addEventListener('mousedown', event => event.preventDefault());
            keyboard.addEventListener('click', event => {
                const button = event.target.closest('button[data-key]');
                if (!button || !activeField) return;
                insertKey(button.dataset.key);
            });
            document.body.appendChild(keyboard);
            return keyboard;
        }

        function keyLabel(key, numeric) {
            if (key === 'space') return 'مسافة';
            if (key === 'backspace') return '⌫';
            if (numeric && /^\d$/.test(key)) return arabicDigits[Number(key)];
            return key;
        }

        function renderKeyboard(numeric) {
            const rows = numeric ? numericKeys : arabicLetterRows;
            const html = rows.map(row => {
                const buttons = row.map(key => {
                    const wide = ['space', 'backspace'].includes(key) ? ' kalannet-arabic-keyboard__key--wide' : '';
                    return `<button type="button" class="kalannet-arabic-keyboard__key${wide}" data-key="${key}">${keyLabel(key, numeric)}</button>`;
                }).join('');
                return `<div class="kalannet-arabic-keyboard__row">${buttons}</div>`;
            }).join('');
            createKeyboard().innerHTML = html;
        }

        function showKeyboard(field) {
            if (!isArabic || isTechnicalField(field)) return hideKeyboard();
            if (!isTextInput(field) && !isNumericInput(field)) return hideKeyboard();
            activeField = field;
            renderKeyboard(isNumericInput(field));
            keyboard.classList.add('is-open');
        }

        function hideKeyboard() {
            activeField = null;
            if (keyboard) keyboard.classList.remove('is-open');
        }

        function insertKey(key) {
            if (!activeField) return;
            const supportsSelection = activeField.type !== 'number' && typeof activeField.setSelectionRange === 'function';
            const start = supportsSelection ? activeField.selectionStart ?? activeField.value.length : activeField.value.length;
            const end = supportsSelection ? activeField.selectionEnd ?? activeField.value.length : activeField.value.length;
            let value = '';

            if (key === 'backspace') {
                if (start === end && start > 0) {
                    activeField.value = activeField.value.slice(0, start - 1) + activeField.value.slice(end);
                    if (supportsSelection) {
                        activeField.setSelectionRange(start - 1, start - 1);
                    }
                } else {
                    activeField.value = activeField.value.slice(0, start) + activeField.value.slice(end);
                    if (supportsSelection) {
                        activeField.setSelectionRange(start, start);
                    }
                }
            } else {
                value = key === 'space' ? ' ' : key;
                activeField.value = activeField.value.slice(0, start) + value + activeField.value.slice(end);
                if (supportsSelection) {
                    activeField.setSelectionRange(start + value.length, start + value.length);
                }
            }

            activeField.dispatchEvent(new Event('input', { bubbles: true }));
            activeField.focus();
        }

        applyAll();
        document.addEventListener('focusin', event => {
            applyFieldDirection(event.target);
            showKeyboard(event.target);
        });
        document.addEventListener('pointerdown', event => {
            if (!keyboard || !keyboard.classList.contains('is-open')) return;
            if (event.target.closest('.kalannet-arabic-keyboard')) return;
            if (event.target === activeField) return;
            hideKeyboard();
        });
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
