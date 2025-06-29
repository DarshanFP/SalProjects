<?php

namespace App\Models\OldProjects\CCI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $CCI_personal_situation_id
 * @property string $project_id
 * @property int|null $children_with_parents_last_year
 * @property int|null $children_with_parents_current_year
 * @property int|null $semi_orphans_last_year
 * @property int|null $semi_orphans_current_year
 * @property int|null $orphans_last_year
 * @property int|null $orphans_current_year
 * @property int|null $hiv_infected_last_year
 * @property int|null $hiv_infected_current_year
 * @property int|null $differently_abled_last_year
 * @property int|null $differently_abled_current_year
 * @property int|null $parents_in_conflict_last_year
 * @property int|null $parents_in_conflict_current_year
 * @property int|null $other_ailments_last_year
 * @property int|null $other_ailments_current_year
 * @property string|null $general_remarks
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereCCIPersonalSituationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereChildrenWithParentsCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereChildrenWithParentsLastYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereDifferentlyAbledCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereDifferentlyAbledLastYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereGeneralRemarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereHivInfectedCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereHivInfectedLastYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereOrphansCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereOrphansLastYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereOtherAilmentsCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereOtherAilmentsLastYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereParentsInConflictCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereParentsInConflictLastYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereSemiOrphansCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereSemiOrphansLastYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPersonalSituation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectCCIPersonalSituation extends Model
{
    use HasFactory;

    protected $table = 'project_CCI_personal_situation';

    protected $fillable = [
        'CCI_personal_situation_id',
        'project_id',
        'children_with_parents_last_year',
        'children_with_parents_current_year',
        'semi_orphans_last_year',
        'semi_orphans_current_year',
        'orphans_last_year',
        'orphans_current_year',
        'hiv_infected_last_year',
        'hiv_infected_current_year',
        'differently_abled_last_year',
        'differently_abled_current_year',
        'parents_in_conflict_last_year',
        'parents_in_conflict_current_year',
        'other_ailments_last_year',
        'other_ailments_current_year',
        'general_remarks', 

    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->CCI_personal_situation_id = $model->generateCCIPersonalSituationId();
        });
    }

    private function generateCCIPersonalSituationId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->CCI_personal_situation_id, -4)) + 1 : 1;

        return 'CCI-PS-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with the Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
