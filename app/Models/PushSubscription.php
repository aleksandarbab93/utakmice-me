<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * One browser that has asked to be told about goals — not a person, see the
 * note on the migration.
 */
class PushSubscription extends Model
{
    protected $guarded = [];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    /** Fixtures this browser is following. */
    public function fixtures(): BelongsToMany
    {
        return $this->belongsToMany(Fixture::class, 'push_subscription_fixture');
    }

    /**
     * The endpoint is what a subscriber is looked up by and is far too long
     * to index whole, so the hash is the key and the endpoint is the
     * payload. Kept in one place so the two can never be computed differently.
     */
    public static function hash(string $endpoint): string
    {
        return hash('sha256', $endpoint);
    }

    /** The shape minishlink/web-push wants back. */
    public function toSubscription(): \Minishlink\WebPush\Subscription
    {
        return \Minishlink\WebPush\Subscription::create([
            'endpoint' => $this->endpoint,
            'publicKey' => $this->p256dh,
            'authToken' => $this->auth,
            'contentEncoding' => 'aesgcm',
        ]);
    }
}
