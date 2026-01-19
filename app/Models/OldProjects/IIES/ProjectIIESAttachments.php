<?php

namespace App\Models\OldProjects\IIES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\IIES\ProjectIIESAttachmentFile;
use App\Helpers\AttachmentFileNamingHelper;

/**
 * 
 *
 * @property int $id
 * @property string $IIES_attachment_id
 * @property string $project_id
 * @property string|null $iies_aadhar_card
 * @property string|null $iies_fee_quotation
 * @property string|null $iies_scholarship_proof
 * @property string|null $iies_medical_confirmation
 * @property string|null $iies_caste_certificate
 * @property string|null $iies_self_declaration
 * @property string|null $iies_death_certificate
 * @property string|null $iies_request_letter
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereIIESAttachmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereIiesAadharCard($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereIiesCasteCertificate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereIiesDeathCertificate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereIiesFeeQuotation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereIiesMedicalConfirmation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereIiesRequestLetter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereIiesScholarshipProof($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereIiesSelfDeclaration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESAttachments whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIIESAttachments extends Model
{
    use HasFactory;

    protected $table = 'project_IIES_attachments';

    protected $fillable = [
        'IIES_attachment_id',
        'project_id',
        'iies_aadhar_card',
        'iies_fee_quotation',
        'iies_scholarship_proof',
        'iies_medical_confirmation',
        'iies_caste_certificate',
        'iies_self_declaration',
        'iies_death_certificate',
        'iies_request_letter',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IIES_attachment_id = $model->generateIIESAttachmentId();
        });

        // If you want to remove old files on model deletion:
        static::deleting(function ($model) {
            $model->deleteAttachments();
        });
    }

    private function generateIIESAttachmentId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest
            ? intval(substr($latest->IIES_attachment_id, -4)) + 1
            : 1;

        return 'IIES-ATTACH-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    /**
     * Get all files for this attachment record
     */
    public function files()
    {
        return $this->hasMany(ProjectIIESAttachmentFile::class, 'IIES_attachment_id', 'IIES_attachment_id');
    }

    /**
     * Get files for a specific field
     */
    public function getFilesForField($fieldName)
    {
        return $this->files()->where('field_name', $fieldName)->orderBy('serial_number')->get();
    }

    /**
     * Similar to handleDocuments in the IAH example, but for iies_ fields.
     */
    public static function handleAttachments($request, $projectId)
    {
        \Log::info("handleAttachments() IIES called for project: {$projectId}");

        // The list of fields from your form
        $fields = [
            'iies_aadhar_card',
            'iies_fee_quotation',
            'iies_scholarship_proof',
            'iies_medical_confirmation',
            'iies_caste_certificate',
            'iies_self_declaration',
            'iies_death_certificate',
            'iies_request_letter',
        ];

        // Ensure directory exists
        $projectDir = "project_attachments/IIES/{$projectId}";
        Storage::disk('public')->makeDirectory($projectDir, 0755, true);

        // Update or create the row for this project
        $attachments = self::updateOrCreate(['project_id' => $projectId], []);

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
                                \Log::error('Invalid file type for IIES attachment', [
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
                                'IIES'
                            );

                            // storeAs on the public disk
                            $filePath = $file->storeAs($projectDir, $fileName, 'public');

                            // If successful:
                            if ($filePath && Storage::disk('public')->exists($filePath)) {
                                $uploadedFiles[] = $filePath; // Track for cleanup

                                // Get next serial number
                                $serialNumber = AttachmentFileNamingHelper::getNextSerialNumber($projectId, $field, 'IIES');
                                $serialFormatted = str_pad($serialNumber, 2, '0', STR_PAD_LEFT);

                                // Create file record in new table
                                ProjectIIESAttachmentFile::create([
                                    'IIES_attachment_id' => $attachments->IIES_attachment_id,
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

        $attachments->save();
        return $attachments;
    }

    /**
     * Optional: get just the filename portion from $field.
     */
    public function getFileName($field)
    {
        return $this->$field ? basename($this->$field) : null;
    }

    /**
     * Optional: remove files on model delete.
     */
    public function deleteAttachments()
    {
        $fields = [
            'iies_aadhar_card',
            'iies_fee_quotation',
            'iies_scholarship_proof',
            'iies_medical_confirmation',
            'iies_caste_certificate',
            'iies_self_declaration',
            'iies_death_certificate',
            'iies_request_letter',
        ];

        foreach ($fields as $field) {
            if (!empty($this->$field)) {
                \Log::info("Deleting file on model delete", ['field' => $field]);
                Storage::disk('public')->delete($this->$field);
            }
        }
    }
}
