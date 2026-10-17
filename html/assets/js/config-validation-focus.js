/* Reveal the invalid setting, including settings hidden inside accordions. */
(() => {
    'use strict';
    function reveal(field, message) {
        const target = document.getElementById(field);
        if (!target) return;
        const search = document.getElementById('config-section-search');
        if (search && search.value) {
            search.value = '';
            search.dispatchEvent(new Event('input', {bubbles: true}));
        }
        const parents = [];
        for (let parent = target.parentElement; parent; parent = parent.parentElement) {
            parent.classList.remove('config-section-search-hidden');
            if (parent.classList.contains('collapse')) parents.unshift(parent);
        }
        function focus() {
            target.scrollIntoView({behavior: 'smooth', block: 'center'});
            if (!target.matches('input,select,textarea,button,a')) target.tabIndex = -1;
            target.focus({preventScroll: true});
        }
        if (target.matches('input,select,textarea')) {
            target.classList.add('is-invalid');
            target.setAttribute('aria-invalid', 'true');
            if (message) {
                const id = target.id + '-validation-error';
                let feedback = document.getElementById(id);
                if (!feedback) {
                    feedback = document.createElement('div');feedback.id = id;
                    feedback.className = 'invalid-feedback d-block';
                    feedback.setAttribute('role', 'alert');target.after(feedback);
                }
                feedback.textContent = message;
                const described = new Set((target.getAttribute('aria-describedby') || '').split(/\s+/).filter(Boolean));
                described.add(id);target.setAttribute('aria-describedby', [...described].join(' '));
                const clear = () => {
                    target.classList.remove('is-invalid');target.removeAttribute('aria-invalid');
                    feedback.remove();described.delete(id);
                    if (described.size) target.setAttribute('aria-describedby', [...described].join(' '));
                    else target.removeAttribute('aria-describedby');
                    target.removeEventListener('input', clear);target.removeEventListener('change', clear);
                };
                target.addEventListener('input', clear);target.addEventListener('change', clear);
            }
        }
        // Open outer accordions first; focus after Bootstrap finishes its transition.
        const open = index => {
            if (index === parents.length) { focus();return; }
            const parent = parents[index];
            if (parent.classList.contains('show')) { open(index + 1);return; }
            if (window.bootstrap?.Collapse) {
                parent.addEventListener('shown.bs.collapse', () => open(index + 1), {once: true});
                bootstrap.Collapse.getOrCreateInstance(parent, {toggle: false}).show();
            } else {
                parent.classList.add('show');open(index + 1);
            }
        };
        open(0);
    }
    function init() {
        const error = window.configErrorFocus;
        if (error?.field) reveal(error.field, error.message);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
    else init();
})();
