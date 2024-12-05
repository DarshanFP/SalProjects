<?php

namespace App\Models\OldProjects;

use App\Models\Reports\Monthly\DPActivity;
use Illuminate\Database\Eloquent\Model;

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
