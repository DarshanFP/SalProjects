<?php

namespace App\Models\OldProjects\ILP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $ILP_personal_id
 * @property string $project_id
 * @property string|null $name
 * @property int|null $age
 * @property string|null $gender
 * @property string|null $dob
 * @property string|null $email
 * @property string|null $contact_no
 * @property string|null $aadhar_id
 * @property string|null $address
 * @property string|null $occupation
 * @property string|null $marital_status
 * @property string|null $spouse_name
 * @property int|null $children_no
 * @property string|null $children_edu
 * @property string|null $religion
 * @property string|null $caste
 * @property string|null $family_situation
 * @property int $small_business_status
 * @property string|null $small_business_details
 * @property string|null $monthly_income
 * @property string|null $business_plan
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereAadharId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereAge($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereBusinessPlan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereCaste($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereChildrenEdu($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereChildrenNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereContactNo($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereDob($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereFamilySituation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereILPPersonalId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereMaritalStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereMonthlyIncome($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereOccupation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereReligion($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereSmallBusinessDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereSmallBusinessStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereSpouseName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPPersonalInfo whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectILPPersonalInfo extends Model
{
    use HasFactory;

    protected $table = 'project_ILP_personal_info';

    protected $fillable = [
        'ILP_personal_id', 'project_id', 'name', 'age', 'gender', 'dob', 'email', 'contact_no', 'aadhar_id', 'address',
        'occupation', 'marital_status', 'spouse_name', 'children_no', 'children_edu', 'religion', 'caste',
        'family_situation', 'small_business_status', 'small_business_details', 'monthly_income', 'business_plan'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->ILP_personal_id = $model->generateILPPersonalId();
        });
    }

    private function generateILPPersonalId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->ILP_personal_id, -4)) + 1 : 1;
        return 'ILP-PERS-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }
}
