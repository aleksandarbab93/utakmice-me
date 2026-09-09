<?php

namespace App\Console\Commands;

use App\Models\Fixture;
use App\Models\Goal;
use App\Models\MatchDetailRecord;
use App\Models\Post;
use App\Services\SStats\SStatsClient;
use App\Support\MatchReportGenerator;
use Illuminate\Console\Command;

/**
 * Rewrites the reports that were written without their match detail.
 *
 * A report generated while SStats' detail call was failing has nothing to
 * work with but the scoreline, so it ends up titled "Arsenal - Coventry 3:0"
 * with no scorers, no narrative and no goals recorded for the league's
 * top-scorer list. Once sstats:fetch-details has stored that match's detail,
 * the same generator produces the real thing — this finds those reports and
 * has it do so.
 *
 * Only reports still carrying the generic title are touched, so running it
 * twice changes nothing the second time.
 */
class RegenerateReports extends Command
{
    protected $signature = 'reports:regenerate
        {--limit=200 : how many to rewrite in one run}
        {--dry-run   : list what would be rewritten and change nothing}';

    protected $description = 'Rewrites match reports that were generated without their match detail';

    public function handle(SStatsClient $client): int
    {
        $withDetail = MatchDetailRecord::query()->select('fixture_id');

        $posts = Post::query()
            ->where('type', 'izvestaj')
            ->whereNotNull('fixture_id')
            ->whereIn('fixture_id', $withDetail)
            ->with(['fixture.homeTeam', 'fixture.awayTeam'])
            ->get()
            ->filter(fn (Post $post) => $post->fixture && $post->title === self::genericTitle($post->fixture))
            ->take((int) $this->option('limit'));

        if ($posts->isEmpty()) {
            $this->info('No generic reports with a stored detail to rewrite.');

            return self::SUCCESS;
        }

        if ($this->option('dry-run')) {
            $this->info("{$posts->count()} would be rewritten:");
            $posts->each(fn (Post $post) => $this->line('  '.$post->title));

            return self::SUCCESS;
        }

        $rewritten = 0;

        foreach ($posts as $post) {
            $fixture = $post->fixture;

            // The generator refuses to write a second report for a fixture,
            // and it re-records the goals it finds, so the old rows go first.
            Goal::where('fixture_id', $fixture->id)->delete();
            $post->delete();

            $fresh = MatchReportGenerator::generate($fixture, $client);

            if (! $fresh) {
                $this->line("  <fg=yellow>-</> {$fixture->homeTeam->name} - {$fixture->awayTeam->name}: generator declined");

                continue;
            }

            $this->line("  <fg=green>✓</> {$fresh->title}");
            $rewritten++;
        }

        $this->newLine();
        $this->info("{$rewritten} reports rewritten.");

        return self::SUCCESS;
    }

    /** What MatchReportGenerator falls back to when it has no detail to work with. */
    private static function genericTitle(Fixture $fixture): string
    {
        return $fixture->homeTeam->name.' - '.$fixture->awayTeam->name.' '.$fixture->home_score.':'.$fixture->away_score;
    }
}
