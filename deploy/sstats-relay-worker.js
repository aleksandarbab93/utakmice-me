/**
 * A one-file Cloudflare Worker that fetches from SStats on our behalf.
 *
 * Why this exists: from the production box, any SStats response larger than
 * about 14,600 bytes (one initial TCP congestion window) stops mid-transfer
 * and never resumes — headers and the first burst arrive, then the
 * connection hangs until it times out. It is reproducible from plain curl
 * and from PHP alike, survives disabling HTTP/2, disabling keep-alive, and
 * rate-limiting the download, and a 20 KB response from an unrelated host on
 * the same box arrives in under a second. So it isn't the app, the HTTP
 * client, or the host's bandwidth: it's that one network path. Small
 * responses (a regional league's fixtures) work; big ones (a Champions
 * League match's full detail, with its odds tree) never finish.
 *
 * Cloudflare's own path to SStats has no such problem, so the app asks
 * Cloudflare instead, and Cloudflare asks SStats.
 *
 * Deploy: Cloudflare dashboard → Workers & Pages → Create → paste this →
 * add a RELAY_TOKEN secret (Settings → Variables) → Deploy. Then, in the
 * app's .env on the server:
 *
 *     SSTATS_BASE_URL=https://<your-worker>.workers.dev
 *     SSTATS_RELAY_TOKEN=<the same secret>
 */

const UPSTREAM = 'https://api.sstats.net';

export default {
  async fetch(request, env) {
    const url = new URL(request.url);

    // Without this the worker is an open proxy anybody can point at
    // anything — the token keeps it ours.
    if (!env.RELAY_TOKEN || url.searchParams.get('token') !== env.RELAY_TOKEN) {
      return new Response('Forbidden', { status: 403 });
    }

    url.searchParams.delete('token');

    const target = UPSTREAM + url.pathname + (url.search || '');

    let upstream;
    try {
      upstream = await fetch(target, {
        headers: { Accept: 'application/json' },
      });
    } catch (e) {
      return new Response(JSON.stringify({ error: 'upstream unreachable' }), {
        status: 502,
        headers: { 'Content-Type': 'application/json' },
      });
    }

    // Buffered rather than streamed on purpose: the caller's whole problem
    // is a response that never ends, so the worker reads it to completion
    // itself and answers with a known, finite length.
    const body = await upstream.text();

    return new Response(body, {
      status: upstream.status,
      headers: {
        'Content-Type': upstream.headers.get('Content-Type') ?? 'application/json',
        'Cache-Control': 'no-store',
      },
    });
  },
};
