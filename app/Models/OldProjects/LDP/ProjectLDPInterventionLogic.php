<?php

namespace App\Models\OldProjects\LDP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectLDPInterventionLogic extends Model
{
    use HasFactory;

    protected $table = 'project_LDP_intervention_logic';

    protected $fillable = [
        'LDP_intervention_logic_id',
        'project_id',
        'intervention_description',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->LDP_intervention_logic_id = $model->generateLDPInterventionLogicId();
        });
    }

    private function generateLDPInterventionLogicId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->LDP_intervention_logic_id, -4)) + 1 : 1;

        return 'LDP-IL-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with the Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
