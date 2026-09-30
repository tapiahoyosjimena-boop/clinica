(function () {
    'use strict';

    var MIN_LEN = 2;
    var DEBOUNCE_MS = 280;

    function typeLabel(type) {
        var labels = {
            paciente: 'Paciente',
            orden: 'Orden',
            examen: 'Examen',
            resultado: 'Resultado',
        };
        return labels[type] || type;
    }

    function escapeHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function escapeAttr(str) {
        return escapeHtml(str).replace(/'/g, '&#39;');
    }

    function init(root) {
        var input = root.querySelector('[data-home-search-input]');
        var panel = root.querySelector('[data-home-search-panel]');
        var endpoint = root.getAttribute('data-endpoint');
        if (!input || !panel || !endpoint) {
            return;
        }

        var timer = null;
        var controller = null;

        function closePanel() {
            panel.classList.remove('is-open');
        }

        function openPanel() {
            panel.classList.add('is-open');
        }

        function renderHint(text) {
            panel.innerHTML = '<div class="home-search__hint">' + escapeHtml(text) + '</div>';
            openPanel();
        }

        function renderEmpty() {
            panel.innerHTML = '<div class="home-search__empty">Sin coincidencias.</div>';
            openPanel();
        }

        function renderItems(items) {
            if (!items.length) {
                renderEmpty();
                return;
            }

            var html = '';
            items.forEach(function (item) {
                var badge = '<span class="home-search__badge">' + escapeHtml(typeLabel(item.type)) + '</span>';
                var title = escapeHtml(item.title || '');
                var subtitle = item.subtitle
                    ? '<div class="home-search__item-meta">' + escapeHtml(item.subtitle) + '</div>'
                    : '';

                if (item.url) {
                    html += '<a class="home-search__item" href="' + escapeAttr(item.url) + '">' +
                        '<div class="home-search__item-title">' + badge + title + '</div>' +
                        subtitle +
                        '</a>';
                } else {
                    html += '<div class="home-search__item">' +
                        '<div class="home-search__item-title">' + badge + title + '</div>' +
                        subtitle +
                        '</div>';
                }
            });

            panel.innerHTML = html;
            openPanel();
        }

        function fetchResults(q) {
            if (controller) {
                controller.abort();
            }
            controller = new AbortController();

            var url = endpoint + '?q=' + encodeURIComponent(q);
            fetch(url, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                signal: controller.signal,
                credentials: 'same-origin',
            })
                .then(function (res) {
                    if (!res.ok) {
                        throw new Error('search failed');
                    }
                    return res.json();
                })
                .then(function (data) {
                    renderItems(data.items || []);
                })
                .catch(function (err) {
                    if (err.name === 'AbortError') {
                        return;
                    }
                    renderEmpty();
                });
        }

        input.addEventListener('input', function () {
            var q = input.value.trim();
            clearTimeout(timer);

            if (q.length < MIN_LEN) {
                if (q.length === 0) {
                    closePanel();
                    panel.innerHTML = '';
                } else {
                    renderHint('Escriba al menos 2 caracteres…');
                }
                return;
            }

            timer = setTimeout(function () {
                fetchResults(q);
            }, DEBOUNCE_MS);
        });

        input.addEventListener('focus', function () {
            var q = input.value.trim();
            if (q.length >= MIN_LEN && panel.innerHTML) {
                openPanel();
            } else if (q.length > 0 && q.length < MIN_LEN) {
                renderHint('Escriba al menos 2 caracteres…');
            }
        });

        document.addEventListener('click', function (e) {
            if (!root.contains(e.target)) {
                closePanel();
            }
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closePanel();
                input.blur();
            }
        });
    }

    document.querySelectorAll('[data-home-search]').forEach(init);
})();
