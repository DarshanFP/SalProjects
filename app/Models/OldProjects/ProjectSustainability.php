<?php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
