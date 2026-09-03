<?php

namespace App\Support;

use App\Models\PushSubscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\WebPush as Sender;

/**
 * Sends a payload to browsers, with nobody in between — no third-party
 * account, no Firebase console. Every path here is best-effort and silent
 * about it: a goal notification that fails to send is a shame, but a goal
 * notification that throws would take the live sync down with it, which is
 * scores stopping for everybody.
 */
class WebPush
{
    public static function configured(): bool
    {
        return (bool) config('services.webpush.public_key')
            && (bool) config('services.webpush.private_key');
    }

    public static function publicKey(): ?string
    {
        return config('services.webpush.public_key');
    }

    /**
     * Deliver one payload to many browsers.
     *
     * @param  Collection<int, PushSubscription>  $subscriptions
     * @param  array{title: string, body?: string, url?: string, tag?: string}  $payload
     * @return int how many the push services accepted
     */
    public static function send(Collection $subscriptions, array $payload): int
    {
        if (! self::configured() || $subscriptions->isEmpty()) {
            return 0;
        }

        try {
            $sender = new Sender(['VAPID' => [
                'subject' => config('services.webpush.subject'),
                'publicKey' => config('services.webpush.public_key'),
                'privateKey' => config('services.webpush.private_key'),
            ]], [
                'TTL' => (int) config('services.webpush.ttl', 1800),
            ]);
        } catch (\Throwable $e) {
            Log::warning('web push is misconfigured', ['reason' => $e->getMessage()]);

            return 0;
        }

        $body = json_encode($payload, JSON_UNESCAPED_UNICODE);

        // Queued and then flushed together: the library sends them over one
        // curl_multi handle, so many subscribers cost about as long as the
        // slowest one rather than the sum of all of them — this runs inside
        // football:sync-live, which has a minute to finish.
        foreach ($subscriptions as $subscription) {
            try {
                $sender->queueNotification($subscription->toSubscription(), $body);
            } catch (\Throwable $e) {
                Log::warning('web push could not queue', ['reason' => $e->getMessage()]);
            }
        }

        $sent = 0;
        $gone = [];

        // flush() returns a Generator — its body (and any key/encryption
        // error) only runs as this loop iterates it, so the try has to wrap
        // the iteration itself, not just the call that creates it. A bad
        // subscriber's row stops the loop early rather than taking the
        // whole live sync down with it.
        try {
            foreach ($sender->flush() as $report) {
                if ($report->isSuccess()) {
                    $sent++;

                    continue;
                }

                // 404 and 410 are the push service saying this browser is
                // gone for good — uninstalled, site data cleared,
                // subscription replaced. Anything else is a bad night, not
                // a dead subscriber.
                if (in_array($report->getResponse()?->getStatusCode(), [404, 410], true)) {
                    $gone[] = PushSubscription::hash($report->getEndpoint());

                    continue;
                }

                Log::warning('web push send failed', [
                    'status' => $report->getResponse()?->getStatusCode(),
                    'reason' => $report->getReason(),
                ]);
            }
        } catch (\Throwable $e) {
            Log::warning('web push flush failed', ['reason' => $e->getMessage()]);
        }

        if ($gone) {
            PushSubscription::whereIn('endpoint_hash', $gone)->delete();
        }

        return $sent;
    }
}
