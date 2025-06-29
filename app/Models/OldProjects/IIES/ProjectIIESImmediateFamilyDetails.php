<?php

namespace App\Models\OldProjects\IIES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IIES_family_detail_id
 * @property string $project_id
 * @property int $iies_mother_expired
 * @property int $iies_father_expired
 * @property int $iies_grandmother_support
 * @property int $iies_grandfather_support
 * @property int $iies_father_deserted
 * @property string|null $iies_family_details_others
 * @property int $iies_father_sick
 * @property int $iies_father_hiv_aids
 * @property int $iies_father_disabled
 * @property int $iies_father_alcoholic
 * @property string|null $iies_father_health_others
 * @property int $iies_mother_sick
 * @property int $iies_mother_hiv_aids
 * @property int $iies_mother_disabled
 * @property int $iies_mother_alcoholic
 * @property string|null $iies_mother_health_others
 * @property int $iies_own_house
 * @property int $iies_rented_house
 * @property string|null $iies_residential_others
 * @property string|null $iies_family_situation
 * @property string|null $iies_assistance_need
 * @property int $iies_received_support
 * @property string|null $iies_support_details
 * @property int $iies_employed_with_stanns
 * @property string|null $iies_employment_details
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIIESFamilyDetailId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesAssistanceNeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesEmployedWithStanns($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesEmploymentDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesFamilyDetailsOthers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesFamilySituation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesFatherAlcoholic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesFatherDeserted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesFatherDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesFatherExpired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesFatherHealthOthers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesFatherHivAids($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesFatherSick($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesGrandfatherSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesGrandmotherSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesMotherAlcoholic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesMotherDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesMotherExpired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesMotherHealthOthers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesMotherHivAids($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesMotherSick($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesOwnHouse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesReceivedSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesRentedHouse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesResidentialOthers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereIiesSupportDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESImmediateFamilyDetails whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIIESImmediateFamilyDetails extends Model
{
    use HasFactory;

    protected $table = 'project_IIES_immediate_family_details';

    protected $fillable = [
        'IIES_family_detail_id',
        'project_id',
        'iies_mother_expired',
        'iies_father_expired',
        'iies_grandmother_support',
        'iies_grandfather_support',
        'iies_father_deserted',
        'iies_family_details_others',
        'iies_father_sick',
        'iies_father_hiv_aids',
        'iies_father_disabled',
        'iies_father_alcoholic',
        'iies_father_health_others',
        'iies_mother_sick',
        'iies_mother_hiv_aids',
        'iies_mother_disabled',
        'iies_mother_alcoholic',
        'iies_mother_health_others',
        'iies_own_house',
        'iies_rented_house',
        'iies_residential_others',
        'iies_family_situation',
        'iies_assistance_need',
        'iies_received_support',
        'iies_support_details',
        'iies_employed_with_stanns',
        'iies_employment_details'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->IIES_family_detail_id = $model->generateIIESFamilyDetailId();
        });
    }

    private function generateIIESFamilyDetailId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IIES_family_detail_id, -4)) + 1 : 1;
        return 'IIES-FAM-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
