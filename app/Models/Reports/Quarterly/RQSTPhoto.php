<?php

namespace App\Models\Reports\Quarterly;

use App\Models\Reports\Quarterly\RQSTReport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQSTPhoto extends Model
{
    use HasFactory;

    protected $table = 'rqst_photos';

    protected $fillable = [
        'report_id',
        'photo_path',
        'description',
    ];

    public function report()
    {
        return $this->belongsTo(RQSTReport::class, 'report_id');
    }
}
