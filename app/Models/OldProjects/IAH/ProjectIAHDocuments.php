<?php

namespace App\Models\OldProjects\IAH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\OldProjects\Project;

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
     * Handle the file upload/storage for IAH documents.
     */
    public static function handleDocuments($request, $projectId)
    {
        \Log::info("handleDocuments() IAH called for project: {$projectId}");

        if (!$request->has('attachments')) {
            // No attachments array at all
            \Log::info("No attachments array found in request for project: {$projectId}");
            return;
        }

        foreach ($request->file('attachments', []) as $docId => $docFields) {
            // $docId might be "new" or an existing numeric ID
            if ($docId === 'new') {
                \Log::info("Creating new doc row for project: {$projectId}");
                $documents = new self();
                $documents->project_id = $projectId;
                $documents->save();
            } else {
                // Find existing doc row
                $documents = self::find($docId);
                if (!$documents) {
                    \Log::warning("No existing doc row with ID=$docId. Creating new row for project=$projectId");
                    $documents = new self();
                    $documents->project_id = $projectId;
                    $documents->save();
                }
            }

            // Now store the files for each column if present
            $fieldsMap = [
                'aadhar_copy'     => 'aadhar',
                'request_letter'  => 'request_letter',
                'medical_reports' => 'medical_reports',
                'other_docs'      => 'other_docs',
            ];

            $projectDir = "project_attachments/IAH/{$projectId}";
            Storage::disk('public')->makeDirectory($projectDir);

            foreach ($fieldsMap as $column => $shortName) {
                if (isset($docFields[$column]) && $docFields[$column] instanceof \Illuminate\Http\UploadedFile) {
                    $file      = $docFields[$column];
                    $extension = $file->getClientOriginalExtension();
                    $fileName  = "{$projectId}_{$shortName}.{$extension}";

                    $filePath = $file->storeAs($projectDir, $fileName, 'public');
                    if ($filePath && Storage::disk('public')->exists($filePath)) {
                        // Optionally remove old file
                        if (!empty($documents->{$column}) && $documents->{$column} !== $filePath) {
                            Storage::disk('public')->delete($documents->{$column});
                        }
                        $documents->{$column} = $filePath;
                    }
                }
            }

            $documents->save();
        }
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
}
