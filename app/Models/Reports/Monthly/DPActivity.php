<?php

namespace App\Models\Reports\Monthly;

use App\Models\OldProjects\ProjectTimeframe;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $activity_id
 * @property string $objective_id
 * @property string|null $project_activity_id
 * @property string|null $activity
 * @property string|null $month
 * @property string|null $summary_activities
 * @property string|null $qualitative_quantitative_data
 * @property string|null $intermediate_outcomes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Monthly\DPObjective $objective
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProjectTimeframe> $timeframes
 * @property-read int|null $timeframes_count
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereActivity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereIntermediateOutcomes($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereObjectiveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereProjectActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereQualitativeQuantitativeData($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereSummaryActivities($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPActivity whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DPActivity extends Model
{
    use HasFactory;

    protected $table = 'DP_Activities';
    protected $primaryKey = 'activity_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'activity_id',
        'objective_id',
        'project_activity_id',
        'activity',
        'month',
        'summary_activities',
        'qualitative_quantitative_data',
        'intermediate_outcomes',
    ];

    public function objective()
    {
        return $this->belongsTo(DPObjective::class, 'objective_id', 'objective_id');
    }
    public function timeframes()
    {
        return $this->hasMany(ProjectTimeframe::class, 'activity_id', 'project_activity_id');
    }




}
