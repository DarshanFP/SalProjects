<?php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $activity_id
 * @property string $objective_id
 * @property string|null $activity
 * @property string|null $verification
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\ProjectObjective $objective
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\ProjectTimeframe> $timeframes
 * @property-read int|null $timeframes_count
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity whereActivity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity whereObjectiveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectActivity whereVerification($value)
 * @mixin \Eloquent
 */
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
