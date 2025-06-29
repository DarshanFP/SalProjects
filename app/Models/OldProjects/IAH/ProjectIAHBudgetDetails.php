<?php

namespace App\Models\OldProjects\IAH;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IAH_budget_id
 * @property string $project_id
 * @property string|null $particular
 * @property string|null $amount
 * @property string|null $total_expenses
 * @property string|null $family_contribution
 * @property string|null $amount_requested
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereAmountRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereFamilyContribution($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereIAHBudgetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereParticular($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereTotalExpenses($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIAHBudgetDetails whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIAHBudgetDetails extends Model
{
    use HasFactory;

    protected $table = 'project_IAH_budget_details';

    protected $fillable = [
        'IAH_budget_id',
        'project_id',
        'particular',
        'amount',
        'total_expenses',
        'family_contribution',
        'amount_requested',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->IAH_budget_id = $model->generateIAHBudgetId();
        });
    }

    private function generateIAHBudgetId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IAH_budget_id, -4)) + 1 : 1;
        return 'IAH-BUDG-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }
}
