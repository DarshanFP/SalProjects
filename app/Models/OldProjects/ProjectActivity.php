<?php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Model;

class ProjectActivity extends Model
{
    protected $fillable = ['activity_id', 'objective_id', 'activity', 'verification'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->activity_id)) {
                $model->activity_id = $model->generateActivityId();
            }
        });
    }

    private function generateActivityId()
    {
        $latestActivity = self::where('objective_id', $this->objective_id)->latest('id')->first();
        $sequenceNumber = $latestActivity ? intval(substr($latestActivity->activity_id, -2)) + 1 : 1;

        $sequenceNumberPadded = str_pad($sequenceNumber, 2, '0', STR_PAD_LEFT);

        return $this->objective_id . '-ACT-' . $sequenceNumberPadded;
    }

    public function objective()
    {
        return $this->belongsTo(ProjectObjective::class, 'objective_id', 'objective_id');
    }

    public function timeframes()
    {
        return $this->hasMany(ProjectTimeframe::class, 'activity_id', 'activity_id');
    }
    

}
