<?php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $cic_basic_info_id
 * @property string $project_id
 * @property int|null $number_served_since_inception
 * @property int|null $number_served_previous_year
 * @property string|null $beneficiary_categories
 * @property string|null $sisters_intervention
 * @property string|null $beneficiary_conditions
 * @property string|null $beneficiary_problems
 * @property string|null $institution_challenges
 * @property string|null $support_received
 * @property string|null $project_need
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereBeneficiaryCategories($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereBeneficiaryConditions($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereBeneficiaryProblems($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereCicBasicInfoId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereInstitutionChallenges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereNumberServedPreviousYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereNumberServedSinceInception($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereProjectNeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereSistersIntervention($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereSupportReceived($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCICBasicInfo whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
