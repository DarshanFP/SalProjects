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
            'need_analysis_file' => 'required|file|mimes:pdf,doc,docx|max:2048', // Validation for file type and size
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
            return response()->json($needAnalysis, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching Need Analysis', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch Need Analysis.'], 500);
        }
    }

    // Edit the Need Analysis for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing Need Analysis', ['project_id' => $projectId]);

            $needAnalysis = ProjectLDPNeedAnalysis::where('project_id', $projectId)->first();
            $document_path = $needAnalysis->document_path ?? null;

            return view('projects.partials.Edit.LDP.need_analysis', compact('document_path'));
        } catch (\Exception $e) {
            Log::error('Error editing Need Analysis', ['error' => $e->getMessage()]);
            return null;
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
