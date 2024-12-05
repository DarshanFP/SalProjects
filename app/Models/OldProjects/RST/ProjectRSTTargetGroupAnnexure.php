<?php

namespace App\Models\OldProjects\RST;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectRSTTargetGroupAnnexure extends Model
{
    use HasFactory;

    protected $table = 'project_RST_target_group_annexure';

    protected $fillable = [
        'target_group_anxr_id',
        'project_id',
        'rst_name',
        'rst_religion',
        'rst_caste',
        'rst_education_background',
        'rst_family_situation',
        'rst_paragraph'
    ];

    // Generate unique ID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->target_group_anxr_id = $model->generateTargetGroupAnnexureId();
        });
    }

    private function generateTargetGroupAnnexureId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->target_group_anxr_id, -4)) + 1 : 1;

        return 'RST-TGA-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
