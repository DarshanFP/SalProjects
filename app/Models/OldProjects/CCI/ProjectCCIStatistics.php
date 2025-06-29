<?php

namespace App\Models\OldProjects\CCI;

use App\Models\OldProjects\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $CCI_statistics_id
 * @property string $project_id
 * @property int|null $total_children_previous_year
 * @property int|null $total_children_current_year
 * @property int|null $reintegrated_children_previous_year
 * @property int|null $reintegrated_children_current_year
 * @property int|null $shifted_children_previous_year
 * @property int|null $shifted_children_current_year
 * @property int|null $pursuing_higher_studies_previous_year
 * @property int|null $pursuing_higher_studies_current_year
 * @property int|null $settled_children_previous_year
 * @property int|null $settled_children_current_year
 * @property int|null $working_children_previous_year
 * @property int|null $working_children_current_year
 * @property int|null $other_category_previous_year
 * @property int|null $other_category_current_year
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereCCIStatisticsId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereOtherCategoryCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereOtherCategoryPreviousYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics wherePursuingHigherStudiesCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics wherePursuingHigherStudiesPreviousYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereReintegratedChildrenCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereReintegratedChildrenPreviousYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereSettledChildrenCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereSettledChildrenPreviousYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereShiftedChildrenCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereShiftedChildrenPreviousYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereTotalChildrenCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereTotalChildrenPreviousYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereWorkingChildrenCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIStatistics whereWorkingChildrenPreviousYear($value)
 * @mixin \Eloquent
 */
class ProjectCCIStatistics extends Model
{
    use HasFactory;

    protected $table = 'project_CCI_statistics';

    protected $fillable = [
        'CCI_statistics_id',
        'project_id',
        'total_children_previous_year',
        'total_children_current_year',
        'reintegrated_children_previous_year',
        'reintegrated_children_current_year',
        'shifted_children_previous_year',
        'shifted_children_current_year',
        'pursuing_higher_studies_previous_year',
        'pursuing_higher_studies_current_year',
        'settled_children_previous_year',
        'settled_children_current_year',
        'working_children_previous_year',
        'working_children_current_year',
        'other_category_previous_year',
        'other_category_current_year',
    ];

    // Automatically generate CCI_statistics_id
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->CCI_statistics_id = $model->generateCCIStatisticsId();
        });
    }

    // Method to generate a unique ID for CCI_statistics_id
    private function generateCCIStatisticsId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->CCI_statistics_id, -4)) + 1 : 1;

        return 'CCI-ST-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with the project
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
