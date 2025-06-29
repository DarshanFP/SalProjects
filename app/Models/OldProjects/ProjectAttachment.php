<?php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $project_id
 * @property string|null $file_path
 * @property string|null $file_name
 * @property string|null $description
 * @property string|null $public_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment wherePublicUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectAttachment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectAttachment extends Model
{
    use HasFactory;

    protected $table = 'project_attachments';
    protected $fillable = [
        'project_id',
        'file_name',
        'file_path',
        'description',
        'public_url'
    ];


    

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
