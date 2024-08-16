<?php

namespace App\Models\Reports\Monthly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QRDLAnnexure extends Model
{
    use HasFactory;

    protected $table = 'qrdl_annexure';

    protected $fillable = [
        'report_id',
        'dla_beneficiary_name',
        'dla_support_date',
        'dla_self_employment',
        'dla_amount_sanctioned',
        'dla_monthly_profit',
        'dla_annual_profit',
        'dla_impact',
        'dla_challenges',
    ];

    public function report()
    {
        return $this->belongsTo(DPReport::class, 'report_id');
    }
}
