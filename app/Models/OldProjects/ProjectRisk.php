<?php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Model;

class ProjectRisk extends Model
{
    protected $fillable = ['risk_id', 'objective_id', 'risk'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->risk_id)) {
                $model->risk_id = $model->generateRiskId();
            }
        });
    }

    private function generateRiskId()
    {
        $latestRisk = self::where('objective_id', $this->objective_id)->latest('id')->first();
        $sequenceNumber = $latestRisk ? intval(substr($latestRisk->risk_id, -2)) + 1 : 1;

        $sequenceNumberPadded = str_pad($sequenceNumber, 2, '0', STR_PAD_LEFT);

        return $this->objective_id . '-RISK-' . $sequenceNumberPadded;
    }

    public function objective()
    {
        return $this->belongsTo(ProjectObjective::class, 'objective_id', 'objective_id');
    }
}
