<?php

namespace App\Models\OldProjects\IES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IES_family_detail_id
 * @property string $project_id
 * @property int $mother_expired
 * @property int $father_expired
 * @property int $grandmother_support
 * @property int $grandfather_support
 * @property int $father_deserted
 * @property string|null $family_details_others
 * @property int $father_sick
 * @property int $father_hiv_aids
 * @property int $father_disabled
 * @property int $father_alcoholic
 * @property string|null $father_health_others
 * @property int $mother_sick
 * @property int $mother_hiv_aids
 * @property int $mother_disabled
 * @property int $mother_alcoholic
 * @property string|null $mother_health_others
 * @property int $own_house
 * @property int $rented_house
 * @property string|null $residential_others
 * @property string|null $family_situation
 * @property string|null $assistance_need
 * @property int $received_support
 * @property string|null $support_details
 * @property int $employed_with_stanns
 * @property string|null $employment_details
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereAssistanceNeed($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereEmployedWithStanns($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereEmploymentDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereFamilyDetailsOthers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereFamilySituation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereFatherAlcoholic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereFatherDeserted($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereFatherDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereFatherExpired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereFatherHealthOthers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereFatherHivAids($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereFatherSick($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereGrandfatherSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereGrandmotherSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereIESFamilyDetailId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereMotherAlcoholic($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereMotherDisabled($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereMotherExpired($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereMotherHealthOthers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereMotherHivAids($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereMotherSick($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereOwnHouse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereReceivedSupport($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereRentedHouse($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereResidentialOthers($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereSupportDetails($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESImmediateFamilyDetails whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIESImmediateFamilyDetails extends Model
{
    use HasFactory;

    protected $table = 'project_IES_immediate_family_details';

    protected $fillable = [
        'IES_family_detail_id',
        'project_id',
        'mother_expired',
        'father_expired',
        'grandmother_support',
        'grandfather_support',
        'father_deserted',
        'family_details_others',
        'father_sick',
        'father_hiv_aids',
        'father_disabled',
        'father_alcoholic',
        'father_health_others',
        'mother_sick',
        'mother_hiv_aids',
        'mother_disabled',
        'mother_alcoholic',
        'mother_health_others',
        'own_house',
        'rented_house',
        'residential_others',
        'family_situation',
        'assistance_need',
        'received_support',
        'support_details',
        'employed_with_stanns',
        'employment_details'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->IES_family_detail_id = $model->generateIESFamilyDetailId();
        });
    }

    private function generateIESFamilyDetailId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IES_family_detail_id, -4)) + 1 : 1;
        return 'IES-FAMDET-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
