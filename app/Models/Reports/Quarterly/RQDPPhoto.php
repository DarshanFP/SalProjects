<?php

namespace App\Models\Reports\Quarterly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQDPPhoto extends Model
{
    use HasFactory;

    protected $table = 'rqdp_photos';

    protected $fillable = [
        'report_id',
        'photo_path',
        'description',
    ];

    public function report()
    {
        return $this->belongsTo(RQDPReport::class, 'report_id');
    }
}
