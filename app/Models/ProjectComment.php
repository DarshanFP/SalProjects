<?php

namespace App\Models;

use App\Models\OldProjects\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectComment extends Model
{
    use HasFactory;

    protected $table = 'project_comments';

    protected $primaryKey = 'project_comment_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'project_comment_id',
        'project_id',
        'user_id',
        'comment',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
