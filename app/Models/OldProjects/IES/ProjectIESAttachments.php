<?php

namespace App\Models\OldProjects\IES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;
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


    public static function handleAttachments($request, $projectId)
    {
        $fields = [
            'aadhar_card', 'fee_quotation', 'scholarship_proof', 'medical_confirmation',
            'caste_certificate', 'self_declaration', 'death_certificate', 'request_letter'
        ];

        // ✅ Change to `public/` so Laravel's `storage` route works!
        $projectDir = "public/project_attachments/IES/{$projectId}";

        // ✅ Ensure the directory exists
        \Storage::makeDirectory($projectDir);

        $attachments = self::updateOrCreate(['project_id' => $projectId], []);

        foreach ($fields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $fileName = "{$projectId}_{$field}." . $file->getClientOriginalExtension();

                // ✅ Save file in `public/` so Laravel can serve it correctly
                $filePath = $file->storeAs($projectDir, $fileName);

                // ✅ Save the correct path in the database
                $attachments->{$field} = $filePath;
            }
        }

        $attachments->save();
        return $attachments;
    }


}
