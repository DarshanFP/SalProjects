<?php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OldDevelopmentProjectAttachment extends Model
{
    use HasFactory;

    protected $table = 'old_DP_attachments';

    protected $fillable = [
        'project_id',
        'file_path',
        'file_name',
        'description',
    ];

    public function project()
    {
        return $this->belongsTo(OldDevelopmentProject::class, 'project_id');
    }
}
