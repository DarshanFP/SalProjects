<?php

namespace App\Models\OldProjects\RST;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $target_group_anxr_id
 * @property string $project_id
 * @property string|null $rst_name
 * @property string|null $rst_religion
 * @property string|null $rst_caste
 * @property string|null $rst_education_background
 * @property string|null $rst_family_situation
 * @property string|null $rst_paragraph
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereRstCaste($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereRstEducationBackground($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereRstFamilySituation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereRstName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereRstParagraph($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereRstReligion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereTargetGroupAnxrId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTTargetGroupAnnexure whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectRSTTargetGroupAnnexure extends Model
{
    use HasFactory;

    protected $table = 'project_RST_target_group_annexure';

    protected $fillable = [
        'target_group_anxr_id',
        'project_id',
        'rst_name',
        'rst_religion',
        'rst_caste',
        'rst_education_background',
        'rst_family_situation',
        'rst_paragraph'
    ];

    // Generate unique ID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->target_group_anxr_id = $model->generateTargetGroupAnnexureId();
        });
    }

    private function generateTargetGroupAnnexureId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->target_group_anxr_id, -4)) + 1 : 1;

        return 'RST-TGA-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
