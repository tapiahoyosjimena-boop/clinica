/**
 * Tema portal (paciente / médico): claro, oscuro, sistema.
 * Misma convención que Filament: clase `dark` en <html>.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'cn_portal_theme';

    function getMode() {
        try {
            var v = localStorage.getItem(STORAGE_KEY);
            if (v === 'light' || v === 'dark' || v === 'system') {
                return v;
            }
        } catch (e) {}

        return 'system';
    }

    function effectiveDark(mode) {
        if (mode === 'dark') {
            return true;
        }
        if (mode === 'light') {
            return false;
        }

        return window.matchMedia('(prefers-color-scheme: dark)').matches;
    }

    function updateSegmentUI(mode) {
        document.querySelectorAll('[data-set-theme]').forEach(function (btn) {
            var m = btn.getAttribute('data-set-theme');
            var on = m === mode;
            btn.classList.toggle('is-active', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
        });
    }

    function apply(mode) {
        try {
            localStorage.setItem(STORAGE_KEY, mode);
        } catch (e) {}
        document.documentElement.classList.toggle('dark', effectiveDark(mode));
        updateSegmentUI(mode);
    }

    var onSystemSchemeChange = null;

    function bindSystemSchemeListener() {
        var mq = window.matchMedia('(prefers-color-scheme: dark)');
        if (onSystemSchemeChange) {
            mq.removeEventListener('change', onSystemSchemeChange);
        }
        onSystemSchemeChange = function () {
            if (getMode() === 'system') {
                document.documentElement.classList.toggle('dark', mq.matches);
            }
        };
        mq.addEventListener('change', onSystemSchemeChange);
    }

    function init() {
        apply(getMode());
        bindSystemSchemeListener();

        document.querySelectorAll('[data-set-theme]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                apply(btn.getAttribute('data-set-theme') || 'system');
            });
        });

        window.addEventListener('storage', function (e) {
            if (e.key === STORAGE_KEY) {
                apply(getMode());
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
