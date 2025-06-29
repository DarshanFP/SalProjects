<?php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $project_id
 * @property string $objective_id
 * @property string|null $objective
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectActivity> $activities
 * @property-read int|null $activities_count
 * @property-read \App\Models\OldProjects\Project $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectResult> $results
 * @property-read int|null $results_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectRisk> $risks
 * @property-read int|null $risks_count
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectObjective newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectObjective newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectObjective query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectObjective whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectObjective whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectObjective whereObjective($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectObjective whereObjectiveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectObjective whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectObjective whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectObjective extends Model
{
    protected $fillable = ['objective_id', 'project_id', 'objective'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->objective_id)) {
                $model->objective_id = $model->generateObjectiveId();
            }
        });
    }

    private function generateObjectiveId()
    {
        $latestObjective = self::where('project_id', $this->project_id)->latest('id')->first();
        $sequenceNumber = $latestObjective ? intval(substr($latestObjective->objective_id, -2)) + 1 : 1;

        $sequenceNumberPadded = str_pad($sequenceNumber, 2, '0', STR_PAD_LEFT);

        return $this->project_id . '-OBJ-' . $sequenceNumberPadded;
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function results()
    {
        return $this->hasMany(ProjectResult::class, 'objective_id', 'objective_id');
    }

    public function activities()
    {
        return $this->hasMany(ProjectActivity::class, 'objective_id', 'objective_id');
    }
    public function risks()
    {
        return $this->hasMany(ProjectRisk::class, 'objective_id', 'objective_id');
    }
}
