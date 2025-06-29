<?php

namespace App\Models\Reports\Monthly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $report_id
 * @property string|null $age_category
 * @property string|null $status
 * @property int|null $number
 * @property int|null $total
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Monthly\DPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereAgeCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereTotal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQWDInmatesProfile whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RQWDInmatesProfile extends Model
{
    use HasFactory;

    protected $table = 'rqwd_inmates_profiles';

    protected $fillable = [
        'report_id',
        'age_category',
        'status',
        'number',
        'total', // total count fo the category

    ];

    public function report()
    {
        return $this->belongsTo(DPReport::class, 'report_id');
    }
}
