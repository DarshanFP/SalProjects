<?php

namespace App\Models\Reports\Monthly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $outlook_id
 * @property string $report_id
 * @property string|null $date
 * @property string|null $plan_next_month
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Monthly\DPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook query()
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook whereOutlookId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook wherePlanNextMonth($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|DPOutlook whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
