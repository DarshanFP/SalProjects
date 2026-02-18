<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UsersSocietyBackfillCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:society-backfill
                            {--dry-run : Report what would be updated without writing}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill users.society_id from users.society_name via societies.name (production-safe). Run after migration add_society_id_to_users_table.';

    /**
     * Execute the console command.
     * Uses a single JOIN update (same SQL as Production Phase 5 doc).
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Dry run — no changes will be written.');
        }

        $toBackfill = DB::table('users')
            ->join('societies', 'users.society_name', '=', 'societies.name')
            ->whereNull('users.society_id')
            ->count();

        $this->info("Users eligible for backfill (society_name matches societies.name, society_id IS NULL): {$toBackfill}");

        if ($toBackfill === 0) {
            $this->info('Nothing to do.');
            return self::SUCCESS;
        }

        if ($dryRun) {
            $sample = DB::table('users')
                ->join('societies', 'users.society_name', '=', 'societies.name')
                ->whereNull('users.society_id')
                ->select('users.id', 'users.name', 'users.society_name', 'societies.id as society_id')
                ->limit(10)
                ->get();
            $this->table(['user_id', 'user_name', 'society_name', 'society_id'], $sample->map(fn ($r) => [(string) $r->id, $r->name, $r->society_name, (string) $r->society_id]));
            if ($toBackfill > 10) {
                $this->info('... and ' . ($toBackfill - 10) . ' more.');
            }
            return self::SUCCESS;
        }

        $rowsAffected = DB::update("
            UPDATE users u
            INNER JOIN societies s ON u.society_name = s.name
            SET u.society_id = s.id
            WHERE u.society_id IS NULL
        ");

        $this->info("Backfill complete. Rows updated: {$rowsAffected}");

        $orphans = DB::table('users')
            ->leftJoin('societies', 'users.society_id', '=', 'societies.id')
            ->whereNotNull('users.society_id')
            ->whereNull('societies.id')
            ->count();

        if ($orphans > 0) {
            $this->error("Orphan check failed: {$orphans} users have society_id not in societies.");
            return self::FAILURE;
        }

        $this->info('Orphan check: 0 (OK).');
        return self::SUCCESS;
    }
}
