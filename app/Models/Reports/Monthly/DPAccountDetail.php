<?php

namespace App\Models\Reports\Monthly;

use App\Models\OldProjects\ProjectBudget;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

/**
 *
 *
 * @property int $account_detail_id
 * @property string $project_id
 * @property string $report_id
 * @property string|null $particulars
 * @property string|null $amount_forwarded
 * @property string|null $amount_sanctioned
 * @property string|null $total_amount
 * @property string|null $expenses_last_month
 * @property string|null $expenses_this_month
 * @property string|null $total_expenses
 * @property string|null $balance_amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read ProjectBudget $projectBudget
 * @property-read \App\Models\Reports\Monthly\DPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereAccountDetailId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereAmountForwarded($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereAmountSanctioned($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereBalanceAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereExpensesLastMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereExpensesThisMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereParticulars($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereTotalExpenses($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPAccountDetail whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class DPAccountDetail extends Model
{
    use HasFactory;

    protected $table = 'DP_AccountDetails';
    protected $primaryKey = 'account_detail_id';

    protected $fillable = [
        'project_id',
        'report_id',
        'particulars',
        'amount_forwarded',
        'amount_sanctioned',
        'total_amount',
        'expenses_last_month',
        'expenses_this_month',
        'total_expenses',
        'balance_amount',
        'is_budget_row',
    ];
    public function report()
    {
        return $this->belongsTo(DPReport::class, 'report_id', 'report_id');
    }
    public function projectBudget()
    {
        return $this->belongsTo(ProjectBudget::class, 'project_id', 'project_id');
    }


}
