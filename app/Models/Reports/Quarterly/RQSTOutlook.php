<?php

namespace App\Models\Reports\Quarterly;

use App\Models\Reports\Quarterly\RQSTReport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQSTOutlook extends Model
{
    use HasFactory;

    protected $table = 'rqst_outlooks';

    protected $fillable = [
        'report_id',
        'date',
        'plan_next_month',
    ];

    public function report()
    {
        return $this->belongsTo(RQSTReport::class, 'report_id');
    }
}
