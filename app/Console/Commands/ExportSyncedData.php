<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Dumps every synced/generated table to JSON — for moving a populated
 * database (e.g. this local SQLite one) to another database (production
 * MySQL) without re-running every sync command and burning the shared
 * SStats rate limit a second time.
 *
 * Raw query-builder rows, not Eloquent models: this keeps every column's
 * on-the-wire value (dates as plain strings, booleans as 0/1, the posts
 * `body` JSON column as its already-encoded string) so data:import can
 * insert it straight back with no per-column translation.
 */
class ExportSyncedData extends Command
{
    protected $signature = 'data:export {--path=storage/app/export}';

    protected $description = 'Export leagues/teams/matches/standings/goals/posts/stream_sources to JSON';

    /** Parent-first — the order data:import replays them in. */
    private const TABLES = [
        'leagues', 'teams', 'matches', 'standings', 'goals', 'posts', 'stream_sources',
    ];

    public function handle(): int
    {
        $path = base_path($this->option('path'));
        File::ensureDirectoryExists($path);

        foreach (self::TABLES as $table) {
            $rows = DB::table($table)->get();
            File::put("{$path}/{$table}.json", $rows->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->info(sprintf('%-16s %d rows', $table, $rows->count()));
        }

        $this->newLine();
        $this->info("Exported to {$path}");
        $this->line('Copy that folder to the server (same relative path), then run: php artisan data:import --fresh');

        return self::SUCCESS;
    }
}
