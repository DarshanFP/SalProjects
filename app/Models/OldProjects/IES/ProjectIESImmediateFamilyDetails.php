<?php

namespace App\Models\OldProjects\IES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

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
