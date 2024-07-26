<?php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectAttachment extends Model
{
    use HasFactory;

    protected $table = 'project_attachments';
    protected $fillable = [
        'project_id',
        'file_name',
        'file_path',
        'description',
        'public_url'];


    // public function project()
    // {
    //     return $this->belongsTo(Project::class);
    // }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
