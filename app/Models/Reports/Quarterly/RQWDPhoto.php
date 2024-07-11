<?php

namespace App\Models\Reports\Quarterly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQWDPhoto extends Model
{
    use HasFactory;

    protected $table = 'rqwd_photos';

    protected $fillable = [
        'report_id',
        'photo_path',
        'description',
    ];

    public function report()
    {
        return $this->belongsTo(RQWDReport::class, 'report_id');
    }
}
