<?php

namespace App\Models\OldProjects\RST;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $RST_institution_id
 * @property string $project_id
 * @property string|null $year_setup
 * @property int|null $total_students_trained
 * @property int|null $beneficiaries_last_year
 * @property string|null $training_outcome
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo whereBeneficiariesLastYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo whereRSTInstitutionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo whereTotalStudentsTrained($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo whereTrainingOutcome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectRSTInstitutionInfo whereYearSetup($value)
 * @mixin \Eloquent
 */
class ProjectRSTInstitutionInfo extends Model
{
    use HasFactory;

    protected $table = 'project_RST_institution_info';

    protected $fillable = [
        'RST_institution_id',
        'project_id',
        'year_setup',
        'total_students_trained',
        'beneficiaries_last_year',
        'training_outcome'
    ];

    // Generate unique ID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->RST_institution_id = $model->generateInstitutionId();
        });
    }

    private function generateInstitutionId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->RST_institution_id, -4)) + 1 : 1;

        return 'RST-INS-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
