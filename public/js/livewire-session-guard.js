/*
 * Prevents Livewire's "This page has expired" confirm dialog site-wide:
 * - keeps the session alive while a tab stays open (periodic ping)
 * - on HTTP 419 reloads the page silently (at most once per 15s to avoid loops)
 */
(function () {
    'use strict';

    if (window.__shopSessionGuard) {
        return;
    }
    window.__shopSessionGuard = true;

    var script = document.currentScript;
    var pingUrl = (script && script.dataset.pingUrl) || '/session/ping';
    var pingEveryMs = 10 * 60 * 1000;
    var reloadKey = 'shop:419-reload-at';

    function ping() {
        if (document.visibilityState !== 'visible') {
            return;
        }
        fetch(pingUrl, {
            method: 'GET',
            credentials: 'same-origin',
            cache: 'no-store',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        }).catch(function () {});
    }

    setInterval(ping, pingEveryMs);
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            ping();
        }
    });

    function recentlyReloaded() {
        try {
            var at = parseInt(sessionStorage.getItem(reloadKey) || '0', 10);
            return Date.now() - at < 15000;
        } catch (e) {
            return false;
        }
    }

    function markReload() {
        try {
            sessionStorage.setItem(reloadKey, String(Date.now()));
        } catch (e) {}
    }

    function register() {
        if (!window.Livewire || typeof window.Livewire.hook !== 'function') {
            return;
        }
        window.Livewire.hook('request', function (ctx) {
            if (!ctx || typeof ctx.fail !== 'function') {
                return;
            }
            ctx.fail(function (failure) {
                if (!failure || failure.status !== 419 || recentlyReloaded()) {
                    return;
                }
                if (typeof failure.preventDefault === 'function') {
                    failure.preventDefault();
                }
                markReload();
                window.location.reload();
            });
        });
    }

    if (window.Livewire && typeof window.Livewire.hook === 'function') {
        register();
    } else {
        document.addEventListener('livewire:init', register, { once: true });
    }
})();
