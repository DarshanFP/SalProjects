<?php

namespace App\Models\OldProjects\ILP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectILPAttachedDocuments extends Model
{
    use HasFactory;

    protected $table = 'project_ILP_attached_docs';

    protected $fillable = [
        'ILP_doc_id', 'project_id', 'aadhar_doc', 'request_letter_doc', 'purchase_quotation_doc', 'other_doc'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->ILP_doc_id = $model->generateILPDocId();
        });
    }

    private function generateILPDocId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->ILP_doc_id, -4)) + 1 : 1;
        return 'ILP-DOC-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }
}
