<?php

namespace App\Models\OldProjects\IIES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IIES_education_id
 * @property string $project_id
 * @property string|null $prev_education
 * @property string|null $prev_institution
 * @property string|null $prev_insti_address
 * @property string|null $prev_marks
 * @property string|null $current_studies
 * @property string|null $curr_institution
 * @property string|null $curr_insti_address
 * @property string|null $aspiration
 * @property string|null $long_term_effect
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereAspiration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereCurrInstiAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereCurrInstitution($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereCurrentStudies($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereIIESEducationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereLongTermEffect($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground wherePrevEducation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground wherePrevInstiAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground wherePrevInstitution($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground wherePrevMarks($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESEducationBackground whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIIESEducationBackground extends Model
{
    use HasFactory;

    protected $table = 'project_IIES_education_background';

    protected $fillable = [
        'IIES_education_id',
        'project_id',
        'prev_education',
        'prev_institution',
        'prev_insti_address',
        'prev_marks',
        'current_studies',
        'curr_institution',
        'curr_insti_address',
        'aspiration',
        'long_term_effect'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IIES_education_id = $model->generateIIESEducationId();
        });
    }

    private function generateIIESEducationId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IIES_education_id, -4)) + 1 : 1;
        return 'IIES-EDU-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
