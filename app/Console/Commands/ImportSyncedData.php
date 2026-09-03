<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * Replays a data:export dump into this database — parent tables first, so
 * every foreign key (team → league, match → team/league, post → match/league)
 * resolves on the way in instead of failing halfway through.
 */
class ImportSyncedData extends Command
{
    protected $signature = 'data:import {--path=storage/app/export} {--fresh : Truncate each table before importing, so this can be re-run safely}';

    protected $description = 'Import a data:export dump (run data:export on the source database first)';

    private const TABLES = [
        'leagues', 'teams', 'matches', 'standings', 'goals', 'posts', 'stream_sources',
    ];

    public function handle(): int
    {
        $path = base_path($this->option('path'));

        if (! File::isDirectory($path)) {
            $this->error("Nema exporta na {$path}. Prvo pokreni 'php artisan data:export' na izvornoj bazi i prebaci taj folder ovdje.");

            return self::FAILURE;
        }

        $isMysql = DB::connection()->getDriverName() === 'mysql';

        if ($this->option('fresh')) {
            if ($isMysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS=0');
            }

            foreach (array_reverse(self::TABLES) as $table) {
                DB::table($table)->truncate();
            }

            if ($isMysql) {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }

        foreach (self::TABLES as $table) {
            $file = "{$path}/{$table}.json";

            if (! File::exists($file)) {
                $this->warn("{$table}: nema fajla, preskačem.");

                continue;
            }

            $rows = json_decode(File::get($file), true);

            if (empty($rows)) {
                $this->line("{$table}: prazno, preskačem.");

                continue;
            }

            foreach (array_chunk($rows, 500) as $chunk) {
                DB::table($table)->insert($chunk);
            }

            $this->info(sprintf('%-16s %d rows', $table, count($rows)));
        }

        $this->newLine();
        $this->info('Gotovo.');

        return self::SUCCESS;
    }
}
