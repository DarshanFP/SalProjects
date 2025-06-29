<?php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property int $project_id
 * @property string $file_path
 * @property string $file_name
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\OldDevelopmentProject $project
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment query()
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment whereFileName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment whereFilePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectAttachment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
