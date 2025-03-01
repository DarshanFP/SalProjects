<?php

namespace App\Models\OldProjects\IIES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

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
