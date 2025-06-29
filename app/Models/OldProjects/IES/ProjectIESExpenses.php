<?php

namespace App\Models\OldProjects\IES;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\OldProjects\Project;

/**
 * 
 *
 * @property int $id
 * @property string $IES_expense_id
 * @property string $project_id
 * @property string|null $total_expenses
 * @property string|null $expected_scholarship_govt
 * @property string|null $support_other_sources
 * @property string|null $beneficiary_contribution
 * @property string|null $balance_requested
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\OldProjects\IES\ProjectIESExpenseDetail> $expenseDetails
 * @property-read int|null $expense_details_count
 * @property-read Project|null $project
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses query()
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereBalanceRequested($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereBeneficiaryContribution($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereExpectedScholarshipGovt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereIESExpenseId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereSupportOtherSources($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereTotalExpenses($value)
 * @method static \Illuminate\Database\Eloquent\Builder|ProjectIESExpenses whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ProjectIESExpenses extends Model
{
    use HasFactory;

    protected $table = 'project_IES_expenses';

    protected $fillable = [
        'IES_expense_id',
        'project_id',
        'total_expenses',
        'expected_scholarship_govt',
        'support_other_sources',
        'beneficiary_contribution',
        'balance_requested'
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->IES_expense_id = $model->generateIESExpenseId();
        });
    }

    private function generateIESExpenseId()
    {
        $latest = self::latest('id')->first();
        $sequenceNumber = $latest ? intval(substr($latest->IES_expense_id, -4)) + 1 : 1;
        return 'IES-EXP-' . str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id', 'project_id');
    }

    public function expenseDetails()
{
    return $this->hasMany(ProjectIESExpenseDetail::class, 'IES_expense_id', 'IES_expense_id');
}



}
