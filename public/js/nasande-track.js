(function () {
    'use strict';

    var queue = [];
    var rageClicks = 0;
    var lastClickAt = 0;
    var maxScrollPct = 0;
    var endpoint = '/api/behavior/beacon';

    document.addEventListener('click', function (e) {
        var now = Date.now();
        if (now - lastClickAt < 450) {
            rageClicks += 1;
        } else {
            rageClicks = 1;
        }
        lastClickAt = now;
        if (rageClicks >= 4) {
            queue.push({
                type: 'rage_click',
                payload: { x: e.clientX, y: e.clientY }
            });
            rageClicks = 0;
        }
    });

    window.addEventListener('scroll', function () {
        var doc = document.documentElement;
        var total = doc.scrollHeight - window.innerHeight;
        if (total <= 0) return;
        var pct = Math.round((window.scrollY / total) * 100);
        var bucket = Math.floor(pct / 25) * 25;
        if (bucket > maxScrollPct && bucket > 0) {
            maxScrollPct = bucket;
            queue.push({ type: 'scroll_depth', payload: { percent: bucket } });
        }
    }, { passive: true });

    var start = Date.now();
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'hidden') {
            queue.push({
                type: 'time_on_page',
                payload: { ms: Date.now() - start }
            });
            flush(true);
        }
    });

    function flush(useBeacon) {
        if (!queue.length) return;
        var body = JSON.stringify({ events: queue.splice(0, queue.length) });
        if (useBeacon && navigator.sendBeacon) {
            navigator.sendBeacon(endpoint, new Blob([body], { type: 'application/json' }));
            return;
        }
        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
            body: body,
            keepalive: true,
            credentials: 'same-origin'
        }).catch(function () {});
    }

    setInterval(function () { flush(false); }, 5000);
})();
