<?php

namespace App\Models\Reports\Quarterly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQDLAccountDetail extends Model
{
    use HasFactory;

    protected $table = 'rqdl_account_details';

    protected $fillable = [
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
        return $this->belongsTo(RQDLReport::class, 'report_id');
    }
}
