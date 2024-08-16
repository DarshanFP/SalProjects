<?php

namespace App\Models\Reports\Monthly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
