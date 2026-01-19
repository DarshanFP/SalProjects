<?php

namespace App\Models\Reports\HalfYearly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HalfYearlyReportDetail extends Model
{
    use HasFactory;

    protected $table = 'half_yearly_report_details';

    protected $fillable = [
        'half_yearly_report_id',
        'particulars',
        'opening_balance',
        'amount_forwarded',
        'amount_sanctioned',
        'total_amount',
        'total_expenses',
        'closing_balance',
        'expenses_by_quarter',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'amount_forwarded' => 'decimal:2',
        'amount_sanctioned' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'expenses_by_quarter' => 'array',
    ];

    // Relationships
    public function halfYearlyReport()
    {
        return $this->belongsTo(HalfYearlyReport::class, 'half_yearly_report_id');
    }
}
