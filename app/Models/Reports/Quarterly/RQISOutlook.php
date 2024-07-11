<?php

namespace App\Models\Reports\Quarterly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQISOutlook extends Model
{
    use HasFactory;

    protected $table = 'rqis_outlooks';

    protected $fillable = [
        'report_id',
        'date',
        'plan_next_month',
    ];

    public function report()
    {
        return $this->belongsTo(RQISReport::class, 'report_id');
    }
}
