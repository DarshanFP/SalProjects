<?php

namespace App\Models\OldProjects;

use App\Models\OldProjects\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $operational_area_id
 * @property string $project_id
 * @property string|null $institution_type
 * @property string|null $group_type
 * @property string|null $category
 * @property string|null $project_location
 * @property string|null $sisters_work
 * @property string|null $conditions
 * @property string|null $problems
 * @property string|null $need
 * @property string|null $criteria
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereConditions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereCriteria($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereGroupType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereInstitutionType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereNeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereOperationalAreaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereProblems($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereProjectLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereSistersWork($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectEduRUTBasicInfo whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectEduRUTBasicInfo extends Model
{
    use HasFactory;

    protected $table = 'Project_EduRUT_Basic_Info';

    protected $fillable = [
        'operational_area_id',
        'project_id',
        'institution_type',
        'group_type',
        'category',
        'project_location',
        'sisters_work',
        'conditions',
        'problems',
        'need',
        'criteria',
    ];

    // Automatically generate operational_area_id
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->operational_area_id = $model->generateOperationalAreaId();
        });
    }

    // Method to generate a unique ID for operational_area_id
    private function generateOperationalAreaId()
    {
        $latestOperationalArea = self::latest('id')->first();
        $sequenceNumber = $latestOperationalArea ? intval(substr($latestOperationalArea->operational_area_id, -4)) + 1 : 1;

        return 'EDURUT-OA-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with the project
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
