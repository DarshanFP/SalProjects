<?php

namespace App\Models\OldProjects\IES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IES_education_id
 * @property string $project_id
 * @property string|null $previous_class
 * @property string|null $amount_sanctioned
 * @property string|null $amount_utilized
 * @property string|null $scholarship_previous_year
 * @property string|null $academic_performance
 * @property string|null $present_class
 * @property string|null $expected_scholarship
 * @property string|null $family_contribution
 * @property string|null $reason_no_support
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereAcademicPerformance($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereAmountSanctioned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereAmountUtilized($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereExpectedScholarship($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereFamilyContribution($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereIESEducationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground wherePresentClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground wherePreviousClass($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereReasonNoSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereScholarshipPreviousYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESEducationBackground whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
