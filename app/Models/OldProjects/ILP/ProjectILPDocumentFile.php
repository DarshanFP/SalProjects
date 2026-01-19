<?php

namespace App\Models\OldProjects\ILP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Storage;

class ProjectILPDocumentFile extends Model
{
    use HasFactory;

    protected $table = 'project_ILP_document_files';

    protected $fillable = [
        'ILP_doc_id',
        'project_id',
        'field_name',
        'file_path',
        'file_name',
        'description',
        'serial_number',
        'public_url',
    ];

    /**
     * Get the project that owns this document file
     */
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    /**
     * Get the parent ILP document record
     */
    public function ilpDocument()
    {
        return $this->belongsTo(ProjectILPAttachedDocuments::class, 'ILP_doc_id', 'ILP_doc_id');
    }

    /**
     * Delete file from storage when model is deleted
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($file) {
            if (Storage::disk('public')->exists($file->file_path)) {
                Storage::disk('public')->delete($file->file_path);
            }
        });
    }

    /**
     * Get the file URL
     */
    public function getUrlAttribute()
    {
        return Storage::url($this->file_path);
    }
}
