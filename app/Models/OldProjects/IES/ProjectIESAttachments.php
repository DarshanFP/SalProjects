<?php

namespace App\Models\OldProjects\IES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

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
}
