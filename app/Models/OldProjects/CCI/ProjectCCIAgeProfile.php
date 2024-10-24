<?php

namespace App\Models\OldProjects\CCI;

use App\Models\OldProjects\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectCCIAgeProfile extends Model
{
    use HasFactory;

    protected $table = 'project_CCI_age_profile';

    protected $fillable = [
        'CCI_age_profile_id',
        'project_id',
        'age_category',
        'education_below_5_bridge_course_prev_year',
        'education_below_5_bridge_course_current_year',
        'education_below_5_kindergarten_prev_year',
        'education_below_5_kindergarten_current_year',
        'education_below_5_other_prev_year',
        'education_below_5_other_current_year',
        'education_6_10_primary_school_prev_year',
        'education_6_10_primary_school_current_year',
        'education_6_10_bridge_course_prev_year',
        'education_6_10_bridge_course_current_year',
        'education_6_10_other_prev_year',
        'education_6_10_other_current_year',
        'education_11_15_secondary_school_prev_year',
        'education_11_15_secondary_school_current_year',
        'education_11_15_high_school_prev_year',
        'education_11_15_high_school_current_year',
        'education_11_15_other_prev_year',
        'education_11_15_other_current_year',
        'education_16_above_undergraduate_prev_year',
        'education_16_above_undergraduate_current_year',
        'education_16_above_technical_vocational_prev_year',
        'education_16_above_technical_vocational_current_year',
        'education_16_above_other_prev_year',
        'education_16_above_other_current_year',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->CCI_age_profile_id = $model->generateCCIAgeProfileId();
        });
    }

    private function generateCCIAgeProfileId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->CCI_age_profile_id, -4)) + 1 : 1;

        return 'CCI-AP-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
