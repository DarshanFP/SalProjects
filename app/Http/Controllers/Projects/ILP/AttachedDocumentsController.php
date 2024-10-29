<?php

namespace App\Http\Controllers\Projects\ILP;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\OldProjects\ILP\ProjectILPAttachedDocuments;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttachedDocumentsController extends Controller
{
    // Store or update attached documents
    public function store(Request $request, $projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Storing ILP Attached Documents', ['project_id' => $projectId]);

            $documents = ProjectILPAttachedDocuments::where('project_id', $projectId)->first();

            $aadharPath = $documents && $request->hasFile('aadhar_doc')
                ? $request->file('aadhar_doc')->store('ilp_documents/aadhar')
                : $documents->aadhar_doc;

            $requestLetterPath = $documents && $request->hasFile('request_letter_doc')
                ? $request->file('request_letter_doc')->store('ilp_documents/request_letters')
                : $documents->request_letter_doc;

            $quotationPath = $documents && $request->hasFile('purchase_quotation_doc')
                ? $request->file('purchase_quotation_doc')->store('ilp_documents/purchase_quotations')
                : $documents->purchase_quotation_doc;

            $otherDocPath = $documents && $request->hasFile('other_doc')
                ? $request->file('other_doc')->store('ilp_documents/other_docs')
                : $documents->other_doc;

            // Create or update attached documents
            ProjectILPAttachedDocuments::updateOrCreate(
                ['project_id' => $projectId],
                [
                    'aadhar_doc' => $aadharPath,
                    'request_letter_doc' => $requestLetterPath,
                    'purchase_quotation_doc' => $quotationPath,
                    'other_doc' => $otherDocPath,
                ]
            );

            DB::commit();
            Log::info('ILP Attached Documents saved successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Attached Documents saved successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving ILP Attached Documents', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save Attached Documents.'], 500);
        }
    }

    // Show attached documents for a project
    public function show($projectId)
    {
        try {
            Log::info('Fetching ILP Attached Documents', ['project_id' => $projectId]);

            $documents = ProjectILPAttachedDocuments::where('project_id', $projectId)->first();
            return response()->json($documents, 200);
        } catch (\Exception $e) {
            Log::error('Error fetching ILP Attached Documents', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch Attached Documents.'], 500);
        }
    }

    // Edit attached documents for a project
    public function edit($projectId)
    {
        try {
            Log::info('Editing ILP Attached Documents', ['project_id' => $projectId]);

            $documents = ProjectILPAttachedDocuments::where('project_id', $projectId)->first();
            return view('projects.partials.Edit.ILP.attached_docs', compact('documents'));
        } catch (\Exception $e) {
            Log::error('Error editing ILP Attached Documents', ['error' => $e->getMessage()]);
            return null;
        }
    }

    // Delete attached documents for a project
    public function destroy($projectId)
    {
        DB::beginTransaction();
        try {
            Log::info('Deleting ILP Attached Documents', ['project_id' => $projectId]);

            $documents = ProjectILPAttachedDocuments::where('project_id', $projectId)->first();
            if ($documents) {
                Storage::delete([$documents->aadhar_doc, $documents->request_letter_doc, $documents->purchase_quotation_doc, $documents->other_doc]);
                $documents->delete();
            }

            DB::commit();
            Log::info('ILP Attached Documents deleted successfully', ['project_id' => $projectId]);
            return response()->json(['message' => 'Attached Documents deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error deleting ILP Attached Documents', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete Attached Documents.'], 500);
        }
    }
}
