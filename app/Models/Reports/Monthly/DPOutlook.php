<?php

namespace App\Models\Reports\Monthly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DPOutlook extends Model
{
    use HasFactory;

    protected $table = 'DP_Outlooks';
    protected $primaryKey = 'outlook_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'outlook_id',
        'report_id',
        'date',
        'plan_next_month',
    ];

    public function report()
    {
        return $this->belongsTo(DPReport::class, 'report_id', 'report_id');
    }
}
