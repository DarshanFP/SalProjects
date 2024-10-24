<?php

namespace App\Models\OldProjects\IAH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
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

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IAH_doc_id = $model->generateIAHDocId();
        });
    }

    private function generateIAHDocId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IAH_doc_id, -4)) + 1 : 1;
        return 'IAH-DOC-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
