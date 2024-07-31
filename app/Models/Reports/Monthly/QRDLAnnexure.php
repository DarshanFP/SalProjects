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
        'beneficiary_name',
        'support_date',
        'self_employment',
        'amount_sanctioned',
        'monthly_profit',
        'annual_profit',
        'impact',
        'challenges',
    ];

    public function report()
    {
        return $this->belongsTo(DPReport::class, 'report_id');
    }
}
