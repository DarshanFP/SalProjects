<?php

namespace App\Models\OldProjects\IGE;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IGE_dvlpmnt_mntrng_id
 * @property string $project_id
 * @property string|null $proposed_activities
 * @property string|null $monitoring_methods
 * @property string|null $evaluation_process
 * @property string|null $conclusion
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring whereConclusion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring whereEvaluationProcess($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring whereIGEDvlpmntMntrngId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring whereMonitoringMethods($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring whereProposedActivities($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEDevelopmentMonitoring whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIGEDevelopmentMonitoring extends Model
{
    use HasFactory;

    protected $table = 'project_IGE_development_monitoring';

    protected $fillable = [
        'IGE_dvlpmnt_mntrng_id',
        'project_id',
        'proposed_activities',
        'monitoring_methods',
        'evaluation_process',
        'conclusion'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IGE_dvlpmnt_mntrng_id = $model->generateIGEDevelopmentMonitoringId();
        });
    }

    private function generateIGEDevelopmentMonitoringId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IGE_dvlpmnt_mntrng_id, -4)) + 1 : 1;
        return 'IGE-DEVM-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
