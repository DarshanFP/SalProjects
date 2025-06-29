<?php

namespace App\Models\OldProjects\LDP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $LDP_intervention_logic_id
 * @property string $project_id
 * @property string|null $intervention_description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPInterventionLogic newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPInterventionLogic newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPInterventionLogic query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPInterventionLogic whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPInterventionLogic whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPInterventionLogic whereInterventionDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPInterventionLogic whereLDPInterventionLogicId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPInterventionLogic whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectLDPInterventionLogic whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
