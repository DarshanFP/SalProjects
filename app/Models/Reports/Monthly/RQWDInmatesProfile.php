<?php

namespace App\Models\Reports\Monthly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
