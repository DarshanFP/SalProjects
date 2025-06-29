<?php

namespace App\Models\OldProjects\IGE;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IGE_institution_id
 * @property string $project_id
 * @property string|null $institutional_type
 * @property string|null $age_group
 * @property int|null $previous_year_beneficiaries
 * @property string|null $outcome_impact
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo whereAgeGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo whereIGEInstitutionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo whereInstitutionalType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo whereOutcomeImpact($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo wherePreviousYearBeneficiaries($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGEInstitutionInfo whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIGEInstitutionInfo extends Model
{
    use HasFactory;

    protected $table = 'project_IGE_institution_info';

    protected $fillable = [
        'IGE_institution_id',
        'project_id',
        'institutional_type',
        'age_group',
        'previous_year_beneficiaries',
        'outcome_impact'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IGE_institution_id = $model->generateIGEInstitutionId();
        });
    }

    private function generateIGEInstitutionId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IGE_institution_id, -4)) + 1 : 1;
        return 'IGE-INST-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
