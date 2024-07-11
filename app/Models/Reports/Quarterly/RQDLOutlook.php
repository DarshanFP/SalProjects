<?php

namespace App\Models\Reports\Quarterly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQDLOutlook extends Model
{
    use HasFactory;

    protected $table = 'rqdl_outlooks';
    protected $fillable = [
        'report_id',
        'date',
        'plan_next_month',
    ];
    
    public function report()
    {
        return $this->belongsTo(RQDLReport::class, 'report_id');
    }
}
