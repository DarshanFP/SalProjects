<?php

namespace App\Models\OldProjects\IAH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IAH_health_id
 * @property string $project_id
 * @property string|null $illness
 * @property int|null $treatment
 * @property string|null $doctor
 * @property string|null $hospital
 * @property string|null $doctor_address
 * @property string|null $health_situation
 * @property string|null $family_situation
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereDoctor($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereDoctorAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereFamilySituation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereHealthSituation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereHospital($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereIAHHealthId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereIllness($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereTreatment($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHHealthCondition whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
