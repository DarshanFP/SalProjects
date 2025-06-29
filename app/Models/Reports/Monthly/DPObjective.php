<?php

namespace App\Models\Reports\Monthly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $objective_id
 * @property string|null $project_objective_id
 * @property string $report_id
 * @property string|null $objective
 * @property array|null $expected_outcome
 * @property string|null $not_happened
 * @property string|null $why_not_happened
 * @property bool|null $changes
 * @property string|null $why_changes
 * @property string|null $lessons_learnt
 * @property string|null $todo_lessons_learnt
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Reports\Monthly\DPActivity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\Reports\Monthly\DPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective query()
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereExpectedOutcome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereLessonsLearnt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereNotHappened($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereObjective($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereObjectiveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereProjectObjectiveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereTodoLessonsLearnt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereWhyChanges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPObjective whereWhyNotHappened($value)
 * @mixin \Eloquent
 */
class DPObjective extends Model
{
    use HasFactory;

    protected $table = 'DP_Objectives';
    protected $primaryKey = 'objective_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $casts = [
        'expected_outcome' => 'array',
        'changes' => 'boolean',
    ];

    protected $fillable = [
        'objective_id',
        'report_id',
        'project_objective_id',
        'objective',
        'expected_outcome',
        'not_happened',
        'why_not_happened',
        'changes',
        'why_changes',
        'lessons_learnt',
        'todo_lessons_learnt',
    ];
    public function report()
    {
        return $this->belongsTo(DPReport::class, 'report_id', 'report_id');
    }

    public function activities()
    {
        return $this->hasMany(DPActivity::class, 'objective_id', 'objective_id');
    }
}
