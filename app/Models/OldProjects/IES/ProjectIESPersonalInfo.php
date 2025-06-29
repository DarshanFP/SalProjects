<?php

namespace App\Models\OldProjects\IES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IES_personal_id
 * @property string $project_id
 * @property string|null $bname
 * @property int|null $age
 * @property string|null $gender
 * @property string|null $dob
 * @property string|null $email
 * @property string|null $contact
 * @property string|null $aadhar
 * @property string|null $full_address
 * @property string|null $father_name
 * @property string|null $mother_name
 * @property string|null $mother_tongue
 * @property string|null $current_studies
 * @property string|null $bcaste
 * @property string|null $father_occupation
 * @property string|null $father_income
 * @property string|null $mother_occupation
 * @property string|null $mother_income
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereAadhar($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereBcaste($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereBname($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereContact($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereCurrentStudies($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereDob($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereFatherIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereFatherName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereFatherOccupation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereFullAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereIESPersonalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereMotherIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereMotherName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereMotherOccupation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereMotherTongue($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESPersonalInfo whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIESPersonalInfo extends Model
{
    use HasFactory;

    protected $table = 'project_IES_personal_info';

    protected $fillable = [
        'IES_personal_id',
        'project_id',
        'bname',
        'age',
        'gender',
        'dob',
        'email',
        'contact',
        'aadhar',
        'full_address',
        'father_name',
        'mother_name',
        'mother_tongue',
        'current_studies',
        'bcaste',
        'father_occupation',
        'father_income',
        'mother_occupation',
        'mother_income'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->IES_personal_id = $model->generateIESPersonalId();
        });
    }

    private function generateIESPersonalId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IES_personal_id, -4)) + 1 : 1;
        return 'IES-PERS-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
