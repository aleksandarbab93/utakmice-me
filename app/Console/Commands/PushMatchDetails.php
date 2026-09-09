<?php

namespace App\Console\Commands;

use App\Models\MatchDetailRecord;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Sends locally stored match details up to production.
 *
 * The other half of sstats:fetch-details: production can't fetch these
 * itself (SStats never finishes a response past ~14.6 KB for a datacenter
 * IP — see the match_details migration), so a machine on an ordinary
 * connection fetches them and this hands them over. Idempotent: production
 * upserts by match, so re-sending costs nothing.
 *
 *     php artisan sstats:fetch-details --limit=100
 *     php artisan sstats:push-details
 */
class PushMatchDetails extends Command
{
    protected $signature = 'sstats:push-details
        {--url=   : the site to push to, default DETAIL_INTAKE_URL}
        {--token= : shared secret, default DETAIL_INTAKE_TOKEN}
        {--days=30 : only matches from the last N days}
        {--all    : every stored detail, however old}
        {--chunk=10 : details per request}';

    protected $description = 'Pushes locally stored match details to production';

    public function handle(): int
    {
        // A single detail is tens of kilobytes of JSON and several times
        // that once decoded into PHP arrays; a few hundred of them walk
        // straight through the default limit even a chunk at a time.
        ini_set('memory_limit', '512M');

        $url = rtrim($this->option('url') ?: (string) env('DETAIL_INTAKE_URL'), '/');
        $token = $this->option('token') ?: (string) env('DETAIL_INTAKE_TOKEN');

        if ($url === '' || $token === '') {
            $this->error('Need a target and a token: --url/--token, or DETAIL_INTAKE_URL/DETAIL_INTAKE_TOKEN in .env');

            return self::FAILURE;
        }

        $query = MatchDetailRecord::query()->with('fixture:id,external_id,external_source,kickoff_at');

        if (! $this->option('all')) {
            $query->whereHas('fixture', fn ($q) => $q->where('kickoff_at', '>=', now()->subDays((int) $this->option('days'))));
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('Nothing stored to push.');

            return self::SUCCESS;
        }

        $this->info("Pushing {$total} details to {$url}...");

        $stored = 0;
        $unknown = [];
        $failed = false;

        // A detail payload is tens of kilobytes and there are hundreds of
        // them — held all at once they exhaust PHP's memory limit, so they
        // are read a chunk at a time and released.
        $query->chunkById((int) $this->option('chunk'), function ($chunk) use ($url, $token, &$stored, &$unknown, &$failed) {
            $payload = $chunk
                ->filter(fn (MatchDetailRecord $r) => $r->fixture?->external_source === 'sstats')
                ->map(fn (MatchDetailRecord $r) => [
                    'external_id' => (string) $r->fixture->external_id,
                    'payload' => $r->payload,
                ])->values()->all();

            if ($payload === []) {
                return true;
            }

            $response = Http::withHeaders(['X-Detail-Token' => $token])
                ->timeout(60)
                ->post($url.'/api/detalji-meca', ['details' => $payload]);

            if ($response->failed()) {
                $this->error('  '.$response->status().': '.$response->body());
                $failed = true;

                return false;
            }

            $stored += (int) $response->json('stored', 0);
            $unknown = array_merge($unknown, $response->json('unknown', []));
            $this->line('  sent '.count($payload));

            return true;
        });

        if ($failed) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->info("{$stored} stored on production.");

        if ($unknown !== []) {
            $this->warn(count($unknown).' matches production does not know yet (it will after its next sync).');
        }

        return self::SUCCESS;
    }
}
