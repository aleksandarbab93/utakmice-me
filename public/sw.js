/* utakmice.me service worker — one job: show a goal notification for a
 * favorited match, and open that match when it's tapped.
 *
 * No caching, no offline page, no fetch handler — the site's whole subject
 * is what the score is right now, and a cached answer here would be a wrong
 * one. This worker exists only so the browser has something to deliver a
 * push event to.
 */

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(self.clients.claim());
});

/* Payload shape, sent by App\Support\GoalPush: {title, body, url, tag}.
 * The tag is the match, so three goals in ten minutes leave one line on the
 * lock screen showing the current score rather than three showing the history.
 */
self.addEventListener('push', (event) => {
    let payload = {};

    try {
        payload = event.data ? event.data.json() : {};
    } catch (e) {
        /* not our payload — show nothing rather than something wrong */
    }

    if (!payload.title) return;

    event.waitUntil(
        self.registration.showNotification(payload.title, {
            body: payload.body || '',
            icon: '/favicon.svg',
            badge: '/favicon.svg',
            tag: payload.tag || undefined,
            // Replace quietly: the first goal buzzes, the second updates the
            // line that's already there without buzzing again.
            renotify: false,
            data: { url: payload.url || '/' },
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const url = (event.notification.data && event.notification.data.url) || '/';

    event.waitUntil((async () => {
        const windows = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });

        for (const client of windows) {
            if ('focus' in client) {
                await client.focus();

                if ('navigate' in client) {
                    try {
                        await client.navigate(url);
                    } catch (e) {
                        /* cross-origin or blocked — the focused window will do */
                    }
                }

                return;
            }
        }

        await self.clients.openWindow(url);
    })());
});
