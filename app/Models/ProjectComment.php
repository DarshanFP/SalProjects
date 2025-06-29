<?php

namespace App\Models;

use App\Models\OldProjects\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $project_comment_id
 * @property string $project_id
 * @property int $user_id
 * @property string $comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project $project
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment whereProjectCommentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectComment whereUserId($value)
 * @mixin \Eloquent
 */
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
