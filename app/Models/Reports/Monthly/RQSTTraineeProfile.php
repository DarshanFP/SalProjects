<?php

namespace App\Models\Reports\Monthly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $report_id
 * @property string|null $education_category
 * @property int|null $number
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Monthly\DPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTTraineeProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTTraineeProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTTraineeProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTTraineeProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTTraineeProfile whereEducationCategory($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTTraineeProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTTraineeProfile whereNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTTraineeProfile whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQSTTraineeProfile whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RQSTTraineeProfile extends Model
{
    use HasFactory;
    protected $table = 'rqst_trainee_profile';
    protected $fillable = [
        'report_id', 'education_category', 'number'
    ];

    public function report()
    {
        return $this->belongsTo(DPReport::class, 'report_id');
    }
}
