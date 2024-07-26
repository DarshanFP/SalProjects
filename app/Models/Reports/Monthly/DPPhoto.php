<?php

namespace App\Models\Reports\Monthly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DPPhoto extends Model
{
    use HasFactory;

    protected $table = 'DP_Photos';
    protected $primaryKey = 'photo_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'photo_id',
        'report_id',
        'photo_path',
        'photo_name',
        'description',
    ];

    public function report()
    {
        return $this->belongsTo(DPReport::class, 'report_id', 'report_id');
    }
}
