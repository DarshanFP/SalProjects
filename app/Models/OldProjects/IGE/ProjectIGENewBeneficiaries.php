<?php

namespace App\Models\OldProjects\IGE;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IGE_new_beneficiaries_id
 * @property string $project_id
 * @property string|null $beneficiary_name
 * @property string|null $caste
 * @property string|null $address
 * @property string|null $group_year_of_study
 * @property string|null $family_background_need
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereAddress($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereBeneficiaryName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereCaste($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereFamilyBackgroundNeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereGroupYearOfStudy($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereIGENewBeneficiariesId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIGENewBeneficiaries whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIGENewBeneficiaries extends Model
{
    use HasFactory;

    protected $table = 'project_IGE_new_beneficiaries';

    protected $fillable = [
        'IGE_new_beneficiaries_id',
        'project_id',
        'beneficiary_name',
        'caste',
        'address',
        'group_year_of_study',
        'family_background_need'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IGE_new_beneficiaries_id = $model->generateIGENewBeneficiariesId();
        });
    }

    private function generateIGENewBeneficiariesId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IGE_new_beneficiaries_id, -4)) + 1 : 1;
        return 'IGE-NBEN-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
