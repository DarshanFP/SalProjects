<?php

namespace App\Models\OldProjects;

use App\Models\Reports\Monthly\DPAccountDetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $project_id
 * @property int|null $phase
 * @property string|null $particular
 * @property string|null $rate_quantity
 * @property string|null $rate_multiplier
 * @property string|null $rate_duration
 * @property string|null $rate_increase
 * @property string|null $this_phase
 * @property string|null $next_phase
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, DPAccountDetail> $dpAccountDetails
 * @property-read int|null $dp_account_details_count
 * @property-read \App\Models\OldProjects\Project $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereNextPhase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereParticular($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget wherePhase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereRateDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereRateIncrease($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereRateMultiplier($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereRateQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereThisPhase($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectBudget whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectBudget extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'phase',
        'particular',
        'rate_quantity',
        'rate_multiplier',
        'rate_duration',
        'rate_increase',
        'this_phase',
        'next_phase'
    ];

    // public function project()
    // {
    //     return $this->belongsTo(Project::class);
    // }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    // for calculation of total amount of a particular phase

    public function dpAccountDetails()
    {
        return $this->hasMany(DPAccountDetail::class, 'project_id', 'project_id');
    }

    public function calculateTotalBudget()
    {
        return $this->rate_quantity * $this->rate_multiplier * $this->rate_duration * $this->rate_increase;
    }

    public function calculateRemainingBalance()
    {
        $totalExpenses = $this->dpAccountDetails()->sum('total_expenses');
        return $this->calculateTotalBudget() - $totalExpenses;
    }
}
