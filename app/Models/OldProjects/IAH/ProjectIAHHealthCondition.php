<?php

namespace App\Models\OldProjects\IAH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectIAHHealthCondition extends Model
{
    use HasFactory;

    protected $table = 'project_IAH_health_condition';

    protected $fillable = [
        'IAH_health_id',
        'project_id',
        'illness',
        'treatment',
        'doctor',
        'hospital',
        'doctor_address',
        'health_situation',
        'family_situation',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IAH_health_id = $model->generateIAHHealthId();
        });
    }

    private function generateIAHHealthId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IAH_health_id, -4)) + 1 : 1;
        return 'IAH-HEALTH-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
