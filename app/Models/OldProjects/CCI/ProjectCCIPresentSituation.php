<?php

namespace App\Models\OldProjects\CCI;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project; // Import the Project model for the relationship

/**
 * 
 *
 * @property int $id
 * @property string $CCI_present_situation_id
 * @property string $project_id
 * @property string|null $internal_challenges
 * @property string|null $external_challenges
 * @property string|null $area_of_focus
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation whereAreaOfFocus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation whereCCIPresentSituationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation whereExternalChallenges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation whereInternalChallenges($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectCCIPresentSituation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectCCIPresentSituation extends Model
{
    use HasFactory;

    protected $table = 'project_CCI_present_situation';

    protected $fillable = [
        'CCI_present_situation_id',
        'project_id',
        'internal_challenges',
        'external_challenges',
        'area_of_focus', // Area of focus for the current year
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->CCI_present_situation_id = $model->generateCCIPresentSituationId();
        });
    }

    private function generateCCIPresentSituationId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->CCI_present_situation_id, -4)) + 1 : 1;

        return 'CCI-PS-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationship with the Project model
    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
