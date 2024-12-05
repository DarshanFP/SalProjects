<?php

namespace App\Http\Controllers\Reports\Monthly;

use App\Http\Controllers\Controller;
use App\Models\Reports\Monthly\RQSTTraineeProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ResidentialSkillTrainingController extends Controller
{
    /**
     * Handle storing or updating Residential Skill Training data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $report_id
     * @return void
     */
    public function handleTraineeProfiles(Request $request, $report_id)
    {
        Log::info('Handling Trainee Profile for report_id:', ['report_id' => $report_id]);

        $validatedData = $request->validate([
            'education.below_9' => 'nullable|numeric',
            'education.class_10_fail' => 'nullable|numeric',
            'education.class_10_pass' => 'nullable|numeric',
            'education.intermediate' => 'nullable|numeric',
            'education.above_intermediate' => 'nullable|numeric',
            'education.other' => 'nullable|string|max:255',
            'education.other_count' => 'nullable|numeric',
            'education.total' => 'nullable|numeric',
        ]);

        // Clear existing trainee profiles for the report
        RQSTTraineeProfile::where('report_id', $report_id)->delete();

        // Store each category
        $categories = [
            'Below 9th standard' => $validatedData['education']['below_9'] ?? 0,
            '10th class failed' => $validatedData['education']['class_10_fail'] ?? 0,
            '10th class passed' => $validatedData['education']['class_10_pass'] ?? 0,
            'Intermediate' => $validatedData['education']['intermediate'] ?? 0,
            'Intermediate and above' => $validatedData['education']['above_intermediate'] ?? 0,
            $validatedData['education']['other'] ?? 'Other' => $validatedData['education']['other_count'] ?? 0,
        ];

        foreach ($categories as $educationCategory => $number) {
            RQSTTraineeProfile::updateOrCreate(
                [
                    'report_id' => $report_id,
                    'education_category' => $educationCategory,
                ],
                [
                    'number' => $number,
                ]
            );
            Log::info('Trainee Profile Updated:', ['education_category' => $educationCategory, 'number' => $number]);
        }

        // Store the total count as a separate entry if needed
        RQSTTraineeProfile::updateOrCreate(
            [
                'report_id' => $report_id,
                'education_category' => 'Total',
            ],
            [
                'number' => $validatedData['education']['total'] ?? 0,
            ]
        );
    }



    /**
     * Retrieve trainee profiles as a collection.
     *
     * @param  int  $report_id
     * @return \Illuminate\Support\Collection
     */
    public function getTraineeProfiles($report_id)
    {

        return RQSTTraineeProfile::where('report_id', $report_id)->get();

    }
}
