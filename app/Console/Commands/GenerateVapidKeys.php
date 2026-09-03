<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

/**
 * The one pair of keys Web Push needs. Generated once and then never again:
 * the public half is baked into every subscription a browser has ever made,
 * so a new pair silently orphans every existing subscriber.
 */
class GenerateVapidKeys extends Command
{
    protected $signature = 'push:keys {--force : Print a new pair even though one is configured}';

    protected $description = 'Generate the VAPID key pair for Web Push';

    public function handle(): int
    {
        if (config('services.webpush.public_key') && ! $this->option('force')) {
            $this->warn('A key pair is already configured.');
            $this->line('Replacing it silently breaks every existing subscriber: their');
            $this->line('browsers hold a subscription signed for the old public key, and');
            $this->line('nothing sent under the new one will ever reach them.');
            $this->newLine();
            $this->line('If that is really what you want: php artisan push:keys --force');

            return self::FAILURE;
        }

        try {
            $keys = VAPID::createVapidKeys();
        } catch (\Throwable $e) {
            $this->error('OpenSSL could not generate the key pair: '.$e->getMessage());
            $this->newLine();
            $this->line('Nearly always a missing openssl.cnf. Point OPENSSL_CONF at one and try again:');
            $this->line('  macOS/Linux   export OPENSSL_CONF=/etc/ssl/openssl.cnf');

            return self::FAILURE;
        }

        $this->info('Add these to .env, then run: php artisan config:cache');
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY='.$keys['publicKey']);
        $this->line('VAPID_PRIVATE_KEY='.$keys['privateKey']);
        $this->newLine();
        $this->warn('The private key is a credential. It belongs in .env and nowhere else —');
        $this->warn('not in the repository, not in a chat window.');

        return self::SUCCESS;
    }
}
