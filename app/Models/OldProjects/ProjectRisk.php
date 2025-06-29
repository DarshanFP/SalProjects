<?php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $risk_id
 * @property string $objective_id
 * @property string|null $risk
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\ProjectObjective $objective
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRisk newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRisk newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRisk query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRisk whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRisk whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRisk whereObjectiveId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRisk whereRisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRisk whereRiskId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRisk whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
