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
        static::deleting(function ($model) {
            $model->files()->each(function ($file) {
                $file->delete();
            });
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
     * Get files for a specific field.
     * Prefers project_IES_attachment_files; falls back to legacy column if no rows exist.
     */
    public function getFilesForField($fieldName)
    {
        $filesFromTable = $this->files()->where('field_name', $fieldName)->orderBy('serial_number')->get();

        if ($filesFromTable->isNotEmpty()) {
            return $filesFromTable;
        }

        // Legacy fallback: check if legacy column has a path
        $legacyPath = $this->{$fieldName} ?? null;
        if (!empty($legacyPath)) {
            $legacyFile = (object) [
                'file_path' => $legacyPath,
                'file_name' => basename($legacyPath),
                'description' => '',
                'serial_number' => '01',
            ];
            return collect([$legacyFile]);
        }

        return collect([]);
    }


    public static function handleAttachments($request, $projectId)
    {
        $fields = [
            'aadhar_card', 'fee_quotation', 'scholarship_proof', 'medical_confirmation',
            'caste_certificate', 'self_declaration', 'death_certificate', 'request_letter'
        ];

        $projectDir = "project_attachments/IES/{$projectId}";
        Storage::disk('public')->makeDirectory($projectDir, 0755, true);

        $attachments = self::updateOrCreate(['project_id' => $projectId], []);

        $uploadedFiles = [];

        try {
            foreach ($fields as $field) {
                if ($request->hasFile($field)) {
                    $files = is_array($request->file($field))
                        ? $request->file($field)
                        : [$request->file($field)];

                    $fileNames = $request->input("{$field}_names", []);
                    $descriptions = $request->input("{$field}_descriptions", []);

                    foreach ($files as $index => $file) {
                        if (!$file || !$file->isValid()) {
                            continue;
                        }

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

                        $maxSize = config('attachments.max_file_size.server_bytes');
                        if ($file->getSize() > $maxSize) {
                            $maxSizeMB = config('attachments.max_file_size.display_mb');
                            $errorMsg = str_replace(':size', $maxSizeMB, config('attachments.messages.file_size_error'));
                            throw new \Exception("File size exceeds limit for {$field}. {$errorMsg}");
                        }

                        $userProvidedName = $fileNames[$index] ?? null;
                        $extension = $file->getClientOriginalExtension();
                        $fileName = AttachmentFileNamingHelper::generateFileName(
                            $projectId,
                            $field,
                            $extension,
                            $userProvidedName,
                            'IES'
                        );

                        $filePath = $file->storeAs($projectDir, $fileName, 'public');

                        if ($filePath && Storage::disk('public')->exists($filePath)) {
                            $uploadedFiles[] = $filePath;

                            $serialNumber = AttachmentFileNamingHelper::getNextSerialNumber($projectId, $field, 'IES');
                            $serialFormatted = str_pad($serialNumber, 2, '0', STR_PAD_LEFT);

                            ProjectIESAttachmentFile::create([
                                'IES_attachment_id' => $attachments->IES_attachment_id,
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
        } catch (\Exception $e) {
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
