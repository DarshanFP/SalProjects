<?php

namespace App\Models\Reports\Quarterly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQDPOutlook extends Model
{
    use HasFactory;

    protected $table = 'rqdp_outlooks';

    protected $fillable = [
        'report_id',
        'date',
        'plan_next_month',
    ];

    public function report()
    {
        return $this->belongsTo(RQDPReport::class, 'report_id');
    }
}
