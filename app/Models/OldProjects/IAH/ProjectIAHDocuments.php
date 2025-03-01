<?php

// namespace App\Models\OldProjects\IAH;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Illuminate\Database\Eloquent\Model;
// use Illuminate\Support\Facades\Storage;
// use App\Models\OldProjects\Project;

// class ProjectIAHDocuments extends Model
// {
//     use HasFactory;

//     protected $table = 'project_IAH_documents';

//     protected $fillable = [
//         'IAH_doc_id',
//         'project_id',
//         'aadhar_copy',
//         'request_letter',
//         'medical_reports',
//         'other_docs',
//     ];

//     protected static function boot()
//     {
//         parent::boot();

//         static::creating(function ($model) {
//             $model->IAH_doc_id = $model->generateIAHDocId();
//         });
//     }

//     private function generateIAHDocId()
//     {
//         $latest = self::latest('id')->first();
//         $sequenceNumber = $latest ? intval(substr($latest->IAH_doc_id, -4)) + 1 : 1;
//         return 'IAH-DOC-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
//     }

//     public function project()
//     {
//         return $this->belongsTo(Project::class, 'project_id', 'project_id');
//     }

//     /**
//      * Handle the file upload/storage for IAH documents, just like ILP does.
//      */
//     public static function handleDocuments($request, $projectId)
//     {
//         // The array used in your Blade partial, matching the `attachments[field]`
//         $fields = [
//             'aadhar_copy'    => 'aadhar',
//             'request_letter' => 'request_letter',
//             'medical_reports'=> 'medical_reports',
//             'other_docs'     => 'other_docs',
//         ];

//         // Use a consistent directory structure
//         $projectDir = "public/project_attachments/IAH/{$projectId}";
//         Storage::makeDirectory($projectDir);

//         // Update or create a record for the project
//         $documents = self::updateOrCreate(['project_id' => $projectId], []);

//         foreach ($fields as $field => $shortName) {
//             if ($request->hasFile("attachments.$field")) {
//                 $file = $request->file("attachments.$field");

//                 $fileName = "{$projectId}_{$shortName}.".$file->getClientOriginalExtension();

//                 // storeAs returns the path where the file is stored
//                 $filePath = $file->storeAs($projectDir, $fileName);

//                 $documents->{$field} = $filePath;
//             }
//         }

//         $documents->save();
//         return $documents;
//     }

//     /**
//      * Optional: Get the base file name without the directory.
//      * E.g. "mydoc.pdf" from "public/project_attachments/IAH/1/mydoc.pdf".
//      */
//     public function getFileName($field)
//     {
//         if ($this->$field) {
//             return basename($this->$field);
//         }
//         return null;
//     }
// }

namespace App\Models\OldProjects\IAH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\OldProjects\Project;

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
     * Allows repeated updates (prevents deleting newly stored file).
     */
    // public static function handleDocuments($request, $projectId)
    // {
    //     \Log::info("handleDocuments() IAH called for project: {$projectId}");

    //     // Mappings from DB fields => short prefix
    //     $fields = [
    //         'aadhar_copy'     => 'aadhar',
    //         'request_letter'  => 'request_letter',
    //         'medical_reports' => 'medical_reports',
    //         'other_docs'      => 'other_docs',
    //     ];

    //     // Directory under storage/app/public
    //     $projectDir = "project_attachments/IAH/{$projectId}";
    //     \Log::info("Ensuring directory exists: {$projectDir}");
    //     Storage::disk('public')->makeDirectory($projectDir);

    //     // Retrieve or create doc record
    //     $documents = self::updateOrCreate(['project_id' => $projectId], []);
    //     \Log::info("ProjectIAHDocuments found or created", ['record_id' => $documents->id]);

    //     foreach ($fields as $field => $shortName) {
    //         if ($request->hasFile("attachments.$field")) {
    //             $file = $request->file("attachments.$field");
    //             $extension = $file->getClientOriginalExtension();
    //             $fileName = "{$projectId}_{$shortName}.{$extension}";

    //             \Log::info("Processing field: {$field}", [
    //                 'original_name' => $file->getClientOriginalName(),
    //                 'extension' => $extension,
    //             ]);

    //             // Store on the 'public' disk => storage/app/public
    //             $filePath = $file->storeAs($projectDir, $fileName, 'public');
    //             \Log::info("File storedAs() returned", ['filePath' => $filePath]);

    //             if ($filePath && Storage::disk('public')->exists($filePath)) {
    //                 \Log::info("New file exists in storage: {$filePath}");

    //                 // Only delete the old file if it's different from new path
    //                 if (!empty($documents->{$field}) && $documents->{$field} !== $filePath) {
    //                     \Log::info("Deleting old file for field {$field}", [
    //                         'old_file' => $documents->{$field}
    //                     ]);
    //                     Storage::disk('public')->delete($documents->{$field});
    //                 }

    //                 // Save new path
    //                 $documents->{$field} = $filePath;
    //             } else {
    //                 \Log::warning("File not stored or doesn't exist after storeAs", [
    //                     'field' => $field
    //                 ]);
    //             }
    //         }
    //     }

    //     $documents->save();
    //     \Log::info("ProjectIAHDocuments record saved", ['record_id' => $documents->id]);

    //     return $documents;
    // }

// public static function handleDocuments($request, $projectId)
//     {
//         \Log::info("handleDocuments() IAH called for project: {$projectId}");

//         // Because you have multiple doc rows, each named attachments[4][aadhar_copy], etc:
//         $allDocs = $request->file('attachments');
//         // e.g. $allDocs might be: [
//         //     '4' => [ 'aadhar_copy' => UploadedFile, 'request_letter' => UploadedFile ],
//         //     '5' => [ 'aadhar_copy' => UploadedFile ],
//         // ]

//         if (!$allDocs) {
//             \Log::info("No new files found in 'attachments' for project: {$projectId}");
//             // Just return or do something
//             return null;
//         }

//         // We loop over each docId => docFields
//         foreach ($allDocs as $docId => $docFields) {

//             // 1) Try to find an existing row with ID=$docId, or fallback to 'project_id' match
//             //    If you REALLY only want ONE ROW per doc, it’s easiest to do:
//             //       $documents = self::where('project_id', $projectId)->firstOrCreate([]);
//             //    But if you’re referencing the actual ID of an existing record, you can do:
//             $documents = self::find($docId);

//             // If the record doesn't exist, create a new row with project_id
//             // (This depends on how you want to handle "new doc rows" in your UI)
//             if (!$documents) {
//                 \Log::info("No existing doc record with ID={$docId}; creating new one for project {$projectId}");
//                 $documents = new self();
//                 $documents->project_id = $projectId;
//                 $documents->save();
//             }

//             // 2) The directory under storage/app/public
//             $projectDir = "project_attachments/IAH/{$projectId}";
//             \Log::info("Ensuring directory exists: {$projectDir}");
//             Storage::disk('public')->makeDirectory($projectDir);

//             // 3) For each field => shortName, see if $docFields[$field] is a file
//             $fieldsMap = [
//                 'aadhar_copy'     => 'aadhar',
//                 'request_letter'  => 'request_letter',
//                 'medical_reports' => 'medical_reports',
//                 'other_docs'      => 'other_docs',
//             ];

//             foreach ($fieldsMap as $column => $shortName) {
//                 if (isset($docFields[$column]) && $docFields[$column] instanceof \Illuminate\Http\UploadedFile) {
//                     $file       = $docFields[$column];
//                     $extension  = $file->getClientOriginalExtension();
//                     $fileName   = "{$projectId}_{$shortName}.{$extension}";

//                     \Log::info("Processing docId={$docId}, field: {$column}", [
//                         'original_name' => $file->getClientOriginalName(),
//                         'extension'     => $extension,
//                     ]);

//                     // storeAs => 'public' disk => storage/app/public/...
//                     $filePath = $file->storeAs($projectDir, $fileName, 'public');
//                     \Log::info("File storedAs() returned", ['filePath' => $filePath]);

//                     if ($filePath && Storage::disk('public')->exists($filePath)) {
//                         \Log::info("New file exists in storage: {$filePath}");

//                         // Delete old file if it’s different from new path
//                         if (!empty($documents->{$column}) && $documents->{$column} !== $filePath) {
//                             \Log::info("Deleting old file for field {$column}", [
//                                 'old_file' => $documents->{$column}
//                             ]);
//                             Storage::disk('public')->delete($documents->{$column});
//                         }

//                         // Save new path in DB
//                         $documents->{$column} = $filePath;
//                     } else {
//                         \Log::warning("File not stored or doesn't exist after storeAs", [
//                             'field' => $column
//                         ]);
//                     }
//                 }
//             }

//             $documents->save();
//             \Log::info("ProjectIAHDocuments record saved", [
//                 'doc_row_id' => $documents->id,
//                 'aadhar_copy' => $documents->aadhar_copy,
//                 // etc.
//             ]);
//         }

//         return true; // or return something else
//     }

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
