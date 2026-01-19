<?php

namespace App\Models\Reports\Quarterly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuarterlyReportDetail extends Model
{
    use HasFactory;

    protected $table = 'quarterly_report_details';

    protected $fillable = [
        'quarterly_report_id',
        'particulars',
        'opening_balance',
        'amount_forwarded',
        'amount_sanctioned',
        'total_amount',
        'total_expenses',
        'closing_balance',
        'expenses_by_month',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'amount_forwarded' => 'decimal:2',
        'amount_sanctioned' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'expenses_by_month' => 'array',
    ];

    // Relationships
    public function quarterlyReport()
    {
        return $this->belongsTo(QuarterlyReport::class, 'quarterly_report_id');
    }
}
