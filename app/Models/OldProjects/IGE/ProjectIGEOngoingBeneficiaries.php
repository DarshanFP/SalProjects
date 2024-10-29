<?php

namespace App\Models\OldProjects\IGE;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectIGEOngoingBeneficiaries extends Model
{
    use HasFactory;

    protected $table = 'project_IGE_ongoing_beneficiaries';

    protected $fillable = [
        'IGE_ongoing_bnfcry_id',
        'project_id',
        'obeneficiary_name',
        'ocaste',
        'oaddress',
        'ocurrent_group_year_of_study',
        'operformance_details'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IGE_ongoing_bnfcry_id = $model->generateIGEOngoingBeneficiariesId();
        });
    }

    private function generateIGEOngoingBeneficiariesId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IGE_ongoing_bnfcry_id, -4)) + 1 : 1;
        return 'IGE-ONGB-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
