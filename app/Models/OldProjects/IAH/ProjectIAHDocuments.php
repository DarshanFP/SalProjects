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

        // The list of fields from your form
        $fields = [
            'aadhar_copy',
            'request_letter',
            'medical_reports',
            'other_docs',
        ];

        // Ensure directory exists
        $projectDir = "project_attachments/IAH/{$projectId}";
        Storage::disk('public')->makeDirectory($projectDir);

        // Update or create the row for this project
        $documents = self::updateOrCreate(['project_id' => $projectId], []);

        // For each field, if there's a file, store it
        foreach ($fields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);

                $extension = $file->getClientOriginalExtension();
                $fileName  = "{$projectId}_{$field}.{$extension}";

                // storeAs on the public disk => /storage/project_attachments/IAH/{$projectId}
                $filePath = $file->storeAs($projectDir, $fileName, 'public');

                // If successful:
                if ($filePath && Storage::disk('public')->exists($filePath)) {
                    // Remove old file if different from new path
                    if (!empty($documents->{$field}) && $documents->{$field} !== $filePath) {
                        Storage::disk('public')->delete($documents->{$field});
                    }

                    $documents->{$field} = $filePath;
                }
            }
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
}
