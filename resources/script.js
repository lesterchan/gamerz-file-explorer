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
})();
