hljs.highlightAll();

(function () {
    var buttons = document.querySelectorAll('.gfe-theme-switch button[data-gfe-set]');
    window.gfeSyncTheme = function () {
        var current = document.documentElement.getAttribute('data-gfe-theme') || 'auto';
        buttons.forEach(function (btn) {
            btn.setAttribute('aria-pressed', btn.getAttribute('data-gfe-set') === current ? 'true' : 'false');
        });
    };
    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            window.gfeSetTheme(btn.getAttribute('data-gfe-set'));
        });
    });
    window.gfeSyncTheme();

    document.querySelectorAll('.gfe-table tbody tr').forEach(function (row) {
        var link = row.querySelector('a');
        if (! link) {
            return;
        }
        row.addEventListener('click', function (e) {
            if (e.target.closest('a') || String(window.getSelection())) {
                return;
            }
            if (e.metaKey || e.ctrlKey) {
                window.open(link.href, '_blank');
            } else {
                window.location.href = link.href;
            }
        });
    });

    document.querySelectorAll('.gfe-copy, .gfe-copy-code').forEach(function (el) {
        el.hidden = false;
    });

    var flashCopied = function (btn) {
        var icon = btn.querySelector('i');
        if (! icon) {
            return;
        }
        var originalClass = icon.className;
        var originalTitle = btn.getAttribute('title');
        icon.className = 'fa-solid fa-check';
        btn.setAttribute('title', 'Copied');
        btn.classList.add('gfe-copied');
        setTimeout(function () {
            icon.className = originalClass;
            btn.setAttribute('title', originalTitle);
            btn.classList.remove('gfe-copied');
        }, 1500);
    };
    var copyText = function (text, btn) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function () {
                flashCopied(btn);
            }, function () {});
        }
    };
    document.querySelectorAll('[data-gfe-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            copyText(btn.getAttribute('data-gfe-copy'), btn);
        });
    });
    document.querySelectorAll('.gfe-copy-code').forEach(function (btn) {
        var card = btn.closest('.card');
        var code = card ? card.querySelector('.gfe-code-body code') : null;
        if (! code) {
            return;
        }
        btn.addEventListener('click', function () {
            copyText(code.textContent, btn);
        });
    });

    var filter = document.getElementById('gfe-filter-input');
    if (filter) {
        var wrap = filter.closest('.gfe-filter');
        if (wrap) {
            wrap.hidden = false;
        }
        var filterRows = Array.prototype.slice.call(document.querySelectorAll('.gfe-table tbody tr'));
        filter.addEventListener('input', function () {
            var q = filter.value.trim().toLowerCase();
            filterRows.forEach(function (row) {
                if (row.classList.contains('gfe-row-parent') || row.classList.contains('gfe-row-empty')) {
                    return;
                }
                var link = row.querySelector('a');
                var name = (link ? link.textContent : row.textContent).toLowerCase();
                row.hidden = q !== '' && name.indexOf(q) === -1;
            });
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.defaultPrevented || e.altKey || e.ctrlKey || e.metaKey) {
            return;
        }
        var el = e.target;
        var tag = ((el && el.tagName) || '').toLowerCase();
        if (tag === 'input' || tag === 'textarea' || tag === 'select' || (el && el.isContentEditable)) {
            return;
        }
        var selector = e.key === 'ArrowLeft' ? '[data-gfe-nav="prev"]' : (e.key === 'ArrowRight' ? '[data-gfe-nav="next"]' : '');
        if (! selector) {
            return;
        }
        var link = document.querySelector(selector);
        if (link) {
            window.location.href = link.href;
        }
    });
})();
