<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Province;
use App\Models\Center;

class CenterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds all centers from hardcoded centersMap arrays in controllers.
     */
    public function run(): void
    {
        // Centers map from hardcoded arrays in controllers
        $centersMap = [
            'VIJAYAWADA' => [
                'Ajitsingh Nagar', 'Nunna', 'Jaggayyapeta', 'Beed', 'Mangalagiri',
                'S.A.Peta', 'Thiruvur', 'Chakan', 'Megalaya', 'Rajavaram',
                'Avanigadda', 'Darjeeling', 'Sarvajan Sneha Charitable Trust, Vijayawada', 'St. Anns Hospital Vijayawada'
            ],
            'VISAKHAPATNAM' => [
                'Arilova', 'Malkapuram', 'Madugula', 'Rajam', 'Kapileswarapuram',
                'Erukonda', 'Navajara, Jharkhand', 'Jalaripeta',
                'Wilhelm Meyer\'s Developmental Society, Visakhapatnam.',
                'Edavalli', 'Megalaya', 'Nalgonda', 'Shanthi Niwas, Madugula',
                'Malkapuram College', 'Malkapuram Hospital', 'Arilova School',
                'Morning Star, Eluru', 'Butchirajaupalem', 'Malakapuram (Hospital)',
                'Shalom', 'Berhampur, Odisha', 'Beemunipatnam', 'Mandapeta',
                'Malkapuram School', 'Bheemunipatnam', 'Arunodaya', 'Pathapatnam',
                'Paderu', 'Meyers Villa', 'Nalkonda'
            ],
            'BANGALORE' => [
                'Prajyothi Welfare Centre', 'Gadag', 'Kurnool', 'Madurai',
                'Madhavaram', 'Belgaum', 'Kadirepalli', 'Munambam', 'Kuderu',
                'Tuticorin', 'Palakkad', 'Thejas', 'Sannenahalli', 'Solavidhyapuram',
                'Kozhenchery', 'Nadavayal', 'Kodaikanal', 'PWC Bangalore', 'Taragarh', 'Chennai'
            ],
            'DIVYODAYA' => [
                'Divyodaya'
            ],
            'INDONESIA' => [
                'Mausambi'
            ],
            'EAST TIMOR' => [
                'Luro'
            ],
            'EAST AFRICA' => [
                'Tabora', 'Monduli Chini', 'Monduli Juu', 'Maji Ya Chai', 'Kahama',
                'Kihonda', 'Tungi', 'Kiambu - Kenya', 'Kericho - Kenya', 'Sirimba - Kenya',
                'Iganga - Uganda'
            ],
            'GENERALATE' => [],
            'LUZERN' => [],
        ];

        $totalCenters = 0;

        foreach ($centersMap as $provinceName => $centers) {
            // Get province by name (case-insensitive)
            $province = Province::whereRaw('UPPER(name) = ?', [strtoupper($provinceName)])->first();

            if (!$province) {
                $this->command->warn("Province '{$provinceName}' not found. Skipping centers.");
                continue;
            }

            // Create centers for this province
            foreach ($centers as $centerName) {
                Center::firstOrCreate(
                    [
                        'province_id' => $province->id,
                        'name' => $centerName
                    ],
                    [
                        'is_active' => true
                    ]
                );
                $totalCenters++;
            }
        }

        $this->command->info('Centers seeded successfully: ' . $totalCenters . ' centers');
        $this->command->info('Total centers in database: ' . Center::count());
    }
}
