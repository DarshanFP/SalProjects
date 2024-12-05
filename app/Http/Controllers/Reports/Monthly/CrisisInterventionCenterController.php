<?php

namespace App\Http\Controllers\Reports\Monthly;

use App\Http\Controllers\Controller;
use App\Models\Reports\Monthly\RQWDInmatesProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CrisisInterventionCenterController extends Controller
{
    /**
     * Handle storing or updating Crisis Intervention Center data .
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $report_id
     * @return void
     */
    public function handleInmateProfiles(Request $request, $report_id)
    {
        Log::info('Handling Inmate Profile for report_id:', ['report_id' => $report_id]);

        $validatedData = $request->validate([
            'inmates.children_below_18.unmarried' => 'nullable|numeric',
            'inmates.children_below_18.married' => 'nullable|numeric',
            'inmates.children_below_18.divorcee' => 'nullable|numeric',
            'inmates.children_below_18.deserted' => 'nullable|numeric',
            'inmates.children_below_18.other_status' => 'nullable|string|max:255',
            'inmates.children_below_18.other_count' => 'nullable|numeric',

            'inmates.women_18_30.unmarried' => 'nullable|numeric',
            'inmates.women_18_30.married' => 'nullable|numeric',
            'inmates.women_18_30.divorcee' => 'nullable|numeric',
            'inmates.women_18_30.deserted' => 'nullable|numeric',
            'inmates.women_18_30.other_status' => 'nullable|string|max:255',
            'inmates.women_18_30.other_count' => 'nullable|numeric',

            'inmates.women_31_50.unmarried' => 'nullable|numeric',
            'inmates.women_31_50.married' => 'nullable|numeric',
            'inmates.women_31_50.divorcee' => 'nullable|numeric',
            'inmates.women_31_50.deserted' => 'nullable|numeric',
            'inmates.women_31_50.other_status' => 'nullable|string|max:255',
            'inmates.women_31_50.other_count' => 'nullable|numeric',

            'inmates.women_above_50.unmarried' => 'nullable|numeric',
            'inmates.women_above_50.married' => 'nullable|numeric',
            'inmates.women_above_50.divorcee' => 'nullable|numeric',
            'inmates.women_above_50.deserted' => 'nullable|numeric',
            'inmates.women_above_50.other_status' => 'nullable|string|max:255',
            'inmates.women_above_50.other_count' => 'nullable|numeric',
        ]);

        // Clear existing inmate profiles for the report
        RQWDInmatesProfile::where('report_id', $report_id)->delete();

        // Define categories and statuses
        $categories = [
            'Children below 18 yrs' => [
                'unmarried' => $validatedData['inmates']['children_below_18']['unmarried'] ?? 0,
                'married' => $validatedData['inmates']['children_below_18']['married'] ?? 0,
                'divorcee' => $validatedData['inmates']['children_below_18']['divorcee'] ?? 0,
                'deserted' => $validatedData['inmates']['children_below_18']['deserted'] ?? 0,
                $validatedData['inmates']['children_below_18']['other_status'] ?? 'others' => $validatedData['inmates']['children_below_18']['other_count'] ?? 0,
            ],
            'Women between 18 – 30 years' => [
                'unmarried' => $validatedData['inmates']['women_18_30']['unmarried'] ?? 0,
                'married' => $validatedData['inmates']['women_18_30']['married'] ?? 0,
                'divorcee' => $validatedData['inmates']['women_18_30']['divorcee'] ?? 0,
                'deserted' => $validatedData['inmates']['women_18_30']['deserted'] ?? 0,
                $validatedData['inmates']['women_18_30']['other_status'] ?? 'others' => $validatedData['inmates']['women_18_30']['other_count'] ?? 0,
            ],
            'Women between 31 – 50 years' => [
                'unmarried' => $validatedData['inmates']['women_31_50']['unmarried'] ?? 0,
                'married' => $validatedData['inmates']['women_31_50']['married'] ?? 0,
                'divorcee' => $validatedData['inmates']['women_31_50']['divorcee'] ?? 0,
                'deserted' => $validatedData['inmates']['women_31_50']['deserted'] ?? 0,
                $validatedData['inmates']['women_31_50']['other_status'] ?? 'others' => $validatedData['inmates']['women_31_50']['other_count'] ?? 0,
            ],
            'Women above 50' => [
                'unmarried' => $validatedData['inmates']['women_above_50']['unmarried'] ?? 0,
                'married' => $validatedData['inmates']['women_above_50']['married'] ?? 0,
                'divorcee' => $validatedData['inmates']['women_above_50']['divorcee'] ?? 0,
                'deserted' => $validatedData['inmates']['women_above_50']['deserted'] ?? 0,
                $validatedData['inmates']['women_above_50']['other_status'] ?? 'others' => $validatedData['inmates']['women_above_50']['other_count'] ?? 0,
            ],
        ];

        $overallTotal = 0; // Grand total across all categories

        foreach ($categories as $ageCategory => $statuses) {
            $categoryTotal = 0;
            foreach ($statuses as $status => $number) {
                $categoryTotal += $number;
                RQWDInmatesProfile::updateOrCreate(
                    [
                        'report_id' => $report_id,
                        'age_category' => $ageCategory,
                        'status' => $status,
                    ],
                    [
                        'number' => $number,
                        'total' => null, // individual entry, no total here
                    ]
                );
                Log::info('Inmate Profile Updated:', ['age_category' => $ageCategory, 'status' => $status, 'number' => $number]);
            }

            // Store the total for each age category as a separate entry
            RQWDInmatesProfile::updateOrCreate(
                [
                    'report_id' => $report_id,
                    'age_category' => $ageCategory,
                    'status' => 'Total',
                ],
                [
                    'number' => $categoryTotal,
                ]
            );

            // Add to overall total
            $overallTotal += $categoryTotal;
        }

        // Optionally store the overall total if needed
        RQWDInmatesProfile::updateOrCreate(
            [
                'report_id' => $report_id,
                'age_category' => 'All Categories',
                'status' => 'Total',
            ],
            [
                'number' => $overallTotal,
            ]
        );

        Log::info('Overall Inmate Profile Total:', ['overall_total' => $overallTotal]);
    }

    /**
     * Retrieve inmate profiles as a collection.
     *
     * @param  int  $report_id
     * @return \Illuminate\Support\Collection
     */
    public function getInmateProfiles($report_id)
    {
        return RQWDInmatesProfile::where('report_id', $report_id)->get();
    }
}
