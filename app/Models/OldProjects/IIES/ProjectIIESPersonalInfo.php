<?php

namespace App\Models\OldProjects\IIES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IIES_personal_id
 * @property string $project_id
 * @property string $iies_bname
 * @property int|null $iies_age
 * @property string|null $iies_gender
 * @property string|null $iies_dob
 * @property string|null $iies_email
 * @property string|null $iies_contact
 * @property string|null $iies_aadhar
 * @property string|null $iies_full_address
 * @property string|null $iies_father_name
 * @property string|null $iies_mother_name
 * @property string|null $iies_mother_tongue
 * @property string|null $iies_current_studies
 * @property string|null $iies_bcaste
 * @property string|null $iies_father_occupation
 * @property string|null $iies_father_income
 * @property string|null $iies_mother_occupation
 * @property string|null $iies_mother_income
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIIESPersonalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesAadhar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesBcaste($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesBname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesCurrentStudies($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesDob($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesFatherIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesFatherName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesFatherOccupation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesFullAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesMotherIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesMotherName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesMotherOccupation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereIiesMotherTongue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESPersonalInfo whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIIESPersonalInfo extends Model
{
    use HasFactory;

    protected $table = 'project_IIES_personal_info';

    protected $fillable = [
        'IIES_personal_id',
        'project_id',
        'iies_bname',
        'iies_age',
        'iies_gender',
        'iies_dob',
        'iies_email',
        'iies_contact',
        'iies_aadhar',
        'iies_full_address',
        'iies_father_name',
        'iies_mother_name',
        'iies_mother_tongue',
        'iies_current_studies',
        'iies_bcaste',
        'iies_father_occupation',
        'iies_father_income',
        'iies_mother_occupation',
        'iies_mother_income',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->IIES_personal_id = $model->generateIIESPersonalId();
        });
    }

    private function generateIIESPersonalId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IIES_personal_id, -4)) + 1 : 1;
        return 'IIES-PERS-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
