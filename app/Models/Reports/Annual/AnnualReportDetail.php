<?php

namespace App\Models\Reports\Annual;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnnualReportDetail extends Model
{
    use HasFactory;

    protected $table = 'annual_report_details';

    protected $fillable = [
        'annual_report_id',
        'particulars',
        'opening_balance',
        'amount_forwarded',
        'amount_sanctioned',
        'total_amount',
        'total_expenses',
        'closing_balance',
        'expenses_by_half_year',
        'expenses_by_quarter',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'amount_forwarded' => 'decimal:2',
        'amount_sanctioned' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'closing_balance' => 'decimal:2',
        'expenses_by_half_year' => 'array',
        'expenses_by_quarter' => 'array',
    ];

    // Relationships
    public function annualReport()
    {
        return $this->belongsTo(AnnualReport::class, 'annual_report_id');
    }
}
