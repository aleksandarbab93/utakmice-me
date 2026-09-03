<?php

namespace App\Http\Controllers;

use App\Models\Fixture;
use App\Models\PushSubscription;
use App\Support\WebPush;
use Illuminate\Http\Request;

/**
 * Subscribing a browser to goal notifications.
 *
 * No accounts and no sessions: a subscription IS the endpoint the push
 * service handed the browser, and it's the only thing stored that identifies
 * anybody. The browser keeps its list of favorited matches where it already
 * does — localStorage — and posts the whole of it whenever it changes; this
 * replaces what the server holds. No merge, no drift.
 */
class PushController extends Controller
{
    /** How many matches one browser may follow. A star is a tap; a list is not. */
    private const MAX_FIXTURES = 100;

    /** The public half of our VAPID pair, which the browser needs to subscribe. */
    public function key()
    {
        return response()->json(['key' => WebPush::publicKey()]);
    }

    /**
     * Create or refresh this browser's subscription and set what it follows.
     * Idempotent on the endpoint: a browser that resubscribes lands on the
     * same row rather than a second one.
     */
    public function subscribe(Request $request)
    {
        if (! WebPush::configured()) {
            return response()->json(['ok' => false, 'reason' => 'disabled'], 503);
        }

        $data = $request->validate([
            'endpoint' => ['required', 'string', 'url', 'max:2048'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'fixtures' => ['array', 'max:'.self::MAX_FIXTURES],
            'fixtures.*' => ['integer'],
        ]);

        $subscription = PushSubscription::updateOrCreate(
            ['endpoint_hash' => PushSubscription::hash($data['endpoint'])],
            [
                'endpoint' => $data['endpoint'],
                'p256dh' => $data['keys']['p256dh'],
                'auth' => $data['keys']['auth'],
                'user_agent' => substr((string) $request->userAgent(), 0, 255),
                'last_seen_at' => now(),
            ]
        );

        // Only matches that exist and haven't been played: a subscription to
        // a finished match is a row that can never fire.
        $ids = Fixture::whereIn('id', $data['fixtures'] ?? [])
            ->where('status', '!=', 'finished')
            ->pluck('id');

        $subscription->fixtures()->sync($ids);

        return response()->json(['ok' => true, 'following' => $ids->count()]);
    }

    /** The reader turned it off, or the browser dropped its subscription. */
    public function unsubscribe(Request $request)
    {
        $data = $request->validate([
            'endpoint' => ['required', 'string', 'max:2048'],
        ]);

        PushSubscription::where('endpoint_hash', PushSubscription::hash($data['endpoint']))->delete();

        return response()->json(['ok' => true]);
    }
}
