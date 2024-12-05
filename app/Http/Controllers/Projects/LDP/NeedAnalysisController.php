<?php

namespace App\Http\Controllers\Projects\LDP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\LDP\ProjectLDPNeedAnalysis;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class NeedAnalysisController extends Controller
{
    // Store or update the Need Analysis
    public function store(Request $request, $projectId)
    {
        $request->validate([
            'need_analysis_file' => 'nullable|file|mimes:pdf,doc,docx|max:2048', // Validation for file type and size
        ]);

        try {
            Log::info('Storing Need Analysis', ['project_id' => $projectId]);

            // Handle file upload
            if ($request->hasFile('need_analysis_file')) {
                $filePath = $request->file('need_analysis_file')->store('ldp/need_analysis', 'public');
            }

            // Save or update the need analysis record
            ProjectLDPNeedAnalysis::updateOrCreate(
                ['project_id' => $projectId],
                ['document_path' => $filePath]
            );

            Log::info('Need Analysis saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Need Analysis saved successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Error saving Need Analysis', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save Need Analysis.'], 500);
        }
    }

    // Show the Need Analysis for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching Need Analysis', ['project_id' => $projectId]);

            $needAnalysis = ProjectLDPNeedAnalysis::where('project_id', $projectId)->first();

            if (!$needAnalysis) {
                Log::warning('No Need Analysis data found', ['project_id' => $projectId]);
                return null; // Return null if no data exists
            }

            return $needAnalysis; // Return the Need Analysis model directly
        } catch (\Exception $e) {
            Log::error('Error fetching Need Analysis', ['error' => $e->getMessage()]);
            return null; // Return null if an error occurs
        }
    }


    // Edit the Need Analysis for a project
    // public function edit($projectId)
    // {
    //     try {
    //         Log::info('Editing Need Analysis', ['project_id' => $projectId]);

    //         $needAnalysis = ProjectLDPNeedAnalysis::where('project_id', $projectId)->first();
    //         $document_path = $needAnalysis->document_path ?? null;

    //         return $needAnalysis;
    //     } catch (\Exception $e) {
    //         Log::error('Error editing Need Analysis', ['error' => $e->getMessage()]);
    //         return null;
    //     }
    // }
    public function edit($projectId)
    {
        try {
            Log::info('Editing Need Analysis', ['project_id' => $projectId]);

            $needAnalysis = ProjectLDPNeedAnalysis::where('project_id', $projectId)->first();
            return $needAnalysis;
        } catch (\Exception $e) {
            Log::error('Error editing Need Analysis', ['error' => $e->getMessage()]);
            return null;
        }
    }

    public function update(Request $request, $projectId)
    {
        $request->validate([
            'need_analysis_file' => 'nullable|file|mimes:pdf,doc,docx|max:2048', // Validation for file type and size
        ]);

        try {
            Log::info('Updating Need Analysis', ['project_id' => $projectId]);

            // Fetch existing need analysis record
            $needAnalysis = ProjectLDPNeedAnalysis::where('project_id', $projectId)->first();

            // Handle file upload
            if ($request->hasFile('need_analysis_file')) {
                // Delete the old file if it exists
                if ($needAnalysis && $needAnalysis->document_path) {
                    Storage::disk('public')->delete($needAnalysis->document_path);
                }

                // Store the new file
                $filePath = $request->file('need_analysis_file')->store('ldp/need_analysis', 'public');
            } else {
                // Keep the existing file path if no new file is uploaded
                $filePath = $needAnalysis ? $needAnalysis->document_path : null;
            }

            // Save or update the need analysis record
            ProjectLDPNeedAnalysis::updateOrCreate(
                ['project_id' => $projectId],
                ['document_path' => $filePath]
            );

            Log::info('Need Analysis updated successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Need Analysis updated successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Error updating Need Analysis', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update Need Analysis.'], 500);
        }
    }


    // Delete the Need Analysis for a project
    public function destroy($projectId)
    {
        try {
            Log::info('Deleting Need Analysis', ['project_id' => $projectId]);

            $needAnalysis = ProjectLDPNeedAnalysis::where('project_id', $projectId)->first();
            if ($needAnalysis) {
                // Delete the file from storage
                Storage::disk('public')->delete($needAnalysis->document_path);
                $needAnalysis->delete();
            }

            Log::info('Need Analysis deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Need Analysis deleted successfully.'], 200);
        } catch (\Exception $e) {
            Log::error('Error deleting Need Analysis', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete Need Analysis.'], 500);
        }
    }
}
