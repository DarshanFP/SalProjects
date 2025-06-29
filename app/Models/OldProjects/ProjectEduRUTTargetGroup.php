<?php
namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $target_group_id
 * @property string $project_id
 * @property string|null $beneficiary_name
 * @property string|null $caste
 * @property string|null $institution_name
 * @property string|null $class_standard
 * @property string|null $total_tuition_fee
 * @property int|null $eligibility_scholarship
 * @property string|null $expected_amount
 * @property string|null $contribution_from_family
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereBeneficiaryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereCaste($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereClassStandard($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereContributionFromFamily($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereEligibilityScholarship($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereExpectedAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereInstitutionName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereTargetGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereTotalTuitionFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTTargetGroup whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectEduRUTTargetGroup extends Model
{
    use HasFactory;

    protected $table = 'project_edu_rut_target_groups'; // Ensure the table name is correct

    protected $fillable = [
        'target_group_id',
        'project_id',
        'beneficiary_name',
        'caste',
        'institution_name',
        'class_standard',
        'total_tuition_fee',
        'eligibility_scholarship',
        'expected_amount',
        'contribution_from_family',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->target_group_id = $model->generateTargetGroupId();
        });
    }

    private function generateTargetGroupId()
    {
        $latestGroup = self::latest('id')->first();
        $sequenceNumber = $latestGroup ? intval(substr($latestGroup->target_group_id, -4)) + 1 : 1;

        return 'TG-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
