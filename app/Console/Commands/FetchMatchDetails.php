<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\MatchDetailRecord;
use App\Services\SStats\SStatsClient;
use App\Support\MatchDetail;
use Illuminate\Console\Command;

/**
 * Fills in the stored match details behind "tok meča", the statistics panel
 * and any report that names its scorers.
 *
 * Worth running from anywhere, but it exists because of where it does NOT
 * work: from a datacenter IP, SStats never finishes a response past
 * ~14.6 KB, which is most match details (see the match_details migration).
 * A home connection gets all of them in half a second each. So this is the
 * command to run on a machine that can, after which `php artisan
 * db:transfer --only=match_details` carries them to production and they are
 * there for good.
 *
 * On the server itself it is still useful: smaller leagues stay under the
 * limit and come through normally.
 */
class FetchMatchDetails extends Command
{
    protected $signature = 'sstats:fetch-details
        {--limit=50   : how many matches to try in one run}
        {--days=      : only matches from the last N days}
        {--refresh    : re-fetch matches whose detail is already stored}';

    protected $description = 'Fetches and stores SStats match details (tok meča, statistics) for finished matches';

    public function handle(SStatsClient $client): int
    {
        $query = Fixture::query()
            ->where('external_source', 'sstats')
            ->where('status', 'finished')
            ->with(['homeTeam', 'awayTeam'])
            ->orderByDesc('kickoff_at');

        if (! $this->option('refresh')) {
            $query->whereNotIn('id', MatchDetailRecord::query()->select('fixture_id'));
        }

        if ($days = $this->option('days')) {
            $query->where('kickoff_at', '>=', now()->subDays((int) $days));
        }

        $fixtures = $query->limit((int) $this->option('limit'))->get();

        if ($fixtures->isEmpty()) {
            $this->info('Nothing missing a detail.');

            return self::SUCCESS;
        }

        $this->info("Trying {$fixtures->count()} matches...");

        $stored = 0;
        $failed = 0;

        foreach ($fixtures as $fixture) {
            $label = $fixture->homeTeam->name.' - '.$fixture->awayTeam->name;

            try {
                $detail = $client->gameDetail((int) $fixture->external_id);
            } catch (\Throwable $e) {
                $this->line("  <fg=red>×</> {$label}: ".$this->reason($e));
                $failed++;
                sleep(2);

                continue;
            }

            if (! $detail) {
                $this->line("  <fg=yellow>-</> {$label}: nothing returned");
                $failed++;
                sleep(2);

                continue;
            }

            MatchDetailRecord::updateOrCreate(
                ['fixture_id' => $fixture->id],
                ['payload' => $detail, 'fetched_at' => now()],
            );

            $events = count($detail['events'] ?? []);
            $this->line("  <fg=green>✓</> {$label} ({$events} events)");
            $stored++;

            // The same pace the rest of the SStats calls keep — one request
            // every two seconds is what its keyless tier sustains.
            sleep(2);
        }

        $this->newLine();
        $this->info("{$stored} stored, {$failed} unavailable.");

        if ($failed > 0 && $stored === 0) {
            $this->warn('All of them failed — if this is the server, that is the ~14.6 KB ceiling; run this from a home connection instead.');
        }

        return self::SUCCESS;
    }

    private function reason(\Throwable $e): string
    {
        return str_contains($e->getMessage(), 'timed out')
            ? 'stalled (response too large for this network)'
            : $e->getMessage();
    }
}
