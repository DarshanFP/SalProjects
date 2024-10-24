<?php

namespace App\Models\OldProjects;

use App\Models\OldProjects\Project;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectEduRUTBasicInfo extends Model
{
    use HasFactory;

    protected $table = 'Project_EduRUT_Basic_Info';

    protected $fillable = [
        'operational_area_id',
        'project_id',
        'institution_type',
        'group_type',
        'category',
        'project_location',
        'sisters_work',
        'conditions',
        'problems',
        'need',
        'criteria',
    ];

    // Automatically generate operational_area_id
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->operational_area_id = $model->generateOperationalAreaId();
        });
    }

    // Method to generate a unique ID for operational_area_id
    private function generateOperationalAreaId()
    {
        $latestOperationalArea = self::latest('id')->first();
        $sequenceNumber = $latestOperationalArea ? intval(substr($latestOperationalArea->operational_area_id, -4)) + 1 : 1;

        return 'EDURUT-OA-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with the project
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
