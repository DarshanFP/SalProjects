<?php

namespace App\Models\OldProjects\CCI;

use App\Models\OldProjects\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectCCIAnnexedTargetGroup extends Model
{
    use HasFactory;

    protected $table = 'project_CCI_annexed_target_group';

    protected $fillable = [
        'CCI_target_group_id',
        'project_id',
        'beneficiary_name',
        'dob',
        'date_of_joining',
        'class_of_study',
        'family_background_description',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->CCI_target_group_id = $model->generateCCITargetGroupId();
        });
    }

    private function generateCCITargetGroupId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->CCI_target_group_id, -4)) + 1 : 1;

        return 'CCI-TG-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
