<?php

namespace App\Models\Reports\Monthly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $report_id
 * @property string|null $age_group
 * @property string|null $education
 * @property int|null $up_to_previous_year
 * @property int|null $present_academic_year
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Reports\Monthly\DPReport $report
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile whereAgeGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile whereEducation($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile wherePresentAcademicYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile whereUpToPreviousYear($value)
 * @method static \Illuminate\Database\Eloquent\Builder|RQISAgeProfile whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class RQISAgeProfile extends Model
{
    use HasFactory;

    protected $table = 'rqis_age_profiles';

    protected $fillable = [
        'report_id',
        'age_group',
        'education',
        'up_to_previous_year',
        'present_academic_year',
    ];

    public function report()
    {
        return $this->belongsTo(DPReport::class, 'report_id');
    }
}
