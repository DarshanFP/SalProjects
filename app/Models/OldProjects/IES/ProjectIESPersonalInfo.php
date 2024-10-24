<?php

namespace App\Models\OldProjects\IES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectIESPersonalInfo extends Model
{
    use HasFactory;

    protected $table = 'project_IES_personal_info';

    protected $fillable = [
        'IES_personal_id',
        'project_id',
        'name',
        'age',
        'gender',
        'dob',
        'email',
        'contact',
        'aadhar',
        'full_address',
        'father_name',
        'mother_name',
        'mother_tongue',
        'current_studies',
        'caste',
        'father_occupation',
        'father_income',
        'mother_occupation',
        'mother_income'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->IES_personal_id = $model->generateIESPersonalId();
        });
    }

    private function generateIESPersonalId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IES_personal_id, -4)) + 1 : 1;
        return 'IES-PERS-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
