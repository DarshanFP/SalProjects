<?php

namespace App\Models\Reports\Quarterly;

use App\Models\Reports\Quarterly\RQSTReport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQSTAccountDetails extends Model
{
    use HasFactory;

    protected $table = 'rqst_account_details';

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
        return $this->belongsTo(RQSTReport::class, 'report_id');
    }
}
