<?php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $sustainability_id
 * @property string $project_id
 * @property string|null $sustainability
 * @property string|null $monitoring_process
 * @property string|null $reporting_methodology
 * @property string|null $evaluation_methodology
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability whereEvaluationMethodology($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability whereMonitoringProcess($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability whereReportingMethodology($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability whereSustainability($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability whereSustainabilityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectSustainability whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectSustainability extends Model
{
    use HasFactory;

    protected $fillable = [
        'sustainability_id',
        'project_id',
        'sustainability',
        'monitoring_process',
        'reporting_methodology',
        'evaluation_methodology',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->sustainability_id = $model->generateSustainabilityId();
        });
    }

    private function generateSustainabilityId()
    {
        $latestSustainability = self::latest('id')->first();
        $sequenceNumber = $latestSustainability ? intval(substr($latestSustainability->sustainability_id, -4)) + 1 : 1;

        $sequenceNumberPadded = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);

        return 'SUS-' . $sequenceNumberPadded;
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
