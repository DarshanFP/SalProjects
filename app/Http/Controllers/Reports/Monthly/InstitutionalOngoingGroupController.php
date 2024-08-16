<?php

namespace App\Http\Controllers\Reports\Monthly;

use App\Http\Controllers\Controller;
use App\Models\Reports\Monthly\RQISAgeProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstitutionalOngoingGroupController extends Controller
{
    /**
     * Handle storing or updating Institutional Ongoing Group data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $report_id
     * @return void
     */
    public function handleInstitutionalGroup(Request $request, $report_id)
    {
        Log::info('Handling Institutional Ongoing Group for report_id:', ['report_id' => $report_id]);

        $validatedData = $request->validate([
            'education' => 'nullable|array',
            'education.*' => 'nullable|string|max:255',
            'up_to_previous_year' => 'nullable|array',
            'up_to_previous_year.*' => 'nullable|integer',
            'present_academic_year' => 'nullable|array',
            'present_academic_year.*' => 'nullable|integer',
            'total_up_to_previous_below_5' => 'nullable|integer',
            'total_present_academic_below_5' => 'nullable|integer',
            'total_up_to_previous_6_10' => 'nullable|integer',
            'total_present_academic_6_10' => 'nullable|integer',
            'total_up_to_previous_11_15' => 'nullable|integer',
            'total_present_academic_11_15' => 'nullable|integer',
            'total_up_to_previous_16_above' => 'nullable|integer',
            'total_present_academic_16_above' => 'nullable|integer',
            'grand_total_up_to_previous' => 'nullable|integer',
            'grand_total_present_academic' => 'nullable|integer',
        ]);

        // Clear existing age profiles for the report
        RQISAgeProfile::where('report_id', $report_id)->delete();

        // Define age groups
        $ageGroups = [
            'Children below 5 years' => [
                'education' => [
                    $validatedData['education'][0] ?? 'Bridge course',
                    $validatedData['education'][1] ?? 'If any other, mention here',
                ],
                'up_to_previous_year' => [
                    $validatedData['up_to_previous_year'][0] ?? 0,
                    $validatedData['up_to_previous_year'][1] ?? 0,
                ],
                'present_academic_year' => [
                    $validatedData['present_academic_year'][0] ?? 0,
                    $validatedData['present_academic_year'][1] ?? 0,
                ],
                'total_up_to_previous' => $validatedData['total_up_to_previous_below_5'] ?? 0,
                'total_present_academic' => $validatedData['total_present_academic_below_5'] ?? 0,
            ],
            'Children between 6 to 10 years' => [
                'education' => [
                    $validatedData['education'][2] ?? 'Primary school',
                    $validatedData['education'][3] ?? 'If any other, mention here',
                ],
                'up_to_previous_year' => [
                    $validatedData['up_to_previous_year'][2] ?? 0,
                    $validatedData['up_to_previous_year'][3] ?? 0,
                ],
                'present_academic_year' => [
                    $validatedData['present_academic_year'][2] ?? 0,
                    $validatedData['present_academic_year'][3] ?? 0,
                ],
                'total_up_to_previous' => $validatedData['total_up_to_previous_6_10'] ?? 0,
                'total_present_academic' => $validatedData['total_present_academic_6_10'] ?? 0,
            ],
            'Children between 11 to 15 years' => [
                'education' => [
                    $validatedData['education'][4] ?? 'Secondary school',
                    $validatedData['education'][5] ?? 'If any other, mention here',
                ],
                'up_to_previous_year' => [
                    $validatedData['up_to_previous_year'][4] ?? 0,
                    $validatedData['up_to_previous_year'][5] ?? 0,
                ],
                'present_academic_year' => [
                    $validatedData['present_academic_year'][4] ?? 0,
                    $validatedData['present_academic_year'][5] ?? 0,
                ],
                'total_up_to_previous' => $validatedData['total_up_to_previous_11_15'] ?? 0,
                'total_present_academic' => $validatedData['total_present_academic_11_15'] ?? 0,
            ],
            '16 and above' => [
                'education' => [
                    $validatedData['education'][6] ?? 'Undergraduate',
                    $validatedData['education'][7] ?? 'If any other, mention here',
                ],
                'up_to_previous_year' => [
                    $validatedData['up_to_previous_year'][6] ?? 0,
                    $validatedData['up_to_previous_year'][7] ?? 0,
                ],
                'present_academic_year' => [
                    $validatedData['present_academic_year'][6] ?? 0,
                    $validatedData['present_academic_year'][7] ?? 0,
                ],
                'total_up_to_previous' => $validatedData['total_up_to_previous_16_above'] ?? 0,
                'total_present_academic' => $validatedData['total_present_academic_16_above'] ?? 0,
            ],
        ];

        // Insert age profiles and their totals
        foreach ($ageGroups as $ageCategory => $data) {
            $categoryTotalPrevious = 0;
            $categoryTotalPresent = 0;

            foreach ($data['education'] as $index => $education) {
                RQISAgeProfile::create([
                    'report_id' => $report_id,
                    'age_group' => $ageCategory,
                    'education' => $education,
                    'up_to_previous_year' => $data['up_to_previous_year'][$index] ?? 0,
                    'present_academic_year' => $data['present_academic_year'][$index] ?? 0,
                ]);

                $categoryTotalPrevious += $data['up_to_previous_year'][$index] ?? 0;
                $categoryTotalPresent += $data['present_academic_year'][$index] ?? 0;
            }

            // Store the totals for each age category
            RQISAgeProfile::create([
                'report_id' => $report_id,
                'age_group' => $ageCategory,
                'education' => 'Total',
                'up_to_previous_year' => $data['total_up_to_previous'],
                'present_academic_year' => $data['total_present_academic'],
            ]);

            Log::info('Age Category Total Updated:', [
                'age_category' => $ageCategory,
                'total_up_to_previous' => $data['total_up_to_previous'],
                'total_present_academic' => $data['total_present_academic'],
            ]);
        }

        // Store the grand totals
        RQISAgeProfile::create([
            'report_id' => $report_id,
            'age_group' => 'All Categories',
            'education' => 'Grand Total',
            'up_to_previous_year' => $validatedData['grand_total_up_to_previous'],
            'present_academic_year' => $validatedData['grand_total_present_academic'],
        ]);

        Log::info('Overall Age Profile Grand Total:', [
            'grand_total_up_to_previous' => $validatedData['grand_total_up_to_previous'],
            'grand_total_present_academic' => $validatedData['grand_total_present_academic'],
        ]);
    }

    /**
     * Retrieve age profile data as a collection.
     *
     * @param  int  $report_id
     * @return \Illuminate\Support\Collection
     */
    public function getAgeProfiles($report_id)
    {
        return RQISAgeProfile::where('report_id', $report_id)->get();
    }
}
