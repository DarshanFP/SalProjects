<?php

namespace App\Models\OldProjects\CCI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

class ProjectCCIPersonalSituation extends Model
{
    use HasFactory;

    protected $table = 'project_CCI_personal_situation';

    protected $fillable = [
        'CCI_personal_situation_id',
        'project_id',
        'children_with_parents_last_year',
        'children_with_parents_current_year',
        'semi_orphans_last_year',
        'semi_orphans_current_year',
        'orphans_last_year',
        'orphans_current_year',
        'hiv_infected_last_year',
        'hiv_infected_current_year',
        'differently_abled_last_year',
        'differently_abled_current_year',
        'parents_in_conflict_last_year',
        'parents_in_conflict_current_year',
        'other_ailments_last_year',
        'other_ailments_current_year',
        'general_remarks', 

    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->CCI_personal_situation_id = $model->generateCCIPersonalSituationId();
        });
    }

    private function generateCCIPersonalSituationId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->CCI_personal_situation_id, -4)) + 1 : 1;

        return 'CCI-PS-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with the Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
