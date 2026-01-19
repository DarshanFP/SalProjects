<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\OldProjects\IES\ProjectIESAttachments;
use App\Models\OldProjects\IES\ProjectIESAttachmentFile;
use App\Models\OldProjects\IIES\ProjectIIESAttachments;
use App\Models\OldProjects\IIES\ProjectIIESAttachmentFile;
use App\Models\OldProjects\IAH\ProjectIAHDocuments;
use App\Models\OldProjects\IAH\ProjectIAHDocumentFile;
use App\Models\OldProjects\ILP\ProjectILPAttachedDocuments;
use App\Models\OldProjects\ILP\ProjectILPDocumentFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration migrates existing single-file-per-field attachments
     * to the new multiple-files-per-field structure.
     */
    public function up(): void
    {
        Log::info('Starting migration of existing attachments to multiple files structure');

        // Migrate IES Attachments
        $this->migrateIESAttachments();
        
        // Migrate IIES Attachments
        $this->migrateIIESAttachments();
        
        // Migrate IAH Documents
        $this->migrateIAHDocuments();
        
        // Migrate ILP Documents
        $this->migrateILPDocuments();

        Log::info('Completed migration of existing attachments to multiple files structure');
    }

    /**
     * Reverse the migrations.
     * 
     * Note: This will NOT restore the old structure, as we cannot determine
     * which file to keep if multiple files exist. This is a one-way migration.
     */
    public function down(): void
    {
        Log::warning('Rollback of attachment migration is not supported. This is a one-way migration.');
        // Intentionally left empty - this is a one-way migration
    }

    /**
     * Migrate IES Attachments
     */
    private function migrateIESAttachments(): void
    {
        $fields = [
            'aadhar_card',
            'fee_quotation',
            'scholarship_proof',
            'medical_confirmation',
            'caste_certificate',
            'self_declaration',
            'death_certificate',
            'request_letter'
        ];

        $attachments = ProjectIESAttachments::all();

        foreach ($attachments as $attachment) {
            foreach ($fields as $field) {
                if (!empty($attachment->$field)) {
                    // Check if file already migrated
                    $existingFile = ProjectIESAttachmentFile::where('IES_attachment_id', $attachment->IES_attachment_id)
                        ->where('field_name', $field)
                        ->where('file_path', $attachment->$field)
                        ->first();

                    if (!$existingFile && Storage::disk('public')->exists($attachment->$field)) {
                        ProjectIESAttachmentFile::create([
                            'IES_attachment_id' => $attachment->IES_attachment_id,
                            'project_id' => $attachment->project_id,
                            'field_name' => $field,
                            'file_path' => $storagePath, // Store without 'public/' prefix
                            'file_name' => basename($storagePath),
                            'description' => null,
                            'serial_number' => '01',
                            'public_url' => Storage::url($storagePath),
                        ]);

                        Log::info('Migrated IES attachment', [
                            'project_id' => $attachment->project_id,
                            'field' => $field,
                            'file_path' => $storagePath
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Migrate IIES Attachments
     */
    private function migrateIIESAttachments(): void
    {
        $fields = [
            'iies_aadhar_card',
            'iies_fee_quotation',
            'iies_scholarship_proof',
            'iies_medical_confirmation',
            'iies_caste_certificate',
            'iies_self_declaration',
            'iies_death_certificate',
            'iies_request_letter'
        ];

        $attachments = ProjectIIESAttachments::all();

        foreach ($attachments as $attachment) {
            foreach ($fields as $field) {
                if (!empty($attachment->$field)) {
                    // Check if file already migrated
                    $existingFile = ProjectIIESAttachmentFile::where('IIES_attachment_id', $attachment->IIES_attachment_id)
                        ->where('field_name', $field)
                        ->where('file_path', $attachment->$field)
                        ->first();

                    // Remove 'public/' prefix if present
                    $filePath = $attachment->$field;
                    $storagePath = str_replace('public/', '', $filePath);
                    
                    if (!$existingFile && Storage::disk('public')->exists($storagePath)) {
                        ProjectIIESAttachmentFile::create([
                            'IIES_attachment_id' => $attachment->IIES_attachment_id,
                            'project_id' => $attachment->project_id,
                            'field_name' => $field,
                            'file_path' => $storagePath, // Store without 'public/' prefix
                            'file_name' => basename($storagePath),
                            'description' => null,
                            'serial_number' => '01',
                            'public_url' => Storage::url($storagePath),
                        ]);

                        Log::info('Migrated IIES attachment', [
                            'project_id' => $attachment->project_id,
                            'field' => $field,
                            'file_path' => $storagePath
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Migrate IAH Documents
     */
    private function migrateIAHDocuments(): void
    {
        $fields = [
            'aadhar_copy',
            'request_letter',
            'medical_reports',
            'other_docs'
        ];

        $documents = ProjectIAHDocuments::all();

        foreach ($documents as $document) {
            foreach ($fields as $field) {
                if (!empty($document->$field)) {
                    // Check if file already migrated
                    $existingFile = ProjectIAHDocumentFile::where('IAH_doc_id', $document->IAH_doc_id)
                        ->where('field_name', $field)
                        ->where('file_path', $document->$field)
                        ->first();

                    // Remove 'public/' prefix if present
                    $filePath = $document->$field;
                    $storagePath = str_replace('public/', '', $filePath);
                    
                    if (!$existingFile && Storage::disk('public')->exists($storagePath)) {
                        ProjectIAHDocumentFile::create([
                            'IAH_doc_id' => $document->IAH_doc_id,
                            'project_id' => $document->project_id,
                            'field_name' => $field,
                            'file_path' => $storagePath, // Store without 'public/' prefix
                            'file_name' => basename($storagePath),
                            'description' => null,
                            'serial_number' => '01',
                            'public_url' => Storage::url($storagePath),
                        ]);

                        Log::info('Migrated IAH document', [
                            'project_id' => $document->project_id,
                            'field' => $field,
                            'file_path' => $storagePath
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Migrate ILP Documents
     */
    private function migrateILPDocuments(): void
    {
        $fields = [
            'aadhar_doc',
            'request_letter_doc',
            'purchase_quotation_doc',
            'other_doc'
        ];

        $documents = ProjectILPAttachedDocuments::all();

        foreach ($documents as $document) {
            foreach ($fields as $field) {
                if (!empty($document->$field)) {
                    // Check if file already migrated
                    $existingFile = ProjectILPDocumentFile::where('ILP_doc_id', $document->ILP_doc_id)
                        ->where('field_name', $field)
                        ->where('file_path', $document->$field)
                        ->first();

                    // Remove 'public/' prefix if present
                    $filePath = $document->$field;
                    $storagePath = str_replace('public/', '', $filePath);
                    
                    if (!$existingFile && Storage::disk('public')->exists($storagePath)) {
                        ProjectILPDocumentFile::create([
                            'ILP_doc_id' => $document->ILP_doc_id,
                            'project_id' => $document->project_id,
                            'field_name' => $field,
                            'file_path' => $storagePath, // Store without 'public/' prefix
                            'file_name' => basename($storagePath),
                            'description' => null,
                            'serial_number' => '01',
                            'public_url' => Storage::url($storagePath),
                        ]);

                        Log::info('Migrated ILP document', [
                            'project_id' => $document->project_id,
                            'field' => $field,
                            'file_path' => $storagePath
                        ]);
                    }
                }
            }
        }
    }
};
