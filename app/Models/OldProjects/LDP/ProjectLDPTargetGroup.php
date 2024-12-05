<?php

namespace App\Models\OldProjects\LDP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectLDPTargetGroup extends Model
{
    use HasFactory;

    protected $table = 'project_LDP_target_group';

    protected $fillable = [
        'LDP_target_group_id',
        'project_id',
        'L_beneficiary_name',
        'L_family_situation',
        'L_nature_of_livelihood',
        'L_amount_requested',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->LDP_target_group_id = $model->generateLDPTargetGroupId();
        });
    }

    private function generateLDPTargetGroupId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->LDP_target_group_id, -4)) + 1 : 1;

        return 'LDP-TG-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with the Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
