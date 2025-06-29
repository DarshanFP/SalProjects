<?php

namespace App\Models\OldProjects\CCI;

use App\Models\OldProjects\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $CCI_age_profile_id
 * @property string $project_id
 * @property int|null $education_below_5_bridge_course_prev_year
 * @property int|null $education_below_5_bridge_course_current_year
 * @property int|null $education_below_5_kindergarten_prev_year
 * @property int|null $education_below_5_kindergarten_current_year
 * @property string|null $education_below_5_other_prev_year
 * @property string|null $education_below_5_other_current_year
 * @property int|null $education_6_10_primary_school_prev_year
 * @property int|null $education_6_10_primary_school_current_year
 * @property int|null $education_6_10_bridge_course_prev_year
 * @property int|null $education_6_10_bridge_course_current_year
 * @property string|null $education_6_10_other_prev_year
 * @property string|null $education_6_10_other_current_year
 * @property int|null $education_11_15_secondary_school_prev_year
 * @property int|null $education_11_15_secondary_school_current_year
 * @property int|null $education_11_15_high_school_prev_year
 * @property int|null $education_11_15_high_school_current_year
 * @property string|null $education_11_15_other_prev_year
 * @property string|null $education_11_15_other_current_year
 * @property int|null $education_16_above_undergraduate_prev_year
 * @property int|null $education_16_above_undergraduate_current_year
 * @property int|null $education_16_above_technical_vocational_prev_year
 * @property int|null $education_16_above_technical_vocational_current_year
 * @property string|null $education_16_above_other_prev_year
 * @property string|null $education_16_above_other_current_year
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereCCIAgeProfileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation1115HighSchoolCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation1115HighSchoolPrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation1115OtherCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation1115OtherPrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation1115SecondarySchoolCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation1115SecondarySchoolPrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation16AboveOtherCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation16AboveOtherPrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation16AboveTechnicalVocationalCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation16AboveTechnicalVocationalPrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation16AboveUndergraduateCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation16AboveUndergraduatePrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation610BridgeCourseCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation610BridgeCoursePrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation610OtherCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation610OtherPrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation610PrimarySchoolCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducation610PrimarySchoolPrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducationBelow5BridgeCourseCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducationBelow5BridgeCoursePrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducationBelow5KindergartenCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducationBelow5KindergartenPrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducationBelow5OtherCurrentYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereEducationBelow5OtherPrevYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIAgeProfile whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectCCIAgeProfile extends Model

{
    use HasFactory;

    protected $table = 'project_CCI_age_profile';
    // Specify primary key and its type
    protected $primaryKey = 'CCI_age_profile_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'CCI_age_profile_id',
        'project_id',
        // 'age_category',
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
