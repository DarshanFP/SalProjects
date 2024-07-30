<?php

namespace App\Models\Reports\Monthly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DPAccountDetail extends Model
{
    use HasFactory;

    protected $table = 'DP_AccountDetails';
    protected $primaryKey = 'account_detail_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'account_detail_id',
        'report_id',
        'particulars',
        'amount_forwarded',
        'amount_sanctioned',
        'total_amount',
        'expenses_last_month',
        'expenses_this_month',
        'total_expenses',
        'balance_amount',
    ];

    public function report()
    {
        return $this->belongsTo(DPReport::class, 'report_id', 'report_id');
    }
///
    public function updateBalance()
    {
        // Calculate total expenses from all previous months
        $totalExpenses = DPAccountDetail::where('report_id', $this->report_id)
            ->where('particulars', $this->particulars)
            ->sum('expenses_this_month');

        // Update current total expenses and balance amount
        $this->total_expenses = $totalExpenses + $this->expenses_this_month;
        $this->balance_amount = $this->amount_sanctioned - $this->total_expenses;
        $this->save();
    }
}
