<?php

namespace App\Models\OldProjects\ILP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * 
 *
 * @property int $id
 * @property string $ILP_doc_id
 * @property string $project_id
 * @property string|null $aadhar_doc
 * @property string|null $request_letter_doc
 * @property string|null $purchase_quotation_doc
 * @property string|null $other_doc
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments whereAadharDoc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments whereILPDocId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments whereOtherDoc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments wherePurchaseQuotationDoc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments whereRequestLetterDoc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPAttachedDocuments whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectILPAttachedDocuments extends Model
{
    use HasFactory;

    protected $table = 'project_ILP_attached_docs';

    protected $fillable = [
        'ILP_doc_id',
        'project_id',
        'aadhar_doc',
        'request_letter_doc',
        'purchase_quotation_doc',
        'other_doc',
    ];

    protected static function boot()
    {
        parent::boot();

        // Generate a unique ILP_doc_id when creating a new record
        static::creating(function ($model) {
            $model->ILP_doc_id = $model->generateILPDocId();
        });

        // Delete associated files when a record is deleted
        static::deleting(function ($model) {
            $model->deleteAttachments();
        });
    }

    /**
     * Generate a unique ILP Document ID.
     */
    private function generateILPDocId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->ILP_doc_id, -4)) + 1 : 1;
        return 'ILP-DOC-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Define a relationship with the Project model.
     * This links the `project_id` in this model to the `project_id` in the `Project` model.
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    /**
     * Get all files for this document record
     */
    public function files()
    {
        return $this->hasMany(ProjectILPDocumentFile::class, 'ILP_doc_id', 'ILP_doc_id');
    }

    /**
     * Get files for a specific field
     */
    public function getFilesForField($fieldName)
    {
        return $this->files()->where('field_name', $fieldName)->orderBy('serial_number')->get();
    }

    /**
     * Handle the file upload and storage process for the ILP attached documents.
     */
    public static function handleDocuments($request, $projectId)
    {
        \Log::info("handleDocuments() called for project: {$projectId}");

        $fields = [
            'aadhar_doc' => 'aadhar',
            'request_letter_doc' => 'request_letter',
            'purchase_quotation_doc' => 'purchase_quotation',
            'other_doc' => 'other',
        ];

        // Example: stored in storage/app/public/project_attachments/ILP/ILA-0013
        $projectDir = "project_attachments/ILP/{$projectId}";
        Storage::disk('public')->makeDirectory($projectDir, 0755, true);

        $documents = self::updateOrCreate(['project_id' => $projectId], []);
        \Log::info("Document record found or created.", ['record_id' => $documents->id]);

        $uploadedFiles = []; // Track uploaded files for cleanup on error

        try {
            foreach ($fields as $field => $shortName) {
                // Support both single file and array of files
                if ($request->hasFile("attachments.$field")) {
                    $files = is_array($request->file("attachments.$field")) 
                        ? $request->file("attachments.$field") 
                        : [$request->file("attachments.$field")];
                    
                    // Get user-provided names if any
                    $fileNames = $request->input("attachments.{$field}_names", []);
                    $descriptions = $request->input("attachments.{$field}_descriptions", []);

                    foreach ($files as $index => $file) {
                        if ($file && $file->isValid()) {
                            // Validate file type
                            if (!self::isValidFileType($file)) {
                                \Log::error('Invalid file type for ILP document', [
                                    'field' => $field,
                                    'mime_type' => $file->getMimeType(),
                                    'extension' => $file->getClientOriginalExtension()
                                ]);
                                $allowedTypes = config('attachments.allowed_file_types.image_only');
                                $typesList = implode(', ', array_map('strtoupper', $allowedTypes['extensions']));
                                $errorMsg = str_replace(':types', $typesList, config('attachments.messages.file_type_error'));
                                throw new \Exception("Invalid file type for {$field}. {$errorMsg}");
                            }

                            // Validate file size (7MB max - allows buffer for files slightly over 5MB)
                            $maxSize = config('attachments.max_file_size.server_bytes');
                            if ($file->getSize() > $maxSize) {
                                $maxSizeMB = config('attachments.max_file_size.display_mb');
                                $errorMsg = str_replace(':size', $maxSizeMB, config('attachments.messages.file_size_error'));
                                throw new \Exception("File size exceeds limit for {$field}. {$errorMsg}");
                            }

                            // Generate file name using helper
                            $userProvidedName = $fileNames[$index] ?? null;
                            $extension = $file->getClientOriginalExtension();
                            $fileName = AttachmentFileNamingHelper::generateFileName(
                                $projectId,
                                $field,
                                $extension,
                                $userProvidedName,
                                'ILP'
                            );

                            // Store on 'public' disk
                            $filePath = $file->storeAs($projectDir, $fileName, 'public');
                            \Log::info("File storedAs", ['field' => $field, 'filePath' => $filePath]);

                            if ($filePath && Storage::disk('public')->exists($filePath)) {
                                $uploadedFiles[] = $filePath; // Track for cleanup
                                \Log::info("New file verified in storage: {$filePath}");

                                // Get next serial number
                                $serialNumber = AttachmentFileNamingHelper::getNextSerialNumber($projectId, $field, 'ILP');
                                $serialFormatted = str_pad($serialNumber, 2, '0', STR_PAD_LEFT);

                                // Create file record in new table
                                ProjectILPDocumentFile::create([
                                    'ILP_doc_id' => $documents->ILP_doc_id,
                                    'project_id' => $projectId,
                                    'field_name' => $field,
                                    'file_path' => $filePath,
                                    'file_name' => $userProvidedName ?? $fileName,
                                    'description' => $descriptions[$index] ?? '',
                                    'serial_number' => $serialFormatted,
                                    'public_url' => Storage::url($filePath),
                                ]);
                            } else {
                                \Log::warning("File was not stored or does not exist after storeAs", [
                                    'field' => $field,
                                    'filePath' => $filePath
                                ]);
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            // Clean up uploaded files on error
            foreach ($uploadedFiles as $filePath) {
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
            }
            throw $e;
        }

        $documents->save();
        \Log::info("Document record updated/saved", [
            'record_id' => $documents->id
        ]);

        return $documents;
    }



    /**
     * Extract the file name from a given path.
     */
    public function getFileName($field)
    {
        return $this->$field ? basename($this->$field) : null;
    }

    /**
     * Get the file URL for a given field.
     */
    public function getFileUrl($field)
    {
        return $this->$field ? Storage::url($this->$field) : null;
    }

    /**
     * Delete attachments when a record is deleted.
     */
    public function deleteAttachments()
    {
        $fields = ['aadhar_doc', 'request_letter_doc', 'purchase_quotation_doc', 'other_doc'];

        foreach ($fields as $field) {
            if ($this->$field) {
                Storage::delete($this->$field);
            }
        }
    }

    /**
     * Validate file type
     */
    private static function isValidFileType($file)
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $mimeType = $file->getMimeType();

        $allowedTypes = config('attachments.allowed_file_types.image_only');

        return in_array($extension, $allowedTypes['extensions']) &&
               in_array($mimeType, $allowedTypes['mimes']);
    }
}
