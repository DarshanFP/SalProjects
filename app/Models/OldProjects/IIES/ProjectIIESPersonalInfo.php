<?php

namespace App\Models\OldProjects\IIES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectIIESPersonalInfo extends Model
{
    use HasFactory;

    protected $table = 'project_IIES_personal_info';

    protected $fillable = [
        'IIES_personal_id',
        'project_id',
        'iies_bname',
        'iies_age',
        'iies_gender',
        'iies_dob',
        'iies_email',
        'iies_contact',
        'iies_aadhar',
        'iies_full_address',
        'iies_father_name',
        'iies_mother_name',
        'iies_mother_tongue',
        'iies_current_studies',
        'iies_bcaste',
        'iies_father_occupation',
        'iies_father_income',
        'iies_mother_occupation',
        'iies_mother_income',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->IIES_personal_id = $model->generateIIESPersonalId();
        });
    }

    private function generateIIESPersonalId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IIES_personal_id, -4)) + 1 : 1;
        return 'IIES-PERS-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
