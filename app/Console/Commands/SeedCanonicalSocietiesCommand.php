<?php

namespace App\Console\Commands;

use App\Models\Society;
use Illuminate\Console\Command;

class SeedCanonicalSocietiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'societies:seed-canonical';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Insert canonical global societies required for society_name → society_id migration';

    /**
     * Canonical society names (exact strings for global societies, province_id = NULL).
     * Excludes names that already exist as province-scoped societies to avoid duplicates.
     *
     * @var array<int, string>
     */
    private const CANONICAL_NAMES = [
        "ST. ANN'S EDUCATIONAL SOCIETY",
        'SARVAJANA SNEHA CHARITABLE TRUST',
        'WILHELM MEYERS DEVELOPMENTAL SOCIETY',
        "ST. ANN'S SOCIETY, VISAKHAPATNAM",
        "ST.ANN'S SOCIETY, SOUTHERN REGION",
        "ST. ANNE'S SOCIETY",
        'BIARA SANTA ANNA, MAUSAMBI',
        "ST. ANN'S CONVENT, LURO",
    ];

    /**
     * Execute the console command.
     * Idempotent: only inserts missing societies. Does not overwrite existing records.
     */
    public function handle(): int
    {
        $this->line('----------------------------------------');
        $this->line('Seeding Canonical Societies');
        $this->line('----------------------------------------');

        $created = 0;
        $existing = 0;

        foreach (self::CANONICAL_NAMES as $name) {
            $society = Society::firstOrCreate(
                [
                    'name' => $name,
                    'province_id' => null,
                ],
                ['is_active' => true]
            );

            if ($society->wasRecentlyCreated) {
                $this->line("✔ Created: {$name}");
                $created++;
            } else {
                $this->line("✔ Exists:  {$name}");
                $existing++;
            }
        }

        $total = count(self::CANONICAL_NAMES);
        $this->newLine();
        $this->line('----------------------------------------');
        $this->line('Summary');
        $this->line('----------------------------------------');
        $this->line("Total processed: {$total}");
        $this->line("Created: {$created}");
        $this->line("Existing: {$existing}");
        $this->line('----------------------------------------');

        return self::SUCCESS;
    }
}
