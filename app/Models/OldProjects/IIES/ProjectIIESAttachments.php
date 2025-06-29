<?php

namespace App\Models\OldProjects\IIES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use App\Models\OldProjects\Project;

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
        Storage::disk('public')->makeDirectory($projectDir);

        // Update or create the row for this project
        $attachments = self::updateOrCreate(['project_id' => $projectId], []);

        // For each field, if there's a file, store it
        foreach ($fields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);

                $extension = $file->getClientOriginalExtension();
                $fileName  = "{$projectId}_{$field}.{$extension}";

                // storeAs on the public disk => /storage/project_attachments/IIES/{$projectId}
                $filePath = $file->storeAs($projectDir, $fileName, 'public');

                // If successful:
                if ($filePath && Storage::disk('public')->exists($filePath)) {
                    // Remove old file if different from new path
                    if (!empty($attachments->{$field}) && $attachments->{$field} !== $filePath) {
                        Storage::disk('public')->delete($attachments->{$field});
                    }

                    $attachments->{$field} = $filePath;
                }
            }
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
