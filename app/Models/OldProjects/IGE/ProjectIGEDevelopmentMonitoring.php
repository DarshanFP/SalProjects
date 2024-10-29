<?php

namespace App\Models\OldProjects\IGE;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

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
