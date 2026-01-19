<?php

namespace App\Models\OldProjects\IES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;
use App\Models\OldProjects\IES\ProjectIESAttachmentFile;
use App\Helpers\AttachmentFileNamingHelper;
use Illuminate\Support\Facades\Storage;

/**
 * 
 *
 * @property int $id
 * @property string $IES_attachment_id
 * @property string $project_id
 * @property string|null $aadhar_card
 * @property string|null $fee_quotation
 * @property string|null $scholarship_proof
 * @property string|null $medical_confirmation
 * @property string|null $caste_certificate
 * @property string|null $self_declaration
 * @property string|null $death_certificate
 * @property string|null $request_letter
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereAadharCard($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereCasteCertificate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereDeathCertificate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereFeeQuotation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereIESAttachmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereMedicalConfirmation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereRequestLetter($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereScholarshipProof($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereSelfDeclaration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESAttachments whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIESAttachments extends Model
{
    use HasFactory;

    protected $table = 'project_IES_attachments';

    protected $fillable = [
        'IES_attachment_id',
        'project_id',
        'aadhar_card',
        'fee_quotation',
        'scholarship_proof',
        'medical_confirmation',
        'caste_certificate',
        'self_declaration',
        'death_certificate',
        'request_letter'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->IES_attachment_id = $model->generateIESAttachmentId();
        });
    }

    private function generateIESAttachmentId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IES_attachment_id, -4)) + 1 : 1;
        return 'IES-ATTACH-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
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
        return $this->hasMany(ProjectIESAttachmentFile::class, 'IES_attachment_id', 'IES_attachment_id');
    }

    /**
     * Get files for a specific field
     */
    public function getFilesForField($fieldName)
    {
        return $this->files()->where('field_name', $fieldName)->orderBy('serial_number')->get();
    }


    public static function handleAttachments($request, $projectId)
    {
        $fields = [
            'aadhar_card', 'fee_quotation', 'scholarship_proof', 'medical_confirmation',
            'caste_certificate', 'self_declaration', 'death_certificate', 'request_letter'
        ];

        // Storage path without 'public/' prefix - Laravel's Storage::disk('public') handles this
        $projectDir = "project_attachments/IES/{$projectId}";

        // Ensure the directory exists on public disk
        Storage::disk('public')->makeDirectory($projectDir, 0755, true);

        $attachments = self::updateOrCreate(['project_id' => $projectId], []);

        $uploadedFiles = []; // Track uploaded files for cleanup on error

        try {
        foreach ($fields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);

                    // Validate file type
                    if (!self::isValidFileType($file)) {
                        \Log::error('Invalid file type for IES attachment', [
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

                $fileName = "{$projectId}_{$field}." . $file->getClientOriginalExtension();

                    // Save file on public disk - storeAs handles the path correctly
                    $filePath = $file->storeAs($projectDir, $fileName, 'public');

                    if ($filePath) {
                        $uploadedFiles[] = $filePath; // Track for cleanup

                        // Remove old file if different from new path
                        if (!empty($attachments->{$field}) && $attachments->{$field} !== $filePath) {
                            Storage::disk('public')->delete($attachments->{$field});
                        }

                // ✅ Save the correct path in the database
                $attachments->{$field} = $filePath;
                    }
            }
        }

        $attachments->save();
        return $attachments;

        } catch (\Exception $e) {
            // Clean up uploaded files on error
            foreach ($uploadedFiles as $filePath) {
                if (Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
            }
            throw $e;
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
