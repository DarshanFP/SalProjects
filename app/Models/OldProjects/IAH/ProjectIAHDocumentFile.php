<?php

namespace App\Models\OldProjects\IAH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;
use Illuminate\Support\Facades\Storage;

class ProjectIAHDocumentFile extends Model
{
    use HasFactory;

    protected $table = 'project_IAH_document_files';

    protected $fillable = [
        'IAH_doc_id',
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
     * Get the parent IAH document record
     */
    public function iahDocument()
    {
        return $this->belongsTo(ProjectIAHDocuments::class, 'IAH_doc_id', 'IAH_doc_id');
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
