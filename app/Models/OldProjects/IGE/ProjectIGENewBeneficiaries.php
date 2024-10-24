<?php

namespace App\Models\OldProjects\IGE;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

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
