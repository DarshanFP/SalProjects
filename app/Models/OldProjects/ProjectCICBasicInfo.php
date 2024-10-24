<?php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectCICBasicInfo extends Model
{
    use HasFactory;

    protected $table = 'project_cic_basic_info';

    protected $fillable = [
        'cic_basic_info_id',
        'project_id',
        'number_served_since_inception',
        'number_served_previous_year',
        'beneficiary_categories',
        'sisters_intervention',
        'beneficiary_conditions',
        'beneficiary_problems',
        'institution_challenges',
        'support_received',
        'project_need',
    ];

    // Automatically generate cic_basic_info_id
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->cic_basic_info_id = $model->generateCICBasicInfoId();
        });
    }

    // Method to generate a unique ID for cic_basic_info_id
    private function generateCICBasicInfoId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->cic_basic_info_id, -4)) + 1 : 1;

        return 'CIC-BI-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with the project
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
