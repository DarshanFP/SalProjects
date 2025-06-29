<?php

namespace App\Models\OldProjects\IIES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IIES_expense_id
 * @property string $project_id
 * @property string $iies_total_expenses
 * @property string $iies_expected_scholarship_govt
 * @property string $iies_support_other_sources
 * @property string $iies_beneficiary_contribution
 * @property string $iies_balance_requested
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\IIES\ProjectIIESExpenseDetail> $expenseDetails
 * @property-read int|null $expense_details_count
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereIIESExpenseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereIiesBalanceRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereIiesBeneficiaryContribution($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereIiesExpectedScholarshipGovt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereIiesSupportOtherSources($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereIiesTotalExpenses($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIIESExpenses whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIIESExpenses extends Model
{
    use HasFactory;

    protected $table = 'project_IIES_expenses';

    protected $fillable = [
        'IIES_expense_id',
        'project_id',
        'iies_total_expenses',
        'iies_expected_scholarship_govt',
        'iies_support_other_sources',
        'iies_beneficiary_contribution',
        'iies_balance_requested'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->IIES_expense_id = $model->generateIIESExpenseId();
        });
    }

    private function generateIIESExpenseId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IIES_expense_id, -4)) + 1 : 1;
        return 'IIES-EXP-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function expenseDetails()
    {
        return $this->hasMany(ProjectIIESExpenseDetail::class, 'IIES_expense_id', 'IIES_expense_id');
    }
}
