<?php
namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $annexed_target_group_id
 * @property string $project_id
 * @property string|null $beneficiary_name
 * @property string|null $family_background
 * @property string|null $need_of_support
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup whereAnnexedTargetGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup whereBeneficiaryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup whereFamilyBackground($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup whereNeedOfSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTAnnexedTargetGroup whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectEduRUTAnnexedTargetGroup extends Model
{
    use HasFactory;

    protected $table = 'project_edu_rut_annexed_target_groups'; // Ensure this matches the table in your DB

    protected $fillable = [
        'annexed_target_group_id',
        'project_id',
        'beneficiary_name',
        'family_background',
        'need_of_support',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->annexed_target_group_id = $model->generateAnnexedTargetGroupId();
        });
    }

    private function generateAnnexedTargetGroupId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->annexed_target_group_id, -4)) + 1 : 1;

        return 'ANNX-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
