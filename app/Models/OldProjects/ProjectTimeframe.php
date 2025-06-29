<?php

namespace App\Models\OldProjects;

use App\Models\Reports\Monthly\DPActivity;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $timeframe_id
 * @property string $activity_id
 * @property string $month
 * @property int $is_active
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read DPActivity $DPactivity
 * @property-read \App\Models\OldProjects\ProjectActivity $activity
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe whereMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe whereTimeframeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectTimeframe whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectTimeframe extends Model
{
    protected $fillable = ['timeframe_id', 'activity_id', 'month', 'is_active'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->timeframe_id)) {
                $model->timeframe_id = $model->generateTimeframeId();
            }
        });
    }

    private function generateTimeframeId()
    {
        $latestTimeframe = self::where('activity_id', $this->activity_id)->latest('id')->first();
        $sequenceNumber = $latestTimeframe ? intval(substr($latestTimeframe->timeframe_id, -2)) + 1 : 1;

        $sequenceNumberPadded = str_pad($sequenceNumber, 2, '0', STR_PAD_LEFT);

        return $this->activity_id . '-TF-' . $sequenceNumberPadded;
    }

    public function activity()
    {
        return $this->belongsTo(ProjectActivity::class, 'activity_id', 'activity_id');
    }
    public function DPactivity()
    {
        return $this->belongsTo(DPActivity::class, 'activity_id', 'project_activity_id');
    }

}
