<?php

namespace App\Models\OldProjects\ILP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $ILP_budget_id
 * @property string $project_id
 * @property string|null $budget_desc
 * @property string|null $cost
 * @property string|null $beneficiary_contribution
 * @property string|null $amount_requested
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget whereAmountRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget whereBeneficiaryContribution($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget whereBudgetDesc($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget whereCost($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget whereILPBudgetId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectILPBudget whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectILPBudget extends Model
{
    use HasFactory;

    protected $table = 'project_ILP_budget';

    protected $fillable = [
        'ILP_budget_id', 'project_id', 'budget_desc', 'cost', 'beneficiary_contribution', 'amount_requested'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->ILP_budget_id = $model->generateILPBudgetId();
        });
    }

    private function generateILPBudgetId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->ILP_budget_id, -4)) + 1 : 1;
        return 'ILP-BUD-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }
}
