<?php

namespace App\Models;

use App\Models\Reports\Monthly\DPReport;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportComment extends Model
{
    use HasFactory;

    protected $primaryKey = 'R_comment_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'R_comment_id',
        'report_id',
        'user_id',
        'comment',
    ];

    public function report()
    {
        return $this->belongsTo(DPReport::class, 'report_id', 'report_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
