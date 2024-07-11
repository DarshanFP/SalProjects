<?php

namespace App\Models\Reports\Quarterly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQISPhoto extends Model
{
    use HasFactory;

    protected $table = 'rqis_photos';

    protected $fillable = [
        'report_id',
        'photo_path',
        'description',
    ];

    public function report()
    {
        return $this->belongsTo(RQISReport::class, 'report_id');
    }
}
