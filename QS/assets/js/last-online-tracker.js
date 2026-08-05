/**
 * Keeps user_form.last_online fresh for as long as a page is open, even
 * if the person never clicks anything (Criterion #2), and records one
 * final ping when the tab/window closes (Criterion #4).
 *
 * Include on every logged-in page:
 *   <script>window.LAST_ONLINE_ENDPOINT = '../../fetch/last_online.php';</script>
 *   <script src="../../assets/js/last-online-tracker.js"></script>
 *
 * The endpoint path differs depending on how deep the including page
 * sits, which is why it's set via a global rather than hard-coded below.
 * If a page doesn't set window.LAST_ONLINE_ENDPOINT, this falls back to
 * '../../fetch/last_online.php' (the depth admin/ and user/ both use).
 */
(function () {
    var ENDPOINT = window.LAST_ONLINE_ENDPOINT || '../../fetch/last_online.php';
    var HEARTBEAT_MS = 60000; // ping every 60s while the tab is open & visible

    var timer = null;

    function ping(useBeacon) {
        if (useBeacon && navigator.sendBeacon) {
            // sendBeacon is fire-and-forget and designed to survive page
            // unload - exactly what's needed when the tab/window closes.
            navigator.sendBeacon(ENDPOINT, new Blob([], { type: 'text/plain' }));
        } else {
            fetch(ENDPOINT, { method: 'POST', keepalive: true, cache: 'no-store' })
                .catch(function () {
                    // Network hiccups here shouldn't interrupt the user;
                    // the next heartbeat tick will simply try again.
                });
        }
    }

    function startHeartbeat() {
        if (timer) return;
        timer = setInterval(function () {
            // Only ping while the tab is actually in the foreground, so a
            // pile of forgotten background tabs doesn't look like activity.
            if (document.visibilityState === 'visible') {
                ping(false);
            }
        }, HEARTBEAT_MS);
    }

    function stopHeartbeat() {
        if (timer) {
            clearInterval(timer);
            timer = null;
        }
    }

    // Coming back to this tab counts as activity right away, rather than
    // waiting for the next scheduled tick.
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') {
            ping(false);
            startHeartbeat();
        }
    });

    // Criterion #4: tab close, window close, browser close, or navigating
    // to a different site. 'pagehide' is used instead of 'beforeunload'
    // because it fires more reliably on mobile browsers and doesn't block
    // back/forward-cache navigation.
    window.addEventListener('pagehide', function () {
        stopHeartbeat();
        ping(true);
    });

    startHeartbeat();
})();