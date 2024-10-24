<?php

namespace App\Models\OldProjects\IAH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectIAHPersonalInfo extends Model
{
    use HasFactory;

    protected $table = 'project_IAH_personal_info';

    protected $fillable = [
        'IAH_info_id',
        'project_id',
        'name',
        'age',
        'gender',
        'dob',
        'aadhar',
        'contact',
        'address',
        'email',
        'guardian_name',
        'children',
        'caste',
        'religion',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IAH_info_id = $model->generateIAHInfoId();
        });
    }

    private function generateIAHInfoId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IAH_info_id, -4)) + 1 : 1;
        return 'IAH-INFO-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
