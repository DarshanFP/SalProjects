<?php

namespace App\Models\OldProjects\CCI;

use App\Models\OldProjects\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $CCI_target_group_id
 * @property string $project_id
 * @property string|null $beneficiary_name
 * @property string|null $dob
 * @property string|null $date_of_joining
 * @property string|null $class_of_study
 * @property string|null $family_background_description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereBeneficiaryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereCCITargetGroupId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereClassOfStudy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereDateOfJoining($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereDob($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereFamilyBackgroundDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAnnexedTargetGroup whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectCCIAnnexedTargetGroup extends Model
{
    use HasFactory;

    protected $table = 'project_CCI_annexed_target_group';

    protected $fillable = [
        'CCI_target_group_id',
        'project_id',
        'beneficiary_name',
        'dob',
        'date_of_joining',
        'class_of_study',
        'family_background_description',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->CCI_target_group_id = $model->generateCCITargetGroupId();
        });
    }

    private function generateCCITargetGroupId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->CCI_target_group_id, -4)) + 1 : 1;

        return 'CCI-TG-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
