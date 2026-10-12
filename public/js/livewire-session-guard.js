/*
 * Prevents Livewire's "This page has expired" confirm dialog site-wide:
 * - keeps the session alive while a tab stays open (periodic ping)
 * - on HTTP 419 reloads the page silently (at most 3 times per minute to avoid loops)
 * - if reloading does not help, shows a small non-blocking notice instead of the confirm dialog
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
    var reloadKey = 'shop:419-reloads';
    var reloadWindowMs = 60 * 1000;
    var maxReloads = 3;

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

    function recentReloads() {
        try {
            var list = JSON.parse(sessionStorage.getItem(reloadKey) || '[]');
            var now = Date.now();
            return Array.isArray(list) ? list.filter(function (t) { return now - t < reloadWindowMs; }) : [];
        } catch (e) {
            return [];
        }
    }

    function markReload(list) {
        try {
            list.push(Date.now());
            sessionStorage.setItem(reloadKey, JSON.stringify(list));
        } catch (e) {}
    }

    function showNotice() {
        if (document.getElementById('shop-session-notice')) {
            return;
        }
        var box = document.createElement('div');
        box.id = 'shop-session-notice';
        box.setAttribute('role', 'alert');
        box.style.cssText = 'position:fixed;bottom:16px;left:50%;transform:translateX(-50%);z-index:99999;'
            + 'display:flex;gap:12px;align-items:center;padding:10px 16px;border-radius:10px;direction:rtl;'
            + 'background:#fff;color:#181c32;border:1px solid #eff2f5;box-shadow:0 6px 24px rgba(0,0,0,.12);'
            + 'font-size:13px;line-height:1.6;font-family:inherit;';
        var text = document.createElement('span');
        text.textContent = 'ارتباط با سرور قطع شد؛ برای ادامه صفحه را تازه کنید.';
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.textContent = 'تازه‌سازی';
        btn.style.cssText = 'border:0;border-radius:8px;padding:4px 12px;background:#009ef7;color:#fff;cursor:pointer;font:inherit;';
        btn.addEventListener('click', function () { window.location.reload(); });
        box.appendChild(text);
        box.appendChild(btn);
        document.body.appendChild(box);
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
                if (!failure || failure.status !== 419) {
                    return;
                }
                if (typeof failure.preventDefault === 'function') {
                    failure.preventDefault();
                }
                var reloads = recentReloads();
                if (reloads.length >= maxReloads) {
                    showNotice();
                    return;
                }
                markReload(reloads);
                window.setTimeout(function () { window.location.reload(); }, reloads.length * 1000);
            });
        });
    }

    if (window.Livewire && typeof window.Livewire.hook === 'function') {
        register();
    } else {
        document.addEventListener('livewire:init', register, { once: true });
    }
})();
