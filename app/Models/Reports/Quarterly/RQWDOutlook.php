<?php

namespace App\Models\Reports\Quarterly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQWDOutlook extends Model
{
    use HasFactory;

    protected $table = 'rqwd_outlooks';

    protected $fillable = [
        'report_id',
        'date',
        'plan_next_month',
    ];

    public function report()
    {
        return $this->belongsTo(RQWDReport::class, 'report_id');
    }
}
