<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/**
 * When the database was restored or created without the `migrations` table rows,
 * `php artisan migrate` tries to CREATE TABLE again and fails with "already exists".
 * This command inserts migration filenames from database/migrations so Laravel
 * only runs genuinely new migrations. Use only if the schema already matches
 * those migration files (or fix the DB before relying on this).
 */
class SyncMigrationsTableCommand extends Command
{
    protected $signature = 'migrate:sync-table
                            {--force : Skip confirmation}
                            {--dry-run : Show rows that would be inserted}';

    protected $description = 'Register all migration files in the migrations table (baseline when DB exists but migration rows are missing)';

    public function handle(): int
    {
        if (! DB::getSchemaBuilder()->hasTable('migrations')) {
            $this->error('Table `migrations` does not exist. Run: php artisan migrate:install');

            return self::FAILURE;
        }

        $paths = File::glob(database_path('migrations/*.php')) ?: [];
        sort($paths, SORT_STRING);
        $names = array_map(static fn (string $p) => basename($p, '.php'), $paths);

        $existing = DB::table('migrations')->pluck('migration')->all();
        $toAdd = array_values(array_diff($names, $existing));

        if ($toAdd === []) {
            $this->info('Nothing to insert; all migration files are already registered.');

            return self::SUCCESS;
        }

        $this->warn('This will mark '.count($toAdd).' migration(s) as applied without running SQL.');
        $this->line('Use only if your database schema already matches these files.');
        foreach ($toAdd as $name) {
            $this->line('  + '.$name);
        }

        if ($this->option('dry-run')) {
            $this->info('Dry run: no rows inserted.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm('Continue?', false)) {
            $this->info('Aborted.');

            return self::FAILURE;
        }

        $batch = (int) (DB::table('migrations')->max('batch') ?? 0);
        $batch = $batch < 1 ? 1 : $batch + 1;

        foreach ($toAdd as $name) {
            DB::table('migrations')->insert([
                'migration' => $name,
                'batch' => $batch,
            ]);
        }

        $this->info('Inserted '.count($toAdd).' row(s) into `migrations` (batch '.$batch.').');
        $this->line('Run `php artisan migrate` to apply any migrations that were not in this list.');

        return self::SUCCESS;
    }
}
