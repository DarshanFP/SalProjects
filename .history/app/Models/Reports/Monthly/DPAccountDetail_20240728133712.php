<?php

namespace App\Models\Reports\Monthly;

use Illuminate\Database\Eloquent\Model;
use DB;

class DPAccountDetail extends Model
{
    protected $fillable = [
        'project_id', 'report_id', 'particulars', 'amount_forwarded', 'amount_sanctioned',
        'total_amount', 'expenses_last_month', 'expenses_this_month', 'total_expenses', 'balance_amount'
    ];

    // Method to update expenses and recalculate the balance
    public function updateExpensesAndBalance($expensesLastMonth, $expensesThisMonth) {
        DB::transaction(function () use ($expensesLastMonth, $expensesThisMonth) {
            $this->expenses_last_month = $expensesLastMonth;
            $this->expenses_this_month = $expensesThisMonth;
            $this->total_expenses = $this->expenses_last_month + $this->expenses_this_month;
            $this->balance_amount = $this->total_amount - $this->total_expenses;
            $this->save();
        });
    }
}
