<?php

namespace App\Http\Controllers\Projects\IAH;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\IAH\ProjectIAHDocuments;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class IAHDocumentsController extends Controller
{
    // Store documents for a project
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing IAH documents', ['project_id' => $projectId]);

            $documents = ProjectIAHDocuments::where('project_id', $projectId)->first() ?: new ProjectIAHDocuments();
            $documents->project_id = $projectId;

            // Handle Aadhar Copy
            if ($request->hasFile('aadhar_copy')) {
                if ($documents->aadhar_copy) {
                    Storage::delete($documents->aadhar_copy);
                }
                $documents->aadhar_copy = $request->file('aadhar_copy')->store('documents');
            }

            // Handle Request Letter
            if ($request->hasFile('request_letter')) {
                if ($documents->request_letter) {
                    Storage::delete($documents->request_letter);
                }
                $documents->request_letter = $request->file('request_letter')->store('documents');
            }

            // Handle Medical Reports
            if ($request->hasFile('medical_reports')) {
                if ($documents->medical_reports) {
                    Storage::delete($documents->medical_reports);
                }
                $documents->medical_reports = $request->file('medical_reports')->store('documents');
            }

            // Handle Other Supporting Documents
            if ($request->hasFile('other_docs')) {
                if ($documents->other_docs) {
                    Storage::delete($documents->other_docs);
                }
                $documents->other_docs = $request->file('other_docs')->store('documents');
            }

            $documents->save();

            DB::commit();
            Log::info('IAH documents saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IAH documents saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving IAH documents', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save IAH documents.'], 500);
        }
    }

    // Show documents for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching IAH documents', ['project_id' => $projectId]);

            $documents = ProjectIAHDocuments::where('project_id', $projectId)->firstOrFail();
            return response()->json($documents, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching IAH documents', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch IAH documents.'], 500);
        }
    }

    // Edit documents for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing IAH documents', ['project_id' => $projectId]);

            $documents = ProjectIAHDocuments::where('project_id', $projectId)->firstOrFail();

            // Return the data directly
            return $documents;
        } catch (\Exception $e) {
            Log::error('Error editing IAH documents', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Update documents for a project
    public function update(Request $request, $projectId)
    {
        return $this->store($request, $projectId); // Reuse the store logic for update
    }

    // Delete documents for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting IAH documents', ['project_id' => $projectId]);

            $documents = ProjectIAHDocuments::where('project_id', $projectId)->firstOrFail();

            if ($documents->aadhar_copy) {
                Storage::delete($documents->aadhar_copy);
            }
            if ($documents->request_letter) {
                Storage::delete($documents->request_letter);
            }
            if ($documents->medical_reports) {
                Storage::delete($documents->medical_reports);
            }
            if ($documents->other_docs) {
                Storage::delete($documents->other_docs);
            }

            $documents->delete();

            DB::commit();
            Log::info('IAH documents deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'IAH documents deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting IAH documents', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete IAH documents.'], 500);
        }
    }
}
