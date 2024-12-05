<?php

namespace App\Models\OldProjects\RST;

use App\Models\OldProjects\Project;
use Illuminate\Database\Eloquent\Model;

class ProjectDPRSTBeneficiariesArea extends Model
{
    protected $table = 'project_RST_DP_beneficiaries_area';
    


    protected $fillable = [
        'project_area',
        'category_beneficiary',
        'direct_beneficiaries',
        'indirect_beneficiaries',
        'project_id',
        'DPRST_bnfcrs_area_id',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->DPRST_bnfcrs_area_id = $model->generateBeneficiariesAreaId();
        });
    }

    private function generateBeneficiariesAreaId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->DPRST_bnfcrs_area_id, -4)) + 1 : 1;

        return 'RST-BA-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
