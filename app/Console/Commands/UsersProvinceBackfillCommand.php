<?php

namespace App\Console\Commands;

use App\Models\Province;
use App\Models\User;
use Illuminate\Console\Command;

class UsersProvinceBackfillCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:province-backfill';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill users.province_id from users.province (string) via provinces.name lookup. Run after adding province_id column.';

    /**
     * Execute the console command.
     * Batched updates (chunk 500). No raw SQL mass update.
     */
    public function handle(): int
    {
        $this->info('Starting users province_id backfill (chunk size: 500).');

        $totalProcessed = 0;
        $totalUpdated = 0;
        $totalUnmatched = 0;

        User::query()
            ->select(['id', 'name', 'province', 'province_id'])
            ->chunk(500, function ($users) use (&$totalProcessed, &$totalUpdated, &$totalUnmatched) {
                foreach ($users as $user) {
                    $totalProcessed++;

                    $provinceName = $user->province;
                    if ($provinceName === null || $provinceName === '') {
                        $this->getOutput()->getErrorStyle()->writeln(
                            "[ERROR] User id={$user->id} name=\"{$user->name}\" has empty province; skipping."
                        );
                        \Log::error('users:province-backfill: empty province', [
                            'user_id' => $user->id,
                            'name' => $user->name,
                        ]);
                        $totalUnmatched++;
                        continue;
                    }

                    $province = Province::where('name', $provinceName)->first();
                    if (!$province) {
                        $this->getOutput()->getErrorStyle()->writeln(
                            "[ERROR] No province found for name=\"{$provinceName}\" (user id={$user->id}, name=\"{$user->name}\")."
                        );
                        \Log::error('users:province-backfill: no province match', [
                            'user_id' => $user->id,
                            'user_name' => $user->name,
                            'province_name' => $provinceName,
                        ]);
                        $totalUnmatched++;
                        continue;
                    }

                    if ((int) $user->province_id !== (int) $province->id) {
                        User::where('id', $user->id)->update(['province_id' => $province->id]);
                        $totalUpdated++;
                    }
                }
            });

        $this->newLine();
        $this->info('--- Summary ---');
        $this->info("Total processed: {$totalProcessed}");
        $this->info("Total updated:   {$totalUpdated}");
        $this->info("Total unmatched: {$totalUnmatched}");

        if ($totalUnmatched > 0) {
            $this->warn('Verification gate: Do NOT proceed to projects.province_id backfill or NOT NULL until all users have province_id set.');
            $this->warn('Run: SELECT id, name, province FROM users WHERE province_id IS NULL;');
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
