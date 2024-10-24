<?php

namespace App\Models\OldProjects\RST;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectRSTTargetGroup extends Model
{
    use HasFactory;

    protected $table = 'project_RST_target_group';

    protected $fillable = [
        'RST_target_group_id',
        'project_id',
        'no_of_beneficiaries',
        'beneficiaries_description_problems'
    ];

    // Generate unique ID
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->RST_target_group_id = $model->generateTargetGroupId();
        });
    }

    private function generateTargetGroupId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->RST_target_group_id, -4)) + 1 : 1;

        return 'RST-TG-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
