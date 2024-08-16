<?php

namespace App\Models\Reports\Monthly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
