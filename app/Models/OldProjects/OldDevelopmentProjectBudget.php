<?php

namespace App\Models\OldProjects;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property int $project_id
 * @property int $phase
 * @property string $description
 * @property string $rate_quantity
 * @property string $rate_multiplier
 * @property string $rate_duration
 * @property string|null $rate_increase
 * @property string $this_phase
 * @property string $next_phase
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\OldProjects\OldDevelopmentProject $project
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget query()
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereNextPhase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget wherePhase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereRateDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereRateIncrease($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereRateMultiplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereRateQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereThisPhase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|OldDevelopmentProjectBudget whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class OldDevelopmentProjectBudget extends Model
{
    use HasFactory;

    protected $table = 'old_DP_budgets';

    protected $fillable = [
        'project_id',
        'phase',
        'description',
        'rate_quantity',
        'rate_multiplier',
        'rate_duration',
        'rate_increase',
        'this_phase',
        'next_phase',
    ];

    public function project()
    {
        return $this->belongsTo(OldDevelopmentProject::class, 'project_id');
    }
}
