<?php

namespace App\Models\OldProjects\ILP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
