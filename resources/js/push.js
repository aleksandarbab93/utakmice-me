/**
 * Goal notifications for the matches you favorite.
 *
 * The favorites list lives where it already does — localStorage, key
 * "utakmice_favorites" (the same one scores.blade.php's star buttons write
 * to). This adds one thing: a browser that opts in hands the server the push
 * endpoint its own PushManager gave it, plus that list, so football:sync-live
 * can reach it when a goal goes in. The list is posted whole on every change;
 * the server replaces what it holds, so the two can never drift apart.
 *
 * Nothing here is required for the site to work, and every step is allowed
 * to fail: no service worker, no PushManager, permission refused, a server
 * with no VAPID keys. In each case the switch simply isn't offered.
 */
(function () {
    const FAV_KEY = 'utakmice_favorites';
    const SUPPORTED = typeof window !== 'undefined'
        && 'serviceWorker' in navigator
        && 'PushManager' in window
        && 'Notification' in window;

    function getFavs() {
        try {
            return JSON.parse(localStorage.getItem(FAV_KEY) || '[]');
        } catch (e) {
            return [];
        }
    }

    /** The VAPID public key arrives base64url; subscribe() wants raw bytes. */
    function toBytes(base64url) {
        const padded = base64url + '='.repeat((4 - (base64url.length % 4)) % 4);
        const raw = atob(padded.replace(/-/g, '+').replace(/_/g, '/'));

        return Uint8Array.from([...raw].map((ch) => ch.charCodeAt(0)));
    }

    async function registration() {
        if (!('serviceWorker' in navigator)) return null;

        try {
            if (!navigator.serviceWorker.controller) {
                await navigator.serviceWorker.register('/sw.js');
            }

            return await navigator.serviceWorker.ready;
        } catch (e) {
            return null;
        }
    }

    async function current() {
        if (!SUPPORTED) return null;

        try {
            const reg = await navigator.serviceWorker.getRegistration();

            return reg ? await reg.pushManager.getSubscription() : null;
        } catch (e) {
            return null;
        }
    }

    async function tell(subscription) {
        await fetch('/api/push/prijava', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            credentials: 'same-origin',
            body: JSON.stringify({
                ...subscription.toJSON(),
                fixtures: getFavs().map(Number),
            }),
        });
    }

    async function enable() {
        const permission = await Notification.requestPermission();

        if (permission !== 'granted') {
            throw new Error('permission');
        }

        const reg = await registration();
        if (!reg) throw new Error('failed');

        let subscription = await reg.pushManager.getSubscription();

        if (!subscription) {
            const response = await fetch('/api/push/kljuc', { credentials: 'same-origin' });
            const { key } = await response.json();

            if (!key) throw new Error('disabled');

            subscription = await reg.pushManager.subscribe({
                // Required by every browser: a push that shows nothing isn't
                // allowed, which is the rule that keeps this from being a tracker.
                userVisibleOnly: true,
                applicationServerKey: toBytes(key),
            });
        }

        await tell(subscription);
    }

    async function disable() {
        const subscription = await current();

        if (!subscription) return;

        try {
            await fetch('/api/push/odjava', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ endpoint: subscription.endpoint }),
            });
        } catch (e) {
            /* pruned on the first failed send instead */
        }

        await subscription.unsubscribe();
    }

    function paint(buttons, on) {
        buttons.forEach((button) => {
            button.setAttribute('aria-pressed', on ? 'true' : 'false');
            const onLabel = button.dataset.tOn || 'Isključi obavještenja o golovima';
            const offLabel = button.dataset.tOff || 'Uključi obavještenja o golovima';
            button.title = on ? onLabel : offLabel;
            button.setAttribute('aria-label', button.title);
            button.classList.toggle('text-accent', on);
            button.classList.toggle('text-text-dim', !on);

            const label = button.querySelector('[data-push-state]');
            if (label) label.textContent = on ? 'Obavještenja uključena' : 'Obavještenja o golovima';
        });
    }

    function init() {
        const buttons = [...document.querySelectorAll('[data-push-toggle]')];

        if (!buttons.length) return;

        // The switch appears only where it can actually do something: a
        // browser that supports push, and a server with keys to sign with —
        // signaled by data-push="1" on <body> (see layouts/app.blade.php).
        if (!SUPPORTED || document.body.dataset.push !== '1') return;

        buttons.forEach((button) => { button.hidden = false; });

        current().then((subscription) => paint(buttons, !!subscription));

        buttons.forEach((button) => button.addEventListener('click', async () => {
            buttons.forEach((el) => { el.disabled = true; });

            try {
                const on = await current();

                if (on) {
                    await disable();
                    paint(buttons, false);
                } else {
                    await enable();
                    paint(buttons, true);
                }
            } catch (e) {
                /* permission denied, or push failed to enable — button stays off */
            } finally {
                buttons.forEach((el) => { el.disabled = false; });
            }
        }));

        // Star a match, and the server hears about it — but only if this
        // browser has already opted in. Favoriting is not consent to be notified.
        document.addEventListener('utakmice:favorites-changed', async () => {
            const subscription = await current();

            if (subscription) {
                tell(subscription).catch(() => {});
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
