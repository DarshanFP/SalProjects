<?php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Model;

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
