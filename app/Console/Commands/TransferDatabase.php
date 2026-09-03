<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Copies the content of one database into another, table by table — ported
 * from utakmice-rs-master's deploy/ setup, for the same job: opening
 * production with the already-synced local archive in place instead of
 * spending the first day re-pulling every league one sync at a time (and
 * burning the shared SStats rate limit doing it).
 *
 * Copies rows only. The target's own migrations own the schema, which is
 * what keeps this safe across two different engines — no SQLite dialect
 * ever reaches MySQL.
 */
class TransferDatabase extends Command
{
    protected $signature = 'db:transfer
        {--from=sqlite_import : connection to read from}
        {--to=                : connection to write to, default is the app default}
        {--only=              : comma separated list of tables, instead of all of them}
        {--chunk=500          : rows per insert}
        {--fresh              : empty each target table first}
        {--dry-run            : count what would move and write nothing}';

    protected $description = 'Copies rows from one database connection into another';

    /**
     * Parents before children, so a half-finished run leaves the target
     * usable rather than dangling on a missing league_id/fixture_id.
     */
    private const TABLES = [
        'leagues',
        'teams',
        'matches',
        'standings',
        'goals',
        'posts',
        'stream_sources',
    ];

    public function handle(): int
    {
        $from = $this->option('from');
        $to = $this->option('to') ?: config('database.default');

        if ($from === $to) {
            $this->error('Source and target are the same connection.');

            return self::FAILURE;
        }

        try {
            DB::connection($from)->getPdo();
            DB::connection($to)->getPdo();
        } catch (Throwable $e) {
            $this->error('Cannot open both connections: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("from: {$from}  ->  to: {$to}");

        $tables = $this->tables();
        $moved = 0;

        foreach ($tables as $table) {
            if (! Schema::connection($from)->hasTable($table)) {
                $this->line("  {$table}: not in the source, skipped");

                continue;
            }

            if (! Schema::connection($to)->hasTable($table)) {
                $this->warn("  {$table}: not in the target — run migrate first");

                continue;
            }

            $count = DB::connection($from)->table($table)->count();

            if ($count === 0) {
                $this->line("  {$table}: empty");

                continue;
            }

            if ($this->option('dry-run')) {
                $this->line(sprintf('  %-16s %6d rows would move', $table, $count));
                $moved += $count;

                continue;
            }

            $moved += $this->copy($from, $to, $table, $count);
        }

        $this->newLine();
        $this->info($this->option('dry-run')
            ? "{$moved} rows would move."
            : "{$moved} rows moved.");

        return self::SUCCESS;
    }

    private function copy(string $from, string $to, string $table, int $count): int
    {
        if ($this->option('fresh')) {
            // delete(), not truncate(): truncate needs privileges a shared
            // host often withholds, and it cannot be rolled back.
            DB::connection($to)->table($table)->delete();
        }

        // Only columns both sides agree on — a target that has moved ahead
        // by a migration must not fail the whole transfer over one new column.
        $columns = array_intersect(
            Schema::connection($from)->getColumnListing($table),
            Schema::connection($to)->getColumnListing($table)
        );

        $bar = $this->output->createProgressBar($count);
        $bar->setFormat("  %message:-16s% %current%/%max% [%bar%] %percent:3s%%");
        $bar->setMessage($table);
        $bar->start();

        $written = 0;
        $key = Schema::connection($from)->hasColumn($table, 'id') ? 'id' : $columns[array_key_first($columns)];

        DB::connection($from)
            ->table($table)
            ->orderBy($key)
            ->chunk((int) $this->option('chunk'), function ($rows) use ($to, $table, $columns, &$written, $bar) {
                $payload = $rows->map(fn ($row) => array_intersect_key((array) $row, array_flip($columns)))->all();

                DB::connection($to)->table($table)->insertOrIgnore($payload);

                $written += count($payload);
                $bar->advance(count($payload));
            });

        $bar->finish();
        $this->newLine();

        return $written;
    }

    /** @return string[] */
    private function tables(): array
    {
        if ($only = $this->option('only')) {
            return array_values(array_filter(array_map('trim', explode(',', $only))));
        }

        return self::TABLES;
    }
}
