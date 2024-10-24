<?php

namespace App\Models\OldProjects\IES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectIESEducationBackground extends Model
{
    use HasFactory;

    protected $table = 'project_IES_educational_background';

    protected $fillable = [
        'IES_education_id',
        'project_id',
        'previous_class',
        'amount_sanctioned',
        'amount_utilized',
        'scholarship_previous_year',
        'academic_performance',
        'present_class',
        'expected_scholarship',
        'family_contribution',
        'reason_no_support'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->IES_education_id = $model->generateIESEducationId();
        });
    }

    private function generateIESEducationId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IES_education_id, -4)) + 1 : 1;
        return 'IES-EDU-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
