<?php

namespace App\Models\OldProjects\IAH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\IAH\ProjectIAHDocumentFile;
use App\Helpers\AttachmentFileNamingHelper;

/**
 * ProjectIAHDocuments Model
 *
 * @property int $id
 * @property string $IAH_doc_id
 * @property string $project_id
 * @property string|null $aadhar_copy
 * @property string|null $request_letter
 * @property string|null $medical_reports
 * @property string|null $other_docs
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 */
class ProjectIAHDocuments extends Model
{
    use HasFactory;

    protected $table = 'project_IAH_documents';

    protected $fillable = [
        'IAH_doc_id',
        'project_id',
        'aadhar_copy',
        'request_letter',
        'medical_reports',
        'other_docs',
    ];

    /**
     * Auto-generate IAH_doc_id on create.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IAH_doc_id = $model->generateIAHDocId();
        });

        // Optionally, if you want to remove old files on model deletion
        static::deleting(function ($model) {
            $model->deleteAttachments();
        });
    }

    /**
     * Generate a unique IAH_doc_id.
     */
    private function generateIAHDocId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IAH_doc_id, -4)) + 1 : 1;
        return 'IAH-DOC-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Relationship to Project model.
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
        return $this->hasMany(ProjectIAHDocumentFile::class, 'IAH_doc_id', 'IAH_doc_id');
    }

    /**
     * Get files for a specific field
     */
    public function getFilesForField($fieldName)
    {
        return $this->files()->where('field_name', $fieldName)->orderBy('serial_number')->get();
    }

    /**
     * Handle the file upload/storage for IAH documents.
     */
    public static function handleDocuments($request, $projectId)
    {
        \Log::info("handleDocuments() IAH called for project: {$projectId}");

        // The list of fields from your form
        $fields = [
            'aadhar_copy',
            'request_letter',
            'medical_reports',
            'other_docs',
        ];

        // Ensure directory exists
        $projectDir = "project_attachments/IAH/{$projectId}";
        Storage::disk('public')->makeDirectory($projectDir, 0755, true);

        // Update or create the row for this project
        $documents = self::updateOrCreate(['project_id' => $projectId], []);

        $uploadedFiles = []; // Track uploaded files for cleanup on error

        try {
            // For each field, if there's a file, store it
            foreach ($fields as $field) {
                // Support both single file and array of files
                if ($request->hasFile($field)) {
                    $files = is_array($request->file($field)) 
                        ? $request->file($field) 
                        : [$request->file($field)];
                    
                    // Get user-provided names if any
                    $fileNames = $request->input("{$field}_names", []);
                    $descriptions = $request->input("{$field}_descriptions", []);

                    foreach ($files as $index => $file) {
                        if ($file && $file->isValid()) {
                            // Validate file type
                            if (!self::isValidFileType($file)) {
                                \Log::error('Invalid file type for IAH document', [
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
                                'IAH'
                            );

                            // storeAs on the public disk
                            $filePath = $file->storeAs($projectDir, $fileName, 'public');

                            // If successful:
                            if ($filePath && Storage::disk('public')->exists($filePath)) {
                                $uploadedFiles[] = $filePath; // Track for cleanup

                                // Get next serial number
                                $serialNumber = AttachmentFileNamingHelper::getNextSerialNumber($projectId, $field, 'IAH');
                                $serialFormatted = str_pad($serialNumber, 2, '0', STR_PAD_LEFT);

                                // Create file record in new table
                                ProjectIAHDocumentFile::create([
                                    'IAH_doc_id' => $documents->IAH_doc_id,
                                    'project_id' => $projectId,
                                    'field_name' => $field,
                                    'file_path' => $filePath,
                                    'file_name' => $userProvidedName ?? $fileName,
                                    'description' => $descriptions[$index] ?? '',
                                    'serial_number' => $serialFormatted,
                                    'public_url' => Storage::url($filePath),
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

        return $documents;
    }

    /**
     * Return the file name portion from the stored path.
     */
    public function getFileName($field)
    {
        return $this->$field ? basename($this->$field) : null;
    }

    /**
     * Optional: Delete attachments on model delete (if you want).
     */
    public function deleteAttachments()
    {
        $fields = ['aadhar_copy', 'request_letter', 'medical_reports', 'other_docs'];

        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                \Log::info("Deleting file on model delete", ['field' => $field]);
                Storage::disk('public')->delete($this->$field);
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
