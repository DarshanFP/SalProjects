<?php

namespace App\Models\Reports\Quarterly;

use App\Models\reports\Quarterly\RqstReport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQSTTraineeProfile extends Model
{
    use HasFactory;

    protected $table = 'rqst_trainee_profile';

    protected $fillable = [
        'report_id',
        'education_category',
        'number',
    ];

    public function report()
    {
        return $this->belongsTo(RQSTReport::class, 'report_id');
    }
}
