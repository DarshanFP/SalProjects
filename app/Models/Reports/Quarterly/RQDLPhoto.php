<?php

namespace App\Models\Reports\Quarterly;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RQDLPhoto extends Model
{
    use HasFactory;

    protected $table = 'rqdl_photos';  // Ensure the table name is correct

    protected $fillable = [
        'report_id',
        'path',
        'description',
    ];

    public function report()
    {
        return $this->belongsTo(RQDLReport::class, 'report_id');
    }
}
